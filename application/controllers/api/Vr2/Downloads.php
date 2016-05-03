<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';

class Downloads extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $methodname = strtolower("index_" . $this->request->method);
        if (method_exists($this, $methodname)) {
            $this->$methodname();
        }
    }

    private function _pre_get() {
        $segmento = $this->uri->segment(4);
        return $segmento;
    }

    public function index_get() {        
        if ($this->_pre_get() != null) {
            switch ($this->_pre_get()) {
                case "statistics":
                    $this->statistics();
                    return;
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

    private function statistics() {
        $this->response([
            'status' => TRUE,
            'result' => "estas son las estadisticas",
            'message'=> "message"
                ], REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }
    
    
    private function getIdDownload(){
        echo "entre";
        $idDevice = $this->get('idDevice');
        $model = $this->get('model');
        $name = $this->get('name');
        $platformName = $this->get('platformName');
        $platformVersion = $this->get('platformVersion');
        $ip =$_SERVER['REMOTE_ADDR'];
        $idApp = $this->get('idApp');
        $additionalInfo = $this->get('additionalInfo');
        $versionEthAppsSystem = $this->get('versionEthAppsSystem');
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
    
    private function validateDataAndApp($idApp) {
        if($idApp != "") {            
            $this->load->model(ETHVERSION.'app');
            if(!$this->app->appExists($idApp)){
                 $this->response([
                    'status' => TRUE,
                    'message' => "App not present in database"
                        ], REST_Controller::HTTP_BAD_REQUEST);
            }           
            else{
                return true;               
            }
        }
        else{
             $this->response([
                    'status' => TRUE,
                    'message' => "Bad Request"
                        ], REST_Controller::HTTP_BAD_REQUEST);              
        }
        
        return false;            
    }
    
    

}
