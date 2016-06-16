<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Screen
 *
 * @author andres
 */
defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
//require APPPATH . '/libraries/REST_Controller.php';
//require APPPATH . '/libraries/REST_Controller.php';
require 'EthRESTController.php';

class Screens extends EthRESTController {

    private $useremail;
    private $idApp;
    private $initialDate;
    private $finalDate;
    private $tipoRetencion;
    public $segment = null;

    public function __construct() {
        parent::__construct();
        $methodname = strtolower("index_" . $this->request->method);
        if (method_exists($this, $methodname)) {
            $this->$methodname();
        }
        $this->load->model(ETHVERSION . "screenstatistics");
        $this->load->model(ETHVERSION . "appstatistics");
    }

    public function index_get() {
        if ($this->_pre_get() != null) {
            switch ($this->_pre_get()) {
                case "views":
                    $this->getViews();
                    break;
                case "interactions":
                    $this->getInteractions();
                    break;
                case "reports":
                    $this->getScreensReport();
                    break;
                case "data":
                    $this->getScreensData();
            }
        } else {
            $this->getScreen();
        }
    }
    
    /**
     * Permite obtener informacion especifica de una pantallas registrada
     */
    private function getScreensData(){        
        $this->loadData($this->screenstatistics);
        $result = null;
        if($this->validateData($this->useremail, $this->idApp ))
        {
            $screename = $this->getUrlData('screenname','base64');
            if($screename != null && $screename != "" )
            {   
                $this->screenstatistics->addCondition($this->segment);
                $result =  $this->screenstatistics->getSpecificScreenData($this->idApp, $this->initialDate, $this->finalDate,$screename); 
            }else{
                 $result = $this->screenstatistics->getScreensData($this->idApp, $this->initialDate, $this->finalDate); 
            }                  
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result));   
            $this->response(['status' => TRUE, "result"=>$result], REST_Controller::HTTP_ACCEPTED);
        }else{
            // datos invalidos
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));            
            $this->response(['status' => FALSE], REST_Controller::HTTP_FORBIDDEN);
        }
    }

    private function getScreensReport() {
        $this->loadData($this->appstatistics);
        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->appstatistics->getGeneralScreensData($this->idApp, $this->initialDate, $this->finalDate);
            $totalScreens = $this->appstatistics->countScreens($this->idApp, $this->initialDate, $this->finalDate);

            if ($totalScreens != 0) {
                for ($i = 0; $i < count($result); $i++) {
                    $result[$i]['Visualization(%)'] = (($result[$i]['pantallas'] / $totalScreens) * 100);
                }
            } else {
                for ($i = 0; $i < count($result); $i++) {
                    $result[$i]['Visualization(%)'] = 0;
                }
            }
            $titulo = array("Screen", "Visualization", "Visualization(%)");
            $this->response(['status' => TRUE, "titles" => $titulo, "result" => $result], REST_Controller::HTTP_ACCEPTED);
        } else {
            $this->response(['status' => FALSE], REST_Controller::HTTP_FORBIDDEN);
        }
    }

    private function getScreen() {
        $this->loadData($this->appstatistics);
        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->appstatistics->getGeneralScreensData($this->idApp, $this->initialDate, $this->finalDate);
            $titulo = array("Screen", "Visualization");
            $this->response(['status' => TRUE, "titles" => $titulo, "result" => $result], REST_Controller::HTTP_ACCEPTED);
            //$this->prepareAndResponse("200", "Success", array("success" => "true", "result" => $result, "titles" => $titulo));
        } else {
            //$this->prepareAndResponse("200", "Fail", array("success" => "false", "result" => array()));
            $this->response(['status' => FALSE], REST_Controller::HTTP_FORBIDDEN);
        }
    }

    private function getInteractions() {
        $this->loadData($this->screenstatistics);
        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->screenstatistics->getInteraction($this->idApp, $this->initialDate, $this->finalDate);
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result  ));            
            $this->response(['status' => TRUE, "result" => $result], REST_Controller::HTTP_ACCEPTED);
        } else {
            // datos invalidos           
            $this->response(['status' => FALSE], REST_Controller::HTTP_FORBIDDEN);
        }
    }

    private function loadData($modelo = null) {
        $this->useremail = $this->get('useremail');
        $this->tipoRetencion = $this->get('type');
        $this->idApp = $this->get('idApp');
        $this->initialDate = $this->get('initialDate'); //fecha en formato (2013-00-00), la diferencia entre esta y finalDate no debe exeder 3 meses o 90 dias
        $this->finalDate = $this->get('finalDate'); //fecha en formato (2013-00-00), esta fecha no debe ser mayor al dia actual.
        $idSegment = $this->get('segment');

        if ($idSegment !== "" && $idSegment !== null) {
            $this->load->model(ETHVERSION . "segment", "seg");
            $segment = $this->seg->getSegment($idSegment);

            if ($segment != null && array_key_exists("valor", $segment) && $modelo != null) {
                $this->segment = $segment["valor"];
                $modelo->addCondition($this->segment);
            } else {
                // echo "Error";
                //$this->prepareAndResponse("500","Fail",array("success"=>"false", "message"=>"No se encontro el segmento" )); 
            }
        }
    }

    private function getViews() {
        $this->loadData($this->screenstatistics);
        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->screenstatistics->getScreenViews($this->idApp, $this->initialDate, $this->finalDate);
            //$this->prepareAndResponse("200", "Success", array("success" => "true", "result" => $result));
            $this->response([
                'status' => TRUE,
                'message' => "success",
                "result" => $result
                    ], REST_Controller::HTTP_ACCEPTED);
        } else {
            // datos invalidos
            $this->response([
                'status' => FALSE,
                'message' => "Fail",
                "result" => array()
                    ], REST_Controller::HTTP_BAD_REQUEST);
            //$this->prepareAndResponse("200", "Fail", array("success" => "false", "result" => array()));
        }
    }

    /**
     * Funcion principal del servicio, aqui se se reciben los parametros via peticion http.
     * @param string $idapp en base64, identificador de la aplicacion.
     * @param string $idDownload en base64, identificador de la descarga.
     * @param string $versionEthAppsSystem en base64, Numero de la version de la API
     * @param string $idVersion en base64, versionn de la aplicacion que obtiene las variables.
     * @param string $idSession en base64, id de la sessio
     * @param string $screen en base64, texto o nombre de la pantalla que se registrara
     * @return json string , respuesta con las variables o el mensaje de error.
     *
     */
    public function index_post() {
        print_r($this->post());
        $idApp = $this->post('idapp');
        $idDownload = $this->post('iddownload');
        $versionEthAppsSystem = $this->post('versionEthAppsSystem');
        $idVersion = $this->post('idversion');
        $idSession = $this->post('idsession');
        $screen = $this->post('screen');

        $screen = str_replace("|", "", $screen);
        $idVersion = str_replace("|", "", $idVersion);
        $idDownload = str_replace("|", "", $idDownload);
        $idApp = str_replace("|", "", $idApp);

        if ($this->verifySession($idDownload, $idApp, $idSession)) {
            $this->report($versionEthAppsSystem, $idVersion, $idDownload, $idApp, $idSession, $screen);
            $this->response([
                'status' => TRUE,
                'message' => "REPORTED"
                    ], REST_Controller::HTTP_ACCEPTED);
            //$this->prepareAndResponse("200", "Success");
        } else {
            //$this->prepareAndResponse("403", "Fail");
            $this->response([
                'status' => FALSE,
                'message' => "no inicio sesion"
                    ], REST_Controller::HTTP_FORBIDDEN);
        }
    }

    /**
     * Esta funcion realiza un reporte de un evento en la base de datos.
     * @param string $versionEthAppsSystem version la api.
     * @param string $idversion, version de la aplicacion
     * @param string $idDownload en base64, identificador de la descarga.
     * @param string $idApp en base64, identificador de la aplicacion.
     * @param string $idSession en base64, id de la session
     * @param string $screen en base64, pantalla  a registrar.	
     * @return void
     */
    private function report($versionEthAppsSystem, $idVersion, $idDownload, $idApp, $idSession, $screen) {
        $this->load->model(ETHVERSION . 'screen');
        $this->screen->reportScreen($versionEthAppsSystem, $idVersion, $idDownload, $idApp, $idSession, $screen);
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
