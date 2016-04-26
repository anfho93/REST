<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/*require_once BASEPATH . 'libraries/aws/aws-autoloader.php';
use Aws\DynamoDb\Iterator\ItemIterator;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Common\Enum\Region;
use Aws\DynamoDb\Enum\Type;
use Aws\DynamoDb\Enum\AttributeAction;
use Aws\DynamoDb\Enum\ReturnValue;*/
/**
* Clase que representa al Modelo de las Aplicaciones.
*
* Esta clase mapea la tabla ethas_applications de  la base de datos no relacional de DynamoDB.
*
*  @author Andres felipe Herrera <anfho93@gmail.com>
*  @version 1.1
*/
class App extends CI_Model {

    /**
    * @var string Variable que representa el HashKey de la tabla id_app
    */
    var $hashname = "id_app";
    /**
    *@var  string nombre de la tabla
    */
    var $tablename = "ethas_application";    
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
        $this->otherdb = $this->load->database('impala', TRUE); 
    }

    
   
    /**
     * Permite obtener las estadisticas de una aplicacion 
     * estas estadisticas deben ser precalculadas diariamente para determinar el valor real.
     * @param string $idApp  identificador de la aplicacion
     * @return array  estadisticas de las sesiones y los usuarios de una app. 
     */
    function getStats($idApp){
     
        $this->db->select('total_sessions, total_users');
        //se puede optimizar reduciendo la cantidad de columnas a traer.
        $result = $this->db->get_where($this->tablename,  array('id_app' => $idApp));
        //se supone que solo existe una downloadcon ese id download y con ese id_app
        if($result->num_rows()==0)
          return null;
        $return = $result->_fetch_assoc();       
        return $return;
    }

    /**
    * Reporta una nueva sesion en la aplicacion.
    * @param  string $idApp, identificador de la aplicacion.
    */
    function reportNewUser($idApp){
   
         $query ="UPDATE ethas_application SET total_users = total_users+1 where id_app = '$idApp'";     
         return $this->db->query($query);        
    }

    /**
    * Reporta una nueva sesion en la aplicacion.
    * @param  string $idApp, identificador de la aplicacion.
    */
    function reportSession($idApp){
         $query ="UPDATE ethas_application SET total_sessions = total_sessions+1 where id_app = '$idApp'";     
         return $this->db->query($query);      
    }

    /**
    * Cambia el valor actual de las sesiones en la aplicacion.
    * @param  string $idApp, identificador de la aplicacion.
    * @param  int $sessiones, numero de sesiones.
    */
    function setTotalSessions($idApp , $sessiones )
    {
         $query ="UPDATE ethas_application SET total_sessions = $sessiones where id_app = '$idApp'";     
         return $this->db->query($query);  
    }


 
    
    /**
    * Determina si la aplicacion ya a sido registrada en la base de datos.
    * 
    * Estafuncion es ejecutada en muliples ocaciones, a la hora de ingresar datos a la base de datos.
    * @param string $idapp , id de la aplicacion.
    */
    function appExists($idapp) {  
       $result = $this->db->get_where($this->tablename, array("id_app"=> $idapp));
       if($result->num_rows()>0)
       {
         return true;
       } 
         return false;
    }

    /**
    * Esta funcion obtiene todas las aplicaciones de un usuario dado.
    *
    * @param string $user_email , correo electronico del usuario.
    */
    function getApps($user_email){
      //  echo "asdsad";
        $result = $this->db->get_where($this->tablename, array("user_email"=> $user_email));        
        return $result->result_array();
    }   

    /**
    * esta funcion permite resgistrar una aplicacion en la base de datos.
    * 
    * @param string $type , indica el tipo de aplicacion que se esta registrando.
    * @param string $name_app , nombre de la aplicacion.
    * @param string $description descripcion de la aplicacion.
    * @param string $user_email , correo electronico del usuario.
    * @param string platforms, las plataformas o urls de descarga de las aplicaciones.
    *
    * @return false , si no se pudo resgistrar  la aplicacion, o string con el ID del app.
    */
    function registerApp($type, $name_app , $description, $user_email, $platforms){
        $this->load->model('vr1.1/user');
        if($this->user->userExists($user_email))
        {
            $this->key[$this->hashname] =   uniqid("", true);                  
            $this->attributes["type"] = $type;
            $this->attributes["name_app"] = $name_app;
            $this->attributes["description"] = $description;
            $this->attributes["user_email"] = $user_email;
            $this->attributes["registerdate"] = date("Y-m-d");            
            $this->attributes["lastmodification"] = date("Y-m-d"); 
            $this->attributes["active"] = true;
            $this->attributes["platforms"] = $platforms;           
            $this->attributes["total_sessions"] = 0;
            $this->attributes["total_users"] = 0;           

            $this->key = array_merge($this->key,$this->attributes );
            $result = $this->db->insert( "ethas_application",$this->key);
            
            return $result;
        }
        
        return false;
    
              
    }
    
    /**
     * Funcion que permite actualizar los datos de una aplicacion
     * en la base de datos mysql.
     * @param String  $id identificador de la aplicacion a actualizar
     * @param String $type Cambio en el tipo de la aplicacion.
     * @param String $name_app nombre de  la aplicacion
     * @param String $description descripcion para la aplicacion
     * @param String $user_email correo electronico del propietario de la app
     * @param String $platforms plataformas que soporta con sus links respectivos
     * @return boolean true si actualizo false si no.
     */
    function updateApp($id, $type, $name_app , $description, $user_email, $platforms){
        $this->load->model('vr1.1/user');
        if($this->user->userExists($user_email))
        {
            $this->attributes["type"] = $type;
            $this->attributes["name_app"] = $name_app;
            $this->attributes["description"] = $description;
            $this->attributes["user_email"] = $user_email;  
            $this->attributes["platforms"] = $platforms;         
            
            $this->db->where($this->hashname, $id);
            $result = $this->db->update( "ethas_application",$this->attributes);
            
            return $result;
        }
        
        return false;
    
    }
    
    /**
     * permite ver las versiones de una aplicacion
     * @param type $id_app identificador de la aplicacion
     * @param type $user_email correo electronico de propietario de la app
     * @return type
     */
    function getAppVersions($id_app, $user_email=null){
        $this->db->select("versions");
        $this->db->from("ethas_appversion");
        $this->db->where("id_app", $id_app);
        $result = $this->db->get();
        return $result->result_array();
    }

  

    /**
    * Funcion que retorna la url de la aplicacion basado en una plataforma dada.
    * @param string $id_app, ID de la aplicacion 
    * @param string $plataform, indica si es android, windows, MAC, entro otros.
    * @return string, Url correspondiente a la plataforma que se busca.
    */
    public function isPlatform($id_app, $platform){
        
        if($id_app!=null){

            $result = $this->db->get_where($this->tablename, array("id_app"=>$id_app));    
            $resultarray  = $result->result_array() ;
            
                $json = str_replace('\\', "", $resultarray[0]["platforms"]);

                $var = json_decode( $json );
                foreach ($var as  $value) {
                    if($value->name == $platform){                        
                        return $value->url ;
                    }
                }
                return "No Url for this platform";
            
        }
    }

    /**
    * Esta funccion recive un conjunto de objetos con los datos de las plataformas seleccionadas.
    * @param Object plataforms, Objeto que contiene cada una de las urls de cada plataforma.
    */
    function parsePlatforms($platforms){
        $array =array();
        foreach ($platforms as $platform) {
            if( $platform->url !== ""){
                    $array[$platform->name] = $platform->url+" ";
            }
            else{
                 $array[$platform->name] = "none";   
            }
        }
        return $array;
    }   

  
    /**
    * Esta funcion determina si una aplicacion esta activa o no.
    * @param string $idApp ID de la alicacion 
    * @return boolean , si la aplicacion esta activa o no. 
    */
    function isActiveApplication($idApp){
       
        $result = $this->db->get_where($this->tablename, array("id_app"=>$idApp));
        if($result->num_rows() == 0)
        {
            return false;
        }else{
            $result = $result->first_row("array");
            return $result["active"] == 1;
        }
    }

    
    /**
    * Activa una aplicacion.
    * @param  string $idApp Id de la aplicacion a activar
    * @param  string $appname nombre de la aplicacion
    */
    function activateApplication($idApp, $appname=null){
        $result = $this->db->update('ethas_application', array("active" =>  1), "id_app = '$idApp' ");        
       return $result;
    }

    /**
    * Activa una aplicacion.
    * @param  string $idApp Id de la aplicacion a activar
    * @param  string $appname nombre de la aplicacion
    */
    function  deactivateApplication($idApp, $appname){
       $result = $this->db->update('ethas_application', array("active" =>  0), "id_app = '$idApp' ");        
       return $result;
    }


   
}
?>