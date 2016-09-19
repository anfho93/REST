<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
//require APPPATH . '/libraries/REST_Controller.php';
require 'EthRESTController.php';
/**
 * Clase que representa el endpoint de descargas, para que los usuarios
 * de las aplicacione pueda reportar datos.
 */
class Downloads extends EthRESTController {

    /**
     * Constructor de la clase, verifica la existencia de la funcion requerida
     */
    public function __construct() {
        parent::__construct();
        $methodname = strtolower("index_" . $this->request->method);
        $this->load->model(ETHVERSION . 'Download', "download");
        if (method_exists($this, $methodname)) {
            $this->$methodname();
        }
    }

    /**
     * Permite realizar funcones de registro de usuario de aplicacion (descargas)
     */
       public function index_post() {        
        if ($this->_pre_get() != null) {
            switch ($this->_pre_get()) {
                case "id" : 
                       $this->getIdDownload();                    
                    break;
                
            }
        } else {
            $this->response([
            'status' => FALSE,
            'message' => "indique que desea obtener"
                ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    
    
    /**
     * Esta funcion permite crear u obtener un identificador de descarga o uso de una app
     * un identificador que sera unico una vez sea obtenido, todos 
     * @param iddevice identificador unico del dispositivo 
     * @param string model  nombre del modelo del dispositivo
     * @param string naem nombre del dispositivo
     * @param string platformname nombre del sistema operativo
     * @param string platformversion version de la plataforma del dispositivo
     * @param string idapp  identificador de la aplicacion
     * @param jsonstring addtionalinfo informacion adicional del dispositivo en formato json
     * @param string versionEthAppsSystem version del sistema de analiticas
     * @throw <400 Bad Request> este mensaje es mostrado cuando hay datos duplicados o en su defecto no se pudo regitrar el usuario
     * @throw <201 Accepted> mensaje mostrado cuando el usuario se registro correctamente.
     * is called, basically if the idApp data is null or empty
     */
    private function getIdDownload(){
        $idDevice = $this->post('iddevice');
        $model = $this->post('model');
        $name = $this->post('name');
        $platformName = $this->post('platformname');
        $platformVersion = $this->post('platformversion');
        $ip =$_SERVER['REMOTE_ADDR'];
        $idApp = $this->post('idapp');
        $additionalInfo = $this->post('additionalinfo');
        $versionEthAppsSystem = $this->post('versionEthAppsSystem');
        $appversion = "none";        
        if($this->validateDataAndApp($idApp)) {
            $this->load->model(ETHVERSION.'Download');
            $assignedId = $this->download->assignIdDownload($idDevice,$model,$name,$platformName,$platformVersion,$ip,$idApp,$additionalInfo, $appversion);
            //$this->prepareAndResponse("200","Success",array("idDownload"=> "$assignedId"));			
            $this->response([
                    'status' => TRUE,
                    'idDownload' => "$assignedId"
                        ], REST_Controller::HTTP_ACCEPTED);
        }else{
              $this->response([
                    'status' => false,
                    'message' => "error in the data"
                        ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
  
    

}
