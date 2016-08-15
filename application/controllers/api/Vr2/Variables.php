<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Variables
 *
 * @author andres
 */
defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
//require APPPATH . '/libraries/REST_Controller.php';
require 'EthRESTController.php';

class Variables extends EthRESTController {

    public function __construct() {
        parent::__construct();
        $this->load->model(ETHVERSION . "Variable", "variable");
        $this->load->model(ETHVERSION . "App", "app");
        $this->load->model(ETHVERSION . "User", "user");
        $methodname = strtolower("index_" . $this->request->method);
        if (method_exists($this, $methodname)) {
            $this->$methodname();
        } else {
            $this->response([
                'status' => FALSE,
                'message' => "ERROR"
                    ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function index_get() {
        if ($this->_pre_get() != null) {
            switch ($this->_pre_get()) {
                case "ab":
                    $this->getData();
                    break;
                case "abvalues":
                    $this->getABVariableValues();
                    break;
            }
        } else {
            $this->getVariables();
        }
    }

    /**
     * Funcion principal del servicio, aqui se se reciben los parametros via peticion http.
     * @param string $idapp en base64, identificador de la aplicacion.
     * @param string $idDownload en base64, identificador de la descarga.
     * @param string $versionEthAppsSystem en base64, Numero de la version de la API
     * @param string $idVersion en base64, versionn de la aplicacion que obtiene las variables.
     * @param string $varName en base64, nombre de la variable a obtener.
     * @param string $withDetails en base64, obtiene la variable con detalles.
     * @return json string|, respuesta con las variables o el mensaje de error.
     *
     */
    private function getVariables() {
        $idApp = $this->get('idapp');
        $idDownload = $this->get('iddownload');
        //$idSession = $this->getUrlData('idSession','base64');
        $versionEthAppsSystem = $this->get('versionEthAppsSystem');
        $idVersion = $this->get('idversion');
        $varName = $this->get('varname');
        $withDetails = $this->get('details');
        $abvars = null;
        //if($this->validateDataAndApp($idApp)) {
        //if($this->verifySession($idDownload,$idApp, $idSession)){

        $this->load->model(ETHVERSION . 'variable');
        $arrVars = array();

        //If the attribute $varName is empty, it's because the user is trying to 
        //get all the variables for this game
        if ($varName == "") {
            $arrVars = $this->variable->getVariables($idApp, $withDetails);
        } else {
            $arrVars = $this->variable->getVariable($idApp, $varName);
            if ($arrVars != null && $arrVars["class"] == "A/B") {
                $abvars = $this->variable->getABVariable($arrVars["id_variable"]);
            }
        }

        if (count($arrVars) == 0) {
            if ($varName == "") {
               // $this->prepareAndResponse("201", "This app doesn't have variables");
                  $this->response([
                    'status' => FALSE,
                     'message' => "This app doesn't have variables"
                    ], REST_Controller::HTTP_ACCEPTED);
            } else {
                   $this->response([
                    'status' => FALSE,
                     'message' => "This var doesn't exist"
                    ], REST_Controller::HTTP_ACCEPTED);
            }
        } else {
            $specificBehaviourPath = APPPATH . "controllers/apps/" . $idApp;

            if (file_exists($specificBehaviourPath)) {
                $this->loadController("apps/" . $idApp . "/appSpecificBehaviour", "appSpecificBehaviour");
                $arrVars = $this->appSpecificBehaviour->convertVariables($idApp, $idDownload, $versionEthAppsSystem, $idVersion, $arrVars);
            }

            $arrResponseVars = array();
            $arrResponseVars["vars"] = $arrVars;
            /* if($abvars!=null)
              {
              $arrResponseVars["varcond"] = $abvars;
              } */
           // $this->prepareAndResponse("200", "Success", $arrResponseVars);
            $this->response([
                    'status' => true,
                     'result' => $arrResponseVars
                    ], REST_Controller::HTTP_ACCEPTED);
        }
        //}
        //else{
        //	$this->prepareAndResponse("501","no autorizado",array());
        //}
    }

    /**
     * Esta funcion permite obtener los datos de una variable AB
     * esta respuesta la indica en formato JSON
     */
    private function getData() {
        //print_r($this->get());
        $idApp = $this->get('idapp');
        //$versionEthAppsSystem = $this->get('versionEthAppsSystem');
        //$idVersion = $this->get('idversion');
        $varName = $this->get('varname');
        $arrVars = $this->variable->getVariable($idApp, $varName);
        //print_r($arrVars);
        if (!empty($arrVars) && $arrVars["class"] == "AB") {
            $abvars = $this->variable->getABVariable($arrVars["id_variable"]);
            //$this->prepareAndResponse("200", "Success", array("cond" => $abvars));            
            $this->response(['status' => TRUE, "message" => "Success", 'cond' => $abvars], REST_Controller::HTTP_ACCEPTED);
        } else {
            //$this->prepareAndResponse("200", "Fail", array("message" => "NOT AB VARIABLE"));
            $this->response(['status' => TRUE, "message" => "NOT AB VARIABLE"], REST_Controller::HTTP_ACCEPTED);
        }
    }

    private function registerVar() {
        $idApp = $this->post('idapp');
        $versionEthAppsSystem = $this->post('versionEthAppsSystem');
        $useremail = $this->post('useremail');
        $class = $this->post('class');
        $value = $this->post('value');
        $icon = $this->post('icon');
        $name = $this->post('name');
        $cond = null;
        switch ($class) {
            case "0":
                $class = "NORMAL";
                break;
            case "1":
                $class = "AB";
                $cond = $this->post('cond', 'base64');
                break;
            case "2":
                $class = "STATE";
                break;
            default :
                $class = "NORMAL";
                break;
        }
        if ($this->app->appExists($idApp) && $this->user->userHaveApp($useremail, $idApp)) {//validar el usuario y el app
            $result = $this->variable->registerVariable($name, $value, $class, $icon, $idApp, $cond);
            if ($result != false) {
                //$this->prepareAndResponse("200", "Success", array("ItemRegistered" => "true"));
                $this->response(['status' => TRUE, 'message' => "ItemRegistered"], REST_Controller::HTTP_ACCEPTED);
            } else {
                //$this->prepareAndResponse("200", "Success", array("Already registred" => "false"));
                $this->response(['status' => FALSE, 'message' => "Already registred"], REST_Controller::HTTP_BAD_REQUEST);
            }
        } else {
            //$this->prepareAndResponse("200", "The dataset doesn't match", array("ItemRegistered" => "false"));
            $this->response(['status' => FALSE, 'message' => "The dataset doesn't match"], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    private function registerABVar() {
        //$this->load->model(ETHVERSION . 'variable');
       // print_r($this->post());
        $versionEthAppsSystem = $this->post('versionEthAppsSystem');
        $idVariable = $this->post('idvar');
        $condName = $this->post('varname');
        $useremail = $this->post('useremail');
        $condsJSON = json_decode($this->post('conditions'));
        $valuesJSON = json_decode($this->post('values'));
        $conds = $this->post('conditions');
        $values = $this->post('values');
        if ($this->validate($useremail, $condName, $idVariable) && $condsJSON != null && $valuesJSON != null) {
            $result = $this->variable->registerCond($idVariable, $conds, $values, $condName);
            if ($result == false) {
                //$this->prepareAndResponse("200", "Fail", array("message" => "Nombre de la variable duplicado"));
                $this->response(['status' => FALSE, 'message' => "Nombre de la variable duplicado"], REST_Controller::HTTP_BAD_REQUEST);
            } else {
                //$this->prepareAndResponse("200", "Success", array("result" => $result));
                $this->response(['status' => true, 'message' => "Success", "result" => $result], REST_Controller::HTTP_ACCEPTED);
            }
        } else {
            //$this->prepareAndResponse("200", "Fail", array("message" => "NOT AB VARIABLE"));
            $this->response(['status' => FALSE, 'Fail' => "NOT AB VARIABLE"], REST_Controller::HTTP_CONFLICT);
        }
    }

    public function index_post() {
        if ($this->_pre_get() != null) {
            switch ($this->_pre_get()) {
                case "ab":
                    
                    $this->registerABVar();
                    break;

                default:
                    $this->registerVar();
                    break;
            }
        } else {
            $this->registerVar();
        }
    }

    /**
     * Funcion que permite realizar el registro de una aplicacion.
     * esta funcion recibe los elementos via post o get en base64, son decodificados.
     * @param string base64, $name_app nombre de la aplicacion,enviado como parametro via POST o GET
     * @param string base64, $descripcion descripcion de la aplicacion,enviado como parametro via POST o GET
     * @param string base64, $type tipo de la aplicacion,enviado como parametro via POST o GET
     * @param string base64, $user_email correo electronico de quien registra la aplicacion, enviado como parametro via POST o GET
     * @param string base64, $platforms nombre de la aplicacion,enviado como parametro via POST o GET
     *
     * @return void | Json , respuesta del servicio wen.
     *
     */
    private function analiticVariable() {

        $this->load->model(ETHVERSION . 'statevariable', "statevariable");
        $name_variable = $this->put('name');
        $value = $this->put('value');
        $date = time();
        $id_download = $this->put('idDownload');
        $id_app = $this->put('idApp');
        //nuevo elemento id de la session
        $session = $this->put('idSession');
        if ($this->verifySession($id_download, $id_app, $session)) {
            //echo "entre";
            $this->statevariable->createVariable($id_download, $id_app, $name_variable, $value);
        } else {
            //echo "la session no pudo ser verificada";
            $this->response(['status' => false, 'message' => "Session not verified", "Variable Updated" => "false"], REST_Controller::HTTP_FORBIDDEN);
        }
    }

    public function index_put() {
        if ($this->_pre_get() != null) {
            switch ($this->_pre_get()) {
                case "ab":
                    
                    $this->modifyABVariable();
                    break;
                case "analitic":
                    $this->analiticVariable();
                    break;
                case "abdef":
                    
                    $this->modifyDefaultValues();
                    break;
            }
        } else {
            $this->modifyVariable();
        }
    }

    private function modifyVariable() {
        $idApp = $this->put('idapp');
        $versionEthAppsSystem = $this->put('versionEthAppsSystem');
        $useremail = $this->put('useremail');
        $idVar = $this->put('idvar');
        $value = $this->put('value');
        //print_r($value);
        $cond = null;
        if ($this->app->appExists($idApp) && $this->user->userHaveApp($useremail, $idApp)) {//validar el usuario y el app
            $variable = $this->variable->getVariableByID($idApp, $idVar);
            if ($variable != null) {
                $class = $variable->class;
                if ($class == "A/B") {
                    $cond = $this->put('cond');
                }
                $result = $this->variable->modifyVariable($idApp, $idVar, $value);
                if ($result != false) {
                    $this->response(['status' => true, 'message' => "Success", "Variable Updated" => "true"], REST_Controller::HTTP_ACCEPTED);
                } else {
                    $this->response(['status' => FALSE, 'message' => "1The dataset doesn't match", "Variable Updated" => "false"], REST_Controller::HTTP_CONFLICT);
                } 
            } else {
                $this->response(['status' => false, 'message' => "2The dataset doesn't match", "Variable Updated" => "false"], REST_Controller::HTTP_CONFLICT);
            }
        } else {
            $this->response(['status' => false, 'message' => "3The dataset doesn't match", "Variable Updated" => "false"], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Permite modificar los valores de una condicion de una variable AB
     * por defecto.
     */
    public function modifyDefaultValues() {
        $idVariable = $this->put('idvariable');
        $idApp = $this->put('idapp');
        $value = $this->put('values');
        //print_r($value);
        if ($this->variable->modifyVariable($idApp, $idVariable, $value)) {
            // $this->prepareAndResponse("200","Success",array("updated" => "true" ));            
            $this->response([
                'status' => TRUE,
                'message' => "Success"
                    ], REST_Controller::HTTP_ACCEPTED);
        } else {
            //$this->prepareAndResponse("200","Failed",array("updated" => "false" ));            
            $this->response([
                'status' => false,
                'message' => "Failed"
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    private function modifyABVariable() {        
        $idApp = $this->put('idapp');
        $versionEthAppsSystem = $this->put('versionEthAppsSystem');
        $useremail = $this->put('useremail');
        $idabVar = $this->put('idabvar');
        $idVar = $this->put('idvar');
        if($this->app->appExists($idApp) && $this->user->userHaveApp($useremail, $idApp))//validar el usuario y el app
        {            
            $variable = $this->variable->obtenerVariable($idVar);          
            if ($variable != null) {
                $class = $variable->class;
                if ($class == "AB") {
                    $conds = $this->put('conds');
                    $values = $this->put('values');
                    $result = $this->variable->modifyABVariable($idVar, $idabVar, $conds, $values);
                    if ($result == 1) {
                        $this->response(['status' => true, 'message' => "Success", "Variable Updated" => "true"], REST_Controller::HTTP_ACCEPTED);
                    } else {
                        $this->response(['status' => false, 'message' => "Success", "Variable Updated" => "FALSE"], REST_Controller::HTTP_BAD_REQUEST);
                    }
                } else {
                   $this->response(['status' => false, 'message' => "Variable not AB", "Variable Updated" => "FALSE"], REST_Controller::HTTP_BAD_REQUEST);
                }
            } else {
                $this->response(['status' => false, 'message' => "The dataset doesn't match", "Variable Updated" => "FALSE"], REST_Controller::HTTP_BAD_REQUEST);
            }
        }  else {
          $this->response(['status' => false, 'message' => "The dataset doesn't match", "Variable Updated" => "FALSE"], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function index_delete() {
        if ($this->_pre_get() != null) {
            switch ($this->_pre_get()) {
                case "abcond":
                    $this->deletecond();
                    break;
            }
        } else {
            $this->deleteVar();
        }
    }

    /**
     * Esta funcion recibe parametros via POST para la 
     * eliminacion de una condicion de variable de tipo A/B
     */
    private function deletecond() {
        $versionEthAppsSystem = $this->query('versionEthAppsSystem');
        $useremail = $this->query('useremail');
        $idabVar = $this->query('idabvar');
        $idVar = $this->query('idvar');
        $res = $this->variable->deleteABCond($idVar, $idabVar);
        if ($res == 1) {
            // $this->prepareAndResponse("200","Variable deleted",array("deleted"=>"true"));
            $this->response(['status' => true, 'message' => "Variable deleted"], REST_Controller::HTTP_ACCEPTED);
        } else {
            //$this->prepareAndResponse("200","The dataset doesn't match",array("deleted"=>"false"));            
            $this->response(['status' => FALSE, 'message' => "The dataset doesn't match"], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    private function deleteVar() {
        //print_r($this->query());
        $idApp = $this->query('idapp');
        $versionEthAppsSystem = $this->query('versionEthAppsSystem');
        $useremail = $this->query('useremail');
        $id_variable = $this->query('idvariable');
        if ($this->app->appExists($idApp) && $this->user->userHaveApp($useremail, $idApp)) {
            $result = $this->variable->delete($id_variable, $idApp);
            if ($result) {
                //$this->prepareAndResponse("200", "Success", array("Itemdeleted" => "true"));
                $this->response(['status' => TRUE, 'message' => "Item deleted"], REST_Controller::HTTP_ACCEPTED);
            } else {
                //$this->prepareAndResponse("200", "Success", array("Itemdeleted" => "false"));
                $this->response(['status' => TRUE, 'message' => "Item not deleted"], REST_Controller::HTTP_BAD_REQUEST);
            }
        } else {
            //$this->prepareAndResponse("200", "The dataset doesn't match", array("Itemdeleted" => "false"));
            $this->response(['status' => FALSE, 'message' => "The dataset doesn't match"], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Permite obtener los valores de las diferentes variables A/B
     * que pueda tener una aplicacion y basado en un ID de descarga
     * se puede determinar que valor le corresponde a dicho disposivo.
     */
    public function getABVariableValues() {
        $this->load->model(ETHVERSION . "download", "download");
        //TODO se debe verificar la sessión para esto.    
        $idapp = $this->get('idapp');
        $idDownload = $this->get('iddownload');
        $idDevice = $this->get('iddevice');
        $model = $this->get('model');
        $name = $this->get('name');
        $platformName = $this->get('platformname');
        $platformVersion = $this->get('platformversion');
        $ip = $_SERVER['REMOTE_ADDR'];
        $idApp = $this->get('idapp');
        $additionalInfo = $this->get('additionalinfo');

        if ($platformName == '' || $platformName == null) {
            $platformName = "<unknown>";
        }
        $dataDownload = array("iddevice" => $idDevice, "idApp" => $idApp, "id_download" => $idDownload, "iddevice" => $idDevice,
            "model" => $model, "name" => $name, "platformversion" => $platformVersion,
            "platformname" => $platformName, "id_download" => $idDownload, "ipdownload" => $ip);


        $variable = explode(";", $additionalInfo);

        foreach ($variable as $value) {
            $var = explode(":", $value);
            if (count($var) == 2) {
                if ($var[1] == "False")
                    $dataDownload[$var[0]] = false;
                else
                if ($var[1] == "True") {
                    $dataDownload[$var[0]] = true;
                } else if (is_numeric($var[1])) {
                    $dataDownload[$var[0]] = (int) $var[1];
                } else
                    $dataDownload[$var[0]] = $var[1];
            }
        }

        $this->load->model(ETHVERSION . "variable", "variable");
        $variables = $this->variable->getVariables($idapp, true);
        $var = $this->processDownload($dataDownload, $variables);

        // $this->prepareAndResponse("200", "Success", array("success" => "true", "vars" => $var));
        $this->response(['status' => TRUE, "success" => "true", "vars" => $var], REST_Controller::HTTP_BAD_REQUEST);
    }

    private function validateModule($factor1, $factor2, $expectedValue) {
        if (($factor1 === null || $factor2 == null) && (!is_int($factor1 && !is_int($factor2)))) {
            return false;
        } else {
            return $factor1 % $factor2 === $expectedValue;
        }
    }

    /**
     * Este algoritmo inicialmente obtiene los datos de un dipositivo, 
     * luego verifica las variables que contiene dicha aplicacion cuales de estas 
     * son de tipo A/B, y basados en las condiciones que estas posean, se responde a dicho dispositivo
     * con el valor que le corresponde segun sea necesario.
     * @param array $dataDownload Datos sobre el dispositivo 
     * @param type $variables variables de la aplicación
     * @return array valores basados en las condiciones aplcicadas a las variables A/B
     */
    private function processDownload($dataDownload, $variables) {
        $this->load->model("Vr1.1/segment", "segment");
        $result = array();
        foreach ($variables as $row) {
            if ($row["class"] === "A/B") {
                $variablesCond = $this->variable->getABVariable($row["id_variable"]);
                foreach ($variablesCond as $abcond) {
                    $coincidencia = false;
                    $conditions = json_decode($abcond["conditions"], true);

                    foreach ($conditions as $cond) {
                        $segment = $this->segment->getSegment($cond["id_segmento"]);

                        if ($segment != null) {
                            $objSegment = json_decode($segment["valor"], true);

                            $coincidencia = $this->checkSegment($dataDownload, $objSegment);
                            if (!$coincidencia)
                                break;
                        }
                    }
                    if ($coincidencia) {
                        $row["value"] = $abcond["values"];
                        $str = $row["name"];
                        $result[] = array("$str" => $row["value"]);
                    }
                }
                if (empty($result)) {
                    $str = $row["name"];
                    $result = array("$str" => $row["value"]);
                }
            }
        }
        return $result;
    }

    private function checkSegment($datDownload, $objSegment) {
        $result = false;
        foreach ($objSegment as $elem) {
            if (array_key_exists("tipo", $elem)) {
                switch ($elem["tipo"]) {
                    case "matematica":
                        $result = $this->procesarCondicionMatematica($elem, $datDownload);
                        break;
                    case "siscompar":
                        $result = $this->procesarCondicionComparacion($elem, $datDownload);
                        break;
                }
                if (!$result) {
                    return $result;
                }
            } else {
                return false;
            }
        }
        return $result;
    }

    private function procesarCondicionComparacion($condicion, $dataDownload) {
        if (array_key_exists("operando", $condicion) &&
                array_key_exists("operador", $condicion) &&
                array_key_exists("value", $condicion) &&
                array_key_exists($condicion["operador"], $dataDownload)) {

            $operando = $condicion["operando"];
            $operador = $condicion["operador"]; //Elemento propio del dispositivo a ser comparado.
            $valor = $condicion["value"]; //valor esperado en esta condicion
            switch ($operando) {
                case "=":
                    return $operador === $valor;
                case "!=":
                    return $operador != $valor;
                case "pair":
                    if (array_key_exists("id_download", $dataDownload)) {
                        return ($dataDownload["id_download"] % 2) == 0;
                    }
                    break;
            }
        } else {
            return false;
        }
    }

    private function procesarCondicionMatematica($condicion, $dataDownload) {
        if (array_key_exists("operando", $condicion) &&
                array_key_exists("value", $condicion)) {

            $operando = $condicion["operando"]; //operacion matematica por ejm probabilidad
            //$operador = $condicion["operador"];//Elemento propio del dispositivo a ser comparado.
            $valor = $condicion["value"]; //valor esperado en esta condicion
            switch ($operando) {
                case "probability":
                    return rand(1, 100) <= $valor;
                default:
                    return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Funcion que valida si un usuario tiene una variable indicada
     * @param String $useremail correo electronico del dueño de la variable
     * @param String $varName nombre de la variable
     * @param String $idVariable identificador de la variable 
     * @return boolean si los datos coinciden con los de la base de datos
     */
    public function validate($useremail, $varName, $idVariable) {
        return true;
    }

}
