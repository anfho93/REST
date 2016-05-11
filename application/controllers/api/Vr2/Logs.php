<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Log
 *
 * @author andres
 */
defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
//require APPPATH . '/libraries/REST_Controller.php';
require 'EthRESTController.php';

class Logs extends EthRESTController {

    public function __construct() {
        parent::__construct();
        $this->load->model(ETHVERSION . "log", "logs");
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
                case "categories":
                    $this->getCategories();
                    return;
                case "types":
                    $this->getTypes();
                    break;
                case "logs":
                    $this->getLogs();
                    break;
                case "values":
                    $this->getValues();
                    break;
            }
        } else {
            $this->response([
                'status' => FALSE,
                'message' => "indique que desea obtener"
                    ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    private function getCategories() {
        $idApp = $this->get('idApp');
        $appVersion = $this->get('appVersion');
        $email = $this->get('useremail');
        $result = $this->logs->getEventCategories($appVersion, $idApp);
        $this->response(['status' => TRUE, "result" => $result], REST_Controller::HTTP_ACCEPTED);
    }

    private function getTypes() {       
        $idApp = $this->get('idApp');
        $appVersion = $this->get('appVersion');
        $email = $this->get('useremail');
        $category = $this->get('category');
        $result = $this->logs->getCategoriesTypes($appVersion, $idApp, $category);
        $this->response(['status' => TRUE, "result" => json_encode($result)], REST_Controller::HTTP_ACCEPTED);

        //$this->prepareAndResponse("200","Success",array("success"=>"false", "result"=> json_encode($result ) ));            
    }

    private function getLogs() {
        
        $idApp = $this->get('idApp');
        $appVersion = $this->getCategories('appVersion');
        $email = $this->get('useremail');
        $category = $this->get('category');
        $type = $this->get('type');
        $result = $this->logs->getTypesLogs($appVersion, $idApp, $category, $type);
        $this->response(['status' => TRUE, "result" => json_encode($result)], REST_Controller::HTTP_ACCEPTED);
        //$this->prepareAndResponse("200","Success",array("success"=>"false", "result"=> json_encode($result ) ));
    }

    private function getValues() {
        //ANTES TODOS TENIAN LA VERSION 1.1 DE LOS MODELOS
        
        $idApp = $this->get('idApp', 'base64');
        $appVersion = $this->get('appVersion', 'base64');
        $email = $this->get('useremail', 'base64');
        $category = $this->get('category', 'base64');
        $type = $this->get('type', 'base64');
        $log = $this->get('log', 'base64');
        $result = $this->logs->getTypesLogs($appVersion, $idApp, $category, $type, $log);
        $this->response(['status' => TRUE, "result" => json_encode($result)], REST_Controller::HTTP_ACCEPTED);
        // $this->prepareAndResponse("200","Success",array("success"=>"false", "result"=> json_encode($result ) ));
    }

    
    public function index_post() {
        $idApp = $this->post('idApp');
        $idDownload = $this->post('idDownload');
        $versionEthAppsSystem = $this->post('versionEthAppsSystem');
        $idVersion = $this->post('idVersion');
        $idSession = $this->post('idSession');
        $log = $this->post('log');
        $category = $this->post('category');
        $type = $this->post('type');
        $value = $this->post('value');

        $idApp = str_replace("|", "", $idApp);
        $idDownload = str_replace("|", "", $idDownload);
        $log = str_replace("|", "", $log);
        $category = str_replace("|", "", $category);
        $type = str_replace("|", "", $type);
        $value = str_replace("|", "", $value);

        //TODO HACER LA VALIDACION DE EL ENVIO DE LOGS.
        if ($this->verifySession($idDownload, $idApp, $idSession))
                        {
            $this->report($versionEthAppsSystem, $idVersion, $idDownload, $idApp, $idSession, $log, $category, $type, $value);
            //$this->prepareAndResponse("200", "Success");
            $this->response(['status' => TRUE, "message" => "Success"], REST_Controller::HTTP_ACCEPTED);
        } else
        {//$this->prepareAndResponse("403", "Fail");
         $this->response(['status' => FALSE, "message" => "Fail"], REST_Controller::HTTP_FORBIDDEN);
        }        
    }

    /**
     * Esta funcion realiza un reporte de un evento en la base de datos.
     * @param string $versionEthAppsSystem version la api.
     * @param string $idversion, version de la aplicacion
     * @param string $idDownload en base64, identificador de la descarga.
     * @param string $idApp en base64, identificador de la aplicacion.
     * @param string $idSession en base64, id de la session
     * @param string $log en base64, evento a registrar.	
     * @param string $category en base64, categoria del evento a registrar.	, por defecto =  default
     * @param string $type en base64, que tipo de evento es.	
     * @param string $value en base64, valor del evento a registrar.	
     * @return void
     */
    private function report($versionEthAppsSystem, $idVersion, $idDownload, $idApp, $idSession, $log, $category = "Default", $type = "Default", $value = "none") {
        $this->logs->reportLog($versionEthAppsSystem, $idVersion, $idDownload, $idApp, $idSession, $log, $category, $type, $value);
    }

    public function index_put() {
        $this->response([
            'status' => FALSE,
            'message' => "No implementado aun"
                ], REST_Controller::HTTP_BAD_REQUEST);
    }

    public function index_delete() {
        $this->response([
            'status' => FALSE,
            'message' => "No implementado aun"
                ], REST_Controller::HTTP_BAD_REQUEST);
    }

}
