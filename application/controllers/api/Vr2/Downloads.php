<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
//require APPPATH . '/libraries/REST_Controller.php';
require 'EthRESTController.php';

class Downloads extends EthRESTController {

    public function __construct() {
        parent::__construct();
        $methodname = strtolower("index_" . $this->request->method);
        $this->load->model(ETHVERSION . 'Download', "download");
        if (method_exists($this, $methodname)) {
            $this->$methodname();
        }
    }

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
