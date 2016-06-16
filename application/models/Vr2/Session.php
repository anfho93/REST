<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Clase que representa al Modelo de las Session.
*
* Esta clase mapea la tabla ethas_sessions de  la base de datos no relacional de DynamoDB.
*
*  @author Andres felipe Herrera <anfho93@gmail.com>
*  @version 1.1
*/
class Session extends CI_Model {


    /**
    * @var string Variable que representa el HashKey de la tabla id_app
    */
    var $hashname = "id_session";
   /**
    * @var string Variable que representa el RangeKey de la tabla id_app
    */  
    var $rangename = "";
     /**
    *@var  string nombre de la tabla
    */
    var $tablename = "session";
   /**
    * @var Array    utilizado para almacenas valores de las llaves.
    */
    var $key = array();   
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
    
    /*-------------------------------------------------------------------------------------------------------------
            GET ID SESSION FUNCTIONS  
    -------------------------------------------------------------------------------------------------------------*/

    

    /**
    * Esta funcion permite crear una nueva session a  una download de un dispositivo.
    * esta se ejecuta normalmente cada vez que el usuario inicia la aplicacion.
    *
    *@param string $idApp  id de la aplicacion
    *@param string $idDownload, id de la descarga registrada en la base dedatos.
    *@return string, id de la session en caso de ser creada, o un mensaje indicando la razon por la que no, 
    */
    function createSession($idApp,$idDevice) {  
        $this->load->model(ETHVERSION."download","download");
        $this->load->model(ETHVERSION."app");
        
        $time = time();
        $newIDSession =  uniqid("", true);
        $newIDSession = str_replace(".", "-", $newIDSession);
        $key[$this->hashname] = "".$newIDSession;
        //se debe reemplazar cualquier caracter enviado por los usuarios que interfiera  
        
        $row = $idDevice.",".$idApp.",".$time.",".$key[$this->hashname];
        //echo $row .'\n';
       // print_r($idApp);
        $ch = curl_init(); 
        $ip = $_SERVER['REMOTE_ADDR'];
      
	$url = RUTASESION."?ip=$ip&data=$row";
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
	//curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100);
	$output=curl_exec($ch);	 	
        //echo $output;
	curl_close($ch);
            
        file_put_contents ( DATAROUTE."session",  $row."\n", FILE_APPEND );
        
        // $this->app->reportSession($idApp);
        
        return $newIDSession;        
           
    }
    
   



   
      /**
    * Funcion que verifica si la session existe 
    *
    * Esta funccion existe para hacer validaciones y que no se puedan enviar datos de maner aleatoria que ocupen espacio innecesario.
    * @param string $idSession, id de la session a verificar.
    * @return boolean si la session existe o no.
    */
    function existSession($idsession, $id_app){
        //TODO ESTABLECER CONEXION CON IMPALA PARA ESTA FUNCIONALIDAD.
        $query = "";
        //si no funciona con este codigo se debe crear nuestra propia consulta.
        $result = $this->db->get_where($this->tablename, array('id_session' => $idsession, "id_app" => $id_app ));
        print_r($result->num_rows);
        return $result->num_rows > 0;
    }

    /**
    * Funcion que permite contar las sesiones de una aplicacion.
    * @param string $idApp, identificadorde la aplicacion.
    * @return int, cantidad de sesiones por aplicación.
    */
    function countSessions($idApp){
       $query =  "SELECT count(id_session) as count from ethas_session where id_app = '$idApp' ";
       $result = $this->db->query($query);
       if($result->num_rows > 0)
       {    
            $row = $result->first_row("array");
            return $row["count"];
       }       
    }



    




}
?>