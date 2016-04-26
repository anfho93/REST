<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once BASEPATH . 'libraries/aws/aws-autoloader.php';
use Aws\DynamoDb\Iterator\ItemIterator;
/**
* Clase que representa al Modelo de las Variables.
*
* Esta clase mapea la tabla ethas_variables de  la base de datos no relacional de DynamoDB.
*
*  @author Andres felipe Herrera <anfho93@gmail.com>
*  @version 1.1
*/
class StateVariable extends CI_Model {

	/**
    * @var string Variable que representa el HashKey de la tabla id_app
    */
    var $hashname = "id_variable";
    /**
    * @var string Variable que representa el Index_HashKey de la tabla id_app
    */
    var $rangekey = "id_download";    
    /**
    * @var  string nombre de la tabla
    */
    var $tablename = "ethas_statevariable";

    /**
    * @var Array    utilizado para almacenas valores de las llaves.
    */
    var $keys= array();   
    /**
    *@var Array     utilizado para almacenas valores de los atributos a agregar a un Item de la tabla..
    */  
    var $attributes = array();
    /**
    * Constructor de la clase.
    */
    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }

    /**
    * Esta funcion permite crear una variable de estado, las variales de estado son por ejm.
    * variables que representan
    */
    function createVariable($id_download, $id_app, $name, $value){
    	/**
    	* Al crear una variable se debe cambiar el estado de la qe se encuentra en la descarga.
    	*/
    	$name = strtolower($name);
    	$this->load->model(ETHVERSION."variable");
        

         $id_variable = $this->variable->getStateVariable($id_app,$name);
         echo "variable ".$id_variable;
         if( $id_variable != false )
         {
            
            //si la variable de estado  ya esta creada
            //se debe validar que la descarga tenga un estado para esta variable
            if($this->existVariableDownload($id_variable, $id_download))
            {
                $this->setStateVariable($id_variable, $id_download, $name, $value, $id_app);
            }else{

                $this->keys[$this->hashname] =  $id_variable;//id de la variable
                $this->keys[$this->rangekey]    = "$id_download" ;
                
                $this->attributes["timestamp"] = date("Y-m-d");       
                $this->attributes["value"] = $value;       

                $this->keys = array_merge($this->keys, $this->attributes );   
                $result = $this->db->insert($this->tablename, $this->keys);
                if($result){
                   return $this->keys[$this->hashname];
                }else{
                    return false;        
                }
            }
            
            return $id_variable;
         }else{
            //crea una variable con la clase estado en el sistema.
                $id_variable = $this->variable->registerVariable($name, $value, "STATE", "none" ,$id_app );
                if($id_variable == false){
                    return false;
                }
         }   
        
       
    }
    /**
    * Funcion que valida la existencia de un registro de una variable
    * de estado para una descarga.
    */
    function  existVariableDownload($id_variable, $id_download){
        $result = $this->db->get_where($this->tablename, array('id_variable' => $id_variable, 'id_download' => $id_download));
        if($result->num_rows == 1)
            return true;
        return false;
    }

    /**
    * Modificamos el estado de una variable de estado
    * la actualizamos para el idDownload respectivo.
    * @param string $id_download, 
    * @param string $name, nombre de la variable a modificar
    * @param string $value, valor de nuevo de la variable.
    * @param string $id_app, id app propietaria de la varible de estado.
    * @return boolean , resultado de la operación.
    */
    function setStateVariable($id_variable, $id_download, $name, $value, $id_app){
        $id_variable = $this->variable->getStateVariable($id_app,$name);
        $array = array("value" => $value);
        //$this->db->where("id_variable" , $id_variable);
        return $this->db->update($this->tablename, $array,  array('id_variable' => $id_variable, "id_download"=>$id_download));
    }

    /**
    * Migra los datos desde un archivo hasta la base de datos MYSQL.
    */
     function migrateDatatoMYSQL(){    
//  value timestamp id_variable_app id_download
        $myfile = fopen("C://xampp/htdocs/Web/DynamoDB/statevariable.out", "r") or die("Unable to open file!");
        $key =  explode(" ", fgets($myfile));
       
        $var = array();
        foreach ($key as $keys => $value) {
            if($keys == 2)
            {
              $key[$keys] = trim("id_variable");
            }else
            $key[$keys] = trim($value);
        }
        //print_r($key);
        while(!feof($myfile)) {
            $data = (explode(" ", fgets($myfile)));

            for ($i=0; $i < sizeof($key); $i++) { 
             if($i == 2)
              {
                echo $data[$i];
                $a = explode('-', $data[$i]);
                $var["$key[$i]"] = $a[0];
              } else if($i ==1)
              {
                $var["$key[$i]"] = date("Y-m-d", (int)$data[$i]);
              }
              else
                $var["$key[$i]"] = $data[$i];
            }
             print_r($var);
            print_r($this->db->insert( "ethas_statevariable",$var));
           
        }
    }

 


}
