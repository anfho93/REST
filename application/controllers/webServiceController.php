<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/** 
*   @authors   Carlos Beltrán <carlos@etherealgf.com> 
*   @version   1.0
*   @date      Abril 12 2014
* 
*   @class  WebServiceController
*   @brief  Class containing utilities for the Controllers that inherited from it.
*
*   This Controller has some common functions among other controllers created for responding 
*   WebService commands.
*/
class WebServiceController extends CI_Controller {

    private $isLive = true;
    /**
    * @var variable utilizada para 
    */
    private $response = array();
    /**
    * Constructor de la clase WebServiceController
    */
    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }

    function session_valid_id($session_id){
       return preg_match('/^[-,a-zA-Z0-9]{1,128}$/', $session_id) > 0;
    }

    public function verifySession( $lastIDSession){
        //para modo desarrollo
        //return true;  
        try{
            if(!$this->session_valid_id($lastIDSession))
            {
                return false;
            }
            session_id($lastIDSession);
            session_start();
            //si esto no pasa, es por que el usuario se salto el inicio de sesion.
            if(isset($_SESSION["dloged"]) && isset($_SESSION["lastactivity"]) )// && $_SESSION["dloged"] == $idApp.$idDownload)
            {
                $now = time();
                if ($now - $_SESSION['lastactivity'] > TIEMPOINACTIVIDAD ) {
                   // Si desde el ultimo momento de actividad, 
                   // se sobrepasa el tiempo maximo de inactividad se cierra la sesion.
                    session_unset();
                    session_destroy();
                    return false;
                }else{
                    //se actualiza el tiempo de la ultima actividad.
                    $_SESSION["lastactivity"] = time();
                    return true;          
                }
            }        
            return false;    
        }catch(Exception $e)
        {
            return false;
        }
        
    }
    /**
    * @brief Validate if an Application with a given Id exist on the database, 
    *
    * This function use the 'app' model for retrieving if an especific app exist on 
    * database.
    *
    * @throw <400 Bad Request> This message is shown if the App has not id when the function 
    * is called, basically if the idApp data is null or empty
    *
    * @throw <401 App not present in database> This message is shown when the id is given 
    * but no application exists in database with this id.
    *
    * @param string $idApp The app id in database
    *
    * @return true if the app exists and false if it doesn't
    */
    public function validateDataAndApp($idApp) {

        if($idApp != "") {            
            $this->load->model(ETHVERSION.'app');
            if(!$this->app->appExists($idApp))
            {
                $this->prepareAndResponse("401","App not present in database");                 
            }           
            else
            {
                return true;               
            }
        }
        //Si no llegaron los datos
        else
        {
            $this->prepareAndResponse("400","Bad Request");                
        }
        
        return false;            
    }

    public function isUsersApp($idApp, $user_email ){
        $this->load->model(ETHVERSION.'app');
        
        return $this->app->isUsersApp($idApp, $user_email);
        
    }


    /**
    * @brief Retrive data from the url parameters, 
    *
    * 
    * This function retrieve data from the given url parameters, if the variable $isLive is true
    * this function only get data using POST data, if it is false then retrieve using both GET
    * and POST data.
    *
    * This function also support encodig for the given parameters.
    *
    * @param string $dataName The name of the data to retrieve
    * @param string $typeEncoding The app id in database
    *
    * @return true if the app exists and false if it doesn't
    */
    public function getUrlData($dataName,$typeEncoding = "none") {

        $responseData = "";

        if(!$this->isLive) {
            $responseData = $this->input->get_post($dataName);
        }
        else
        {
            $responseData = $this->input->post($dataName);          
        }

        switch ($typeEncoding) {
            case 'base64':
                return base64_decode($responseData);
                break;
            
            default:
                return $responseData;
                break;
        }
    }
    /**
    * Esta funcion genera una respuesta en Json string para el usuario.
    *
    * @param int $code, codigo de respuesta http.
    * @param string $message , mensaje de respuesta para el usuario.
    * @param array parametros de respuesta para el usuario.
    * @return void
    */
    public function prepareAndResponse($code,$message,$arrParams = array()){
        $this->prepareResponse($code,$message,$arrParams);
        $this->respond();
    }
    
    /**
    * preppara algunas variables de la clase para ser utilizadas en una futura respuesta.
    * @param int $code, codigo de respuesta http.
    * @param string $message , mensaje de respuesta para el usuario.
    * @param array parametros de respuesta para el usuario.
    * @return void
    * 
    */
    public function prepareResponse($code,$message,$arrParams = array()){
        //$response = array();
        $result = array(
            "result"=>$code,
            "message"=>$message
        );     

        $result = array_merge($result, $arrParams);

        $this->response = $result;   
    }  

    /**
    * Funcion que carga una vista llamada webServiceResponse
    */
    public function respond() {
        $this->load->view('webServices/webServiceResponse',array("response"=>$this->response));
    }
    
    /**
    * Carga un controller en la ruta indicada,. etos controladores hacen referencia 
    * a los servicios web en la carpeta ethAppsSystemVersions
    * @param string $file_name  , ruta del archivo que contiene el controlador
    * @param string $class_object  , nombre de la clase que contiene el controlador
    * @return boolean, si pudo ser cargado o no
    */
    public function loadController($file_name,$class_object = ""){

        $object_name = $class_object;

        if($class_object=="") 
        {
            $class_object = ucfirst($file_name);
            $object_name = $file_name;
        }
        
        $CI = & get_instance();
      
        $file_path = APPPATH.'controllers/'.$file_name.'.php';
        
        if(file_exists($file_path)){
            require($file_path);          
            $CI->$object_name = new $class_object();
            return true;
        }
        else{
            echo "NON ".$file_path;
            return false;            
        }
    }
}
?>