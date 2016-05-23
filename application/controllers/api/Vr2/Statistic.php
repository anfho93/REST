<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Statistic
 *
 * @author anfho
 */
defined('BASEPATH') OR exit('No direct script access allowed');
require_once 'EthRESTController.php';

class Statistic extends EthRESTController {

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
        } else {
            $this->response([
                'status' => FALSE,
                'message' => "No implementado aun"
                    ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Este metodo carga datos comunes para todas las estadisticas.      * 
     * @param Object $modelo ActiveRecord Model que se usa para agregar condiciones de segmentacion
     */
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

    /**
     * Permite determina la cantidad de usuariso nuevos de una aplicacion
     * 
     */
    private function newUsers() {
        $this->load->model(ETHVERSION . "appstatistics");
        $this->loadData($this->appstatistics);
        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->appstatistics->getNewUsers($this->idApp, $this->initialDate, $this->finalDate);
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result , "title"=>"New Users"));            
            $this->response(['status' => true, 'message' => "Success", "result" => $result, "title" => "New Users"], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => "Fail", "result" => array()], REST_Controller::HTTP_CONFLICT);
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));            
        }
    }

    /**
     * Permite saber que usuarios usaron la aplicacion durante un rango de tiempo determinado
     */
    private function users() {
        $this->load->model(ETHVERSION . "appstatistics");
        $this->loadData($this->appstatistics);
        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->appstatistics->getUsers($this->idApp, $this->initialDate, $this->finalDate);
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result, "title"=>"Users"  ));
        } else {
            // $this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));            
        }
    }

    public function index_get() {
        $segmento = $this->uri->segment(5);
        if ($this->_pre_get() != null) {
            switch ($this->_pre_get()) {
                case "newusers":
                    //tener en cuenta  SO
                    if ($segmento === "os") {
                        
                    } else {
                        $this->newUsers();
                    }
                    break;
                case "users":
                    $this->users();
                    break;
                case "sessions":
                    //TODO tener en cuenta
                    //idioma, OS, MB
                    $this->sessions();
                    break;
                case "screenviews":
                    $this->screenviews();
                    break;
                case "interactions":
                    $this->interactions();
                    break;
                case "screen":
                    $this->getScreen();
                    break;
                case "screenreport":
                    $this->getScreensReport();
                    break;
                case "screendata":
                    $this->getScreensData();
                    break;
                case "userinteractions":
                    $this->getUserInteraction();
                    break;
                case "activeusers":
                    //tener en cuenta  OS; MB, Lang
                    $this->getActiveUsers();
                    break;
                case "sampledata":
                    $this->getGeneralSampleData();
                    break;
                case "totalusers":
                    $this->getTotalUsers();
                    break;
                case "newuserdata":
                    //tener en cuenta SAMPLE, OS
                    $this->getNewUsersSampleData();
                    break;
                case "userretention":
                    $this->userRetention();
                    break;
                case "variables":
                    if ($segmento == "state") {
                        $this->getStateVariables();
                    } else if ($segmento == "statedata") {
                        $this->getStateVariableData();
                    }
                    //state, statedata
                    break;
                case "event":
                    switch ($segmento) {
                        case "categorytypes":
                            $this->categoryTypes();
                            break;
                        case "logcategorytype":
                            $this->logsFromCategoryTypes();
                            break;
                        case "categoriesdata":
                            $this->categoryDailyData();
                            break;
                        case "typedata":
                            $this->typeDailyData();
                            break;
                        case "logdata":
                            $this->logDailyData();
                            break;
                        case "categories":
                            $this->eventCategories();
                            break;
                        case "types":
                            $this->eventTypes();
                            break;
                        case "logs":
                            $this->eventLogs();
                            break;
                        default :
                            $this->eventsDailyData();
                            break;
                    }
                    // categories, types, logs
                    //categorytypes, logscategorytypes
                    break;
                case "dailydata":
                    //category, type, log , events
                    break;
            }
        } else {
            $this->index();
        }
    }

    /**
     * Obtiene las categorias de los evnetos registrados en la app.
     */
    private function eventCategories() {
        $this->load->model(ETHVERSION . "Eventstatistics", "evtStats");
        $this->loadData($this->evtStats);
        if ($this->validateData($this->useremail, $this->idApp)) {
            //$variable = $this->get('variable');
            $result = $this->evtStats->getEventsCategories($this->idApp, $this->initialDate, $this->finalDate);
            $titles = array("Categories", "# Events");
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                "titles" => $titles
                    ], REST_Controller::HTTP_OK);
        } else {
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));  
            $this->response([
                'status' => false,
                'message' => "fail",
                "result" => array(),
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Permite obtener la cantidad de eventos de un nombre especifico.
     */
    private function logDailyData() {
        $this->load->model(ETHVERSION . "Eventstatistics", "evtStats");
        $this->loadData($this->evtStats);
        if ($this->validateData($this->useremail, $this->idApp)) {
            $log = $this->get('log');
            $result = $this->evtStats->getLogDailyData($this->idApp, $this->initialDate, $this->finalDate, $log);
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result));
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                    ], REST_Controller::HTTP_OK);
        } else {
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));  
            $this->response([
                'status' => false,
                'message' => "fail",
                "result" => array(),
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Obtiene los tipos de los evnetos registrados en la app.
     */
    private function eventTypes(){
        $this->load->model(ETHVERSION."Eventstatistics","evtStats");
        $this->loadData($this->evtStats);
        if($this->validateData($this->useremail, $this->idApp )){
             $result =  $this->evtStats->getTypes($this->idApp, $this->initialDate, $this->finalDate);
             $titles = array("Types", "# Events");
             //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result , "titles" => $titles));
             $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                 "titles" => $titles
                    ], REST_Controller::HTTP_OK);
        }else{
           // $this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));  
            $this->response([
                'status' => false,
                'message' => "fail",
                "result" => array(),
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }
     /**
     * Obtiene los nombres  de los evnetos registrados en la app.
     */
    private function eventLogs(){
        $this->load->model(ETHVERSION."Eventstatistics","evtStats");
        $this->loadData($this->evtStats);
        if($this->validateData($this->useremail, $this->idApp )){
             $result =  $this->evtStats->getLogs($this->idApp, $this->initialDate, $this->finalDate);
             $titles = array("Logs", "# Events");
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                 "titles" => $titles
                    ], REST_Controller::HTTP_OK);
        }else{
           // $this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));  
            $this->response([
                'status' => false,
                'message' => "fail",
                "result" => array(),
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }
    
    /**
     *  Permite obtener la cantidad de eventos diarios basados en una categoria y un tipo respectivamente.
     */
    private function typeDailyData() {
        $this->load->model(ETHVERSION . "Eventstatistics", "evtStats");
        $this->loadData($this->evtStats);
        if ($this->validateData($this->useremail, $this->idApp)) {
            $type = $this->get('type');
            $category = $this->get('type');
            if ($category != null) {
                $result = $this->evtStats->getTypeDailyData($this->idApp, $this->initialDate, $this->finalDate, $type, $category);
            } else {
                $result = $this->evtStats->getTypeDailyData($this->idApp, $this->initialDate, $this->finalDate, $type);
            }
            // $this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result));
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                    ], REST_Controller::HTTP_OK);
        } else {
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));  
            $this->response([
                'status' => false,
                'message' => "fail",
                "result" => array(),
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Permite obtener la cantidad de eventos diarios basados en una categoria.
     */
    private function categoryDailyData() {
        $this->load->model(ETHVERSION . "Eventstatistics", "evtStats");
        $this->loadData($this->evtStats);
        if ($this->validateData($this->useremail, $this->idApp)) {
            $category = $this->get('category');
            $result = $this->evtStats->getCategoryDailyData($this->idApp, $this->initialDate, $this->finalDate, $category);
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result));
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                    ], REST_Controller::HTTP_OK);
        } else {
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));  
            $this->response([
                'status' => false,
                'message' => "Fail",
                "result" => array(),
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Envia datos de los eventos basado en una cateogoria y un tipo
     */
    private function logsFromCategoryTypes() {
        $this->load->model(ETHVERSION . "Eventstatistics", "evtStats");
        $this->loadData($this->evtStats);
        if ($this->validateData($this->useremail, $this->idApp)) {
            $category = $this->get('category');
            $type = $this->get('type');
            $result = $this->evtStats->getLogsFromCategoryTypes($this->idApp, $this->initialDate, $this->finalDate, $category, $type);
            $titles = array("Logs", "# Events");
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result, "titles"=>$titles));
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                "titles" => $titles
                    ], REST_Controller::HTTP_OK);
        } else {
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));  
            $this->response([
                'status' => false,
                'message' => "false",
                "result" => array(),
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Permite obtener los tipos, de una categoria de eventos de una aplicacion.
     */
    private function categoryTypes() {
        $this->load->model(ETHVERSION . "Eventstatistics", "evtStats");
        $this->loadData($this->evtStats);
        if ($this->validateData($this->useremail, $this->idApp)) {
            $category = $this->get('category');
            $result = $this->evtStats->getCategoryTypes($this->idApp, $this->initialDate, $this->finalDate, $category);
            $titles = array("Types", "# Events");
            // $this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result, "titles"=>$titles));
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                "titles" => $titles
                    ], REST_Controller::HTTP_OK);
        } else {
            // $this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));  
            $this->response([
                'status' => false,
                'message' => "fail",
                "result" => array(),
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Permite conocer el comportamiento de los eventos de manera diaria en una aplicacion.
     */
    private function eventsDailyData() {
        $this->load->model(ETHVERSION . "Eventstatistics", "evtStats");
        $this->loadData($this->evtStats);
        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->evtStats->getEventsDailyData($this->idApp, $this->initialDate, $this->finalDate);
            // $this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result));
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                    ], REST_Controller::HTTP_OK);
        } else {
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));  
            $this->response([
                'status' => false,
                'message' => "fail",
                "result" => array(),
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Obtiene el estado actual de una variable de estado determinada de una aplicacion.
     */
    private function getStateVariableData() {
        $this->load->model(ETHVERSION . "Variablestatistics", "varStats");
        $this->loadData($this->varStats);
        $result = null;

        if ($this->validateData($this->useremail, $this->idApp)) {
            $variable = $this->get('variable');
            $result = $this->varStats->getStateVariableData($this->idApp, $variable);
            $titles = array("Name", "Value", "Users");
            //  $this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result , "titles" => $titles));
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                "titles" => $titles
                    ], REST_Controller::HTTP_OK);
        } else {
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));  
            $this->response([
                'status' => false,
                'message' => "fail",
                "result" => array(),
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Permite obtener todas la variales de estado
     */
    private function getStateVariables() {
        $this->load->model(ETHVERSION . "Variablestatistics", "varStats");
        $this->loadData($this->varStats);
        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->varStats->getStateVariables($this->idApp);
            $titles = array("Variable ID", "Name");
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result , "titles" => $titles));
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                "titles" => $titles
                    ], REST_Controller::HTTP_OK);
        } else {
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));  
            $this->response([
                'status' => false,
                'message' => "Fail",
                "result" => array()
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Calcula la retencion de usuarios en la aplicacion
     */
    private function userRetention() {
        $this->load->model(ETHVERSION . "Userstatistics", "userStats");
        $this->loadData($this->userStats);
        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $dayRet = $this->get('days');
            $result = $this->userStats->getUserRetention($this->idApp, $this->initialDate, $this->finalDate, $dayRet);
            $titles = array();
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result , "titles" => $titles));
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                "titles" => $titles
                    ], REST_Controller::HTTP_OK);
        } else {
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));  
            $this->response([
                'status' => false,
                'message' => "fail",
                "result" => array(),
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Obtiene un resumen de datos generales sobre usuarios nuevos de una aplicacion
     */
    private function getNewUsersSampleData() {
        $this->load->model(ETHVERSION . "appstatistics");
        $this->loadData($this->appstatistics);
        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $sesiones = $this->appstatistics->getTotalSessions($this->idApp, $this->initialDate, $this->finalDate);
            $usuarios = $this->appstatistics->getTotalUsers($this->idApp, $this->initialDate, $this->finalDate);
            $newusers = $this->appstatistics->getTotalNewUsers($this->idApp, $this->initialDate, $this->finalDate);
            $sampledata = array("sessions" => $sesiones, "users" => $usuarios, "nusers" => $newusers);
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$sampledata )); 
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $sampledata,
                    ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => false,
                'message' => "fail",
                "result" => array(),
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Obtiene un resumen de datos generales sobre usuarios, sesion y pantallas de una aplicacion
     */
    private function getGeneralSampleData() {
        $this->load->model(ETHVERSION . "appstatistics");
        $this->loadData($this->appstatistics);
        if ($this->validateData($this->useremail, $this->idApp)) {
            $sesiones = $this->appstatistics->getTotalSessions($this->idApp, $this->initialDate, $this->finalDate);
            $usuarios = $this->appstatistics->getTotalUsers($this->idApp, $this->initialDate, $this->finalDate);
            $screens = $this->appstatistics->countScreens($this->idApp, $this->initialDate, $this->finalDate);
            $sampledata = array("sessions" => $sesiones, "users" => $usuarios, "screens" => $screens);
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$sampledata ));            
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $usuarios,
                "title" => "Active Users"
                    ], REST_Controller::HTTP_OK);
        } else {
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));  
            $this->response([
                'status' => false,
                'message' => "fail",
                "result" => array(),
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Permite obtener datos concretossobre los nuevos usuarios durante un rango de tiempo
     */
    private function getTotalUsers() {
        $this->load->model(ETHVERSION . "appstatistics");
        $this->loadData($this->appstatistics);

        if ($this->validateData($this->useremail, $this->idApp)) {
            $usuarios = $this->appstatistics->getTotalUsers($this->idApp, $this->initialDate, $this->finalDate);
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result, "title" => "Active Users" ));            
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $usuarios,
                "title" => "Active Users"
                    ], REST_Controller::HTTP_OK);
        } else {
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));            
            $this->response([
                'status' => FALSE,
                'message' => "Fail",
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Permite determinar un conjunto de datos generales sobre los usuarios actuales de la aplciacion
     */
    private function getActiveUsers() {
        $this->load->model(ETHVERSION . "appstatistics");
        $this->loadData($this->appstatistics);
        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->appstatistics->getActiveUsers($this->idApp, $this->initialDate, $this->finalDate);
            //$sesiones =  $this->appstatistics->getTotalSessions($this->idApp, $this->initialDate, $this->finalDate);
            //$usuarios = $this->appstatistics->getTotalUsers($this->idApp, $this->initialDate, $this->finalDate);
            // $screens = $this->appstatistics->countScreens($this->idApp, $this->initialDate, $this->finalDate);
            // $sampledata = array("sessions"=>$sesiones, "users"=>$usuarios, "screens"=>$screens);
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result, "title" => "Active Users" ));            
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                "title" => "Active Users"
                    ], REST_Controller::HTTP_OK);
        } else {
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));            
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => array()
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Mide la la interaccion de usuarios por session con las pantallas
     */
    public function getUserInteraction() {
        $this->load->model(ETHVERSION . "appstatistics");
        $this->loadData($this->appstatistics);

        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->appstatistics->getUserInteraction($this->idApp, $this->initialDate, $this->finalDate);
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result  ));            
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                    ], REST_Controller::HTTP_OK);
        } else {
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));            
            $this->response([
                'status' => false,
                'message' => "fail",
                "result" => array(),
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Permite obtener informacion especifica de una pantallas registrada
     */
    private function getScreensData() {
        $this->load->model(ETHVERSION . "screenstatistics");
        $this->loadData($this->screenstatistics);
        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $screename = $this->get('screenname');
            if ($screename != null && $screename != "") {
                $this->screenstatistics->addCondition($this->segment);
                $result = $this->screenstatistics->getSpecificScreenData($this->idApp, $this->initialDate, $this->finalDate, $screename);
            } else {
                $result = $this->screenstatistics->getScreensData($this->idApp, $this->initialDate, $this->finalDate);
            }
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result));            
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                    ], REST_Controller::HTTP_OK);
        } else {
            // datos invalidos
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));            
            $this->response([
                'status' => FALSE,
                'message' => "fail",
                "result" => array()
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Obtiene un reporte general sobre las pantallas
     */
    private function getScreensReport() {
        $this->load->model(ETHVERSION . "appstatistics");
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
            // $this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result , "titles"=>$titulo ));            
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                "titles" => $titulo
                    ], REST_Controller::HTTP_OK);
        } else {
            // datos invalidos
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));
            $this->response([
                'status' => FALSE,
                'message' => "Fail",
                "result" => array()
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Permite ver datos generales de las pantallas y la cantidad de visualizaciones.
     */
    private function getScreen() {
        $this->load->model(ETHVERSION . "appstatistics");
        $this->loadData($this->appstatistics);

        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->appstatistics->getGeneralScreensData($this->idApp, $this->initialDate, $this->finalDate);
            $titulo = array("Screen", "Visualization");
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result , "titles"=>$titulo ));            
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                "titles" => $titulo
                    ], REST_Controller::HTTP_OK);
        } else {
            // datos invalidos
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));            
            $this->response([
                'status' => FALSE,
                'message' => "Fail",
                "result" => array()
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Retorna una metrica con la cantidad de pantallas por sesion en una app.
     */
    private function interactions() {
        $this->load->model(ETHVERSION . "screenstatistics");
        $this->loadData($this->screenstatistics);
        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->screenstatistics->getInteraction($this->idApp, $this->initialDate, $this->finalDate);
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result  ));            
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                    ], REST_Controller::HTTP_OK);
        } else {
            // datos invalidos
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));            
            $this->response([
                'status' => FALSE,
                'message' => "Fail",
                "result" => array()
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Permite ver cuantos usuarios vieron pantallas en la aplicacion.
     */
    private function screenViews() {
        $this->load->model(ETHVERSION . "screenstatistics");
        $this->loadData($this->screenstatistics);
        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->screenstatistics->getScreenViews($this->idApp, $this->initialDate, $this->finalDate);
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result  ));            
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                    ], REST_Controller::HTTP_OK);
        } else {
            // datos invalidos
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));            
            $this->response([
                'status' => FALSE,
                'message' => "Fail",
                "result" => array()
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Permite conocer datos generales sobre las sesiones de una aplicacion.
     */
    private function sessions() {
        $this->load->model(ETHVERSION . "appstatistics");
        $this->loadData($this->appstatistics);
        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->appstatistics->getGeneralSessionData($this->idApp, $this->initialDate, $this->finalDate);
            //$this->prepareAndResponse("200","Success",array("success"=>"true", "result"=>$result, "title"=>"Sessions"  ));
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result,
                "title" => "Sessions"
                    ], REST_Controller::HTTP_OK);
        } else {
            //$this->prepareAndResponse("200","Fail",array("success"=>"false", "result"=>array() ));            
            $this->response([
                'status' => FALSE,
                'message' => "Fail",
                "result" => array()
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Obtiene estadisticas basicas de una aplicacion.
     */
    private function index() {
        $this->load->model(ETHVERSION . "appstatistics");
        $this->loadData($this->appstatistics);
        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->appstatistics->getStatistics($this->idApp, $this->initialDate, $this->finalDate);
            $result = json_encode($result);
            // $this->prepareAndResponse("200", "Success", array("success" => "true", "result" => $result));
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result
                    ], REST_Controller::HTTP_OK);
        } else {
            // datos invalidos
            //$this->prepareAndResponse("200", "Fail", array("success" => "false", "result" => array()));
            $this->response([
                'status' => FALSE,
                'message' => "Fail",
                "result" => array()
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }
    
    /**
   * Valida que los datos del usuario sean correctos
   * @param type $email, correo del usuario
   * @param type $idApp, id de la aplicacion registrada
   * @return boolean confirmacion de que el usuario y la app son correctos.
   */
    private function validateData($email, $idApp){
            $this->load->model(ETHVERSION."user");		
            return $this->user->userHaveApp($email, $idApp);
    }

}
