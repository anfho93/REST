<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Session
 *
 * @author andres
 */
defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
//require APPPATH . '/libraries/REST_Controller.php';
require 'EthRESTController.php';

class Session extends EthRESTController {

    public function __construct() {
        parent::__construct();
        $methodname = strtolower("index_" . $this->request->method);
        if (method_exists($this, $methodname)) {
            $this->$methodname();
        }
    }

    public function index_get() {
        if ($this->_pre_get() != null) {
            switch ($this->_pre_get()) {
                case "id":
                    
                    break;
            }
        } else {
            $this->response([
                'status' => FALSE,
                'message' => "No implementado aun"
                    ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Funcion principal del controlador y servicio web para obtener el id de una nueva session
     * @param string $idApp, identificador de la aplicacion.
     * @param string $idDownload, identificador de la descarga.
     * @param string $versionEthAppsSystem , version que el usuario usa de la API
     * @param string $idVersion , version de la apliacaciones que genera la session, esta sera registrada en el evento o log.	
     * @return json string , respuesta del servidor con el Id de la session creada.
     */
    private function iniciarSesion($lastIDSession) {
        //setea el maximo tiempo de vida de la sesion basado en la ultima modificacion.
        //destruye los datos de una sesion anterior.
        session_id($lastIDSession);
        session_start();
        if (isset($_SESSION["dloged"])) {
            ///echo "string";
            session_unset();
            session_destroy();
        }
        if (!isset($_SESSION["dloged"])) {
            $_SESSION["dloged"] = $lastIDSession;
            $_SESSION["start"] = time();
            $_SESSION["lastactivity"] = time();
            return true;
        }
        
    }

    public function index_post() {
        $idApp = $this->post('idApp');
        $idDownload = $this->post('idDownload');
        $versionEthAppsSystem = $this->post('versionEthAppsSystem');
        $idVersion = $this->post('idVersion');
        
        if ($this->validateDataAndApp($idApp)) {
            $this->load->model(ETHVERSION . 'session');
            $lastIDSession = $this->session->createSession($idApp, $idDownload);
            if ($lastIDSession != false) {
                $lastIDSession = str_replace(".", "-", $lastIDSession);
                $result = $this->iniciarSesion($lastIDSession);
                $this->loadController(VERSION_PATH . "/reportLog", 'reportLogVersion');
                $this->reportLogVersion->report($versionEthAppsSystem, $idVersion, $idDownload, $idApp, $lastIDSession, 'App Session Created');
                //$this->prepareAndResponse("200", "Success", array("idSession" => "" . $lastIDSession, "el" => session_id()));
                $this->response([
                    'status' => TRUE,
                    'message' => "Success",
                    array("idSession" => "" . $lastIDSession, "el" => session_id())
                        ], REST_Controller::HTTP_ACCEPTED);
            } else {
                $this->response([
                    'status' => FALSE,
                    'message' => "Given id download doent exist"
                        ], REST_Controller::HTTP_BAD_REQUEST);
            }
        } else {
            $this->response([
                'status' => FALSE,
                'message' => "Error con los datos de la app"
                    ], REST_Controller::HTTP_BAD_REQUEST);
        }
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
