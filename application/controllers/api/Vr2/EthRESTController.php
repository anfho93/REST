<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';

/**
 * Controlador que contiene  funciones principales y que los demas
 * controladores de servicios REST deben usar.
 */
class EthRESTController extends REST_Controller {

    

    /**
     * Valida un identificador de sesion
     * @param string $session_id identificador de la sesion del usuario del app
     * @return boolean si coincide o no
     */
    function session_valid_id($session_id) {
        return preg_match('/^[-,a-zA-Z0-9]{1,128}$/', $session_id) > 0;
    }

    /**
     * Permite verificar que una sesion creada este aun vigente
     * @param string $lastIDSession ultimo identificador de sesion
     * @return boolean 
     */
    public function verifySession($lastIDSession) {
        //para modo desarrollo
        return true;
        try {
            if (!$this->session_valid_id($lastIDSession)) {
                return false;
            }
            session_id($lastIDSession);
            session_start();
            //si esto no pasa, es por que el usuario se salto el inicio de sesion.
            if (isset($_SESSION["dloged"]) && isset($_SESSION["lastactivity"])) {// && $_SESSION["dloged"] == $idApp.$idDownload)
                $now = time();
                if ($now - $_SESSION['lastactivity'] > TIEMPOINACTIVIDAD) {
                    // Si desde el ultimo momento de actividad, 
                    // se sobrepasa el tiempo maximo de inactividad se cierra la sesion.
                    session_unset();
                    session_destroy();
                    return false;
                } else {
                    //se actualiza el tiempo de la ultima actividad.
                    $_SESSION["lastactivity"] = time();
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Valida que los datos del usuario sean correctos
     * @param type $email, correo del usuario
     * @param type $idApp, id de la aplicacion registrada
     * @return boolean confirmacion de que el usuario y la app son correctos.
     */
    protected function validateData($email, $idApp) {
        $this->load->model(ETHVERSION . "user");
        return $this->user->userHaveApp($email, $idApp);
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
    protected function validateDataAndApp($idApp) {

        if ($idApp != "") {
            $this->load->model(ETHVERSION . 'app');
            if (!$this->app->appExists($idApp)) {
                //$this->prepareAndResponse("401", "App not present in database");
                  $this->response([
            'status' => FALSE,
            'message' => "App not present in database"
                ], REST_Controller::HTTP_BAD_REQUEST);
            } else {
                return true;
            }
        }
        //Si no llegaron los datos
        else {
            //$this->prepareAndResponse("400", "Bad Request");
            $this->response([
            'status' => FALSE,
            'message' => "El app no existe"
                ], REST_Controller::HTTP_BAD_REQUEST);
        }

        return false;
    }

    public function isUsersApp($idApp, $user_email) {
        $this->load->model(ETHVERSION . 'app');

        return $this->app->isUsersApp($idApp, $user_email);
    }
    
    protected function _pre_get() {
        $segmento = $this->uri->segment(4);
        
        return $segmento;
    }
    
     
    /**
    * Carga un controller en la ruta indicada,. etos controladores hacen referencia 
    * a los servicios web en la carpeta ethAppsSystemVersions
    * @param string $file_name  , ruta del archivo que contiene el controlador
    * @param string $class_object  , nombre de la clase que contiene el controlador
    * @return boolean, si pudo ser cargado o no
    */
    protected function loadController($file_name,$class_object = ""){

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
