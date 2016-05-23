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

class Variables extends REST_Controller {

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
            
        }
    }

    /**
     * Esta funcion permite obtener los datos de una variable AB
     * esta respuesta la indica en formato JSON
     */
    private function getData() {
        $idApp = $this->get('idApp');
        $versionEthAppsSystem = $this->get('versionEthAppsSystem');
        $idVersion = $this->get('idVersion');
        $varName = $this->get('varName');
        $arrVars = $this->variable->getVariable($idApp, $varName);
        if (!empty($arrVars) && $arrVars["class"] == "A/B") {
            $abvars = $this->variable->getABVariable($arrVars["id_variable"]);
            //$this->prepareAndResponse("200", "Success", array("cond" => $abvars));            
            $this->response(['status' => TRUE, "message" => "Success", 'cond' => $abvars], REST_Controller::HTTP_OK);
        } else {
            //$this->prepareAndResponse("200", "Fail", array("message" => "NOT AB VARIABLE"));
            $this->response(['status' => TRUE, "message" => "NOT AB VARIABLE"], REST_Controller::HTTP_OK);
        }
    }

    private function registerVar() {
        $idApp = $this->post('idApp');
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
                $class = "A/B";
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
                $this->response(['status' => TRUE, 'message' => "ItemRegistered"], REST_Controller::HTTP_OK);
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
        $versionEthAppsSystem = $this->post('versionEthAppsSystem');
        $idVariable = $this->post('idVar');
        $condName = $this->post('varName');
        $useremail = $this->post('useremail');
        $condsJSON = json_decode($this->post('conditions'));
        $valuesJSON = json_decode($this->post('values'));
        $conds = $this->post('conditions', 'base64');
        $values = $this->post('values', 'base64');
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

    public function index_put() {
        if ($this->_pre_get() != null) {
            switch ($this->_pre_get()) {
                case "ab":
                    $this->modifyABVariable();
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
        $idApp = $this->put('idApp');
        $versionEthAppsSystem = $this->put('versionEthAppsSystem');
        $useremail = $this->put('useremail');
        $idVar = $this->put('idvar');
        $value = $this->put('value');
        $cond = null;
        if ($this->app->appExists($idApp) && $this->user->userHaveApp($useremail, $idApp)) {//validar el usuario y el app
            $variable = $this->variable->getVariableByID($idApp, $idVar);
            if ($variable != null) {
                $class = $variable->class;
                if ($class == "A/B") {
                    $cond = $this->getUrlData('cond', 'base64');
                }
                $result = $this->variable->modifyVariable($idApp, $idVar, $value);
                if ($result != false) {
                   // $this->prepareAndResponse("200", "Success", array("Variable Updated" => "true"));
                   $this->response(['status' => true, 'message' => "Success", "Variable Updated" => "true"], REST_Controller::HTTP_ACCEPTED);
                } else
                {
                    $this->response(['status' => FALSE, 'message' => "The dataset doesn't match", "Variable Updated" => "false"], REST_Controller::HTTP_CONFLICT);
                } // $this->prepareAndResponse("200", "The dataset doesn't match", array("Variable Updated" => "false"));
            }else {
                //$this->prepareAndResponse("200", "The dataset doesn't match", array("Variable Updated" => "false"));
                $this->response(['status' => false, 'message' => "The dataset doesn't match", "Variable Updated" => "false"], REST_Controller::HTTP_CONFLICT);
            }
        } else {
            //$this->prepareAndResponse("200", "The dataset doesn't match", array("Variable Updated" => "false"));
            $this->response(['status' => false, 'message' => "The dataset doesn't match", "Variable Updated" => "false"], REST_Controller::HTTP_CONFLICT);
        }
    }

    /**
     * Permite modificar los valores de una condicion de una variable AB
     * por defecto.
     */
    public function modifyDefaultValues() {
        $idVariable = $this->put('idVariable');
        $idApp = $this->put('idApp');
        $value = $this->put('values');
        if ($this->variable->modifyVariable($idApp, $idVariable, $value)) {
            // $this->prepareAndResponse("200","Success",array("updated" => "true" ));            
            $this->response([
                'status' => TRUE,
                'message' => "Success"
                    ], REST_Controller::HTTP_OK);
        } else {
            //$this->prepareAndResponse("200","Failed",array("updated" => "false" ));            
            $this->response([
                'status' => false,
                'message' => "Failed"
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    private function modifyABVariable() {
        $idApp = $this->put('idApp');
        $versionEthAppsSystem = $this->put('versionEthAppsSystem');
        $useremail = $this->put('useremail');
        $idabVar = $this->put('id_abvar');
        $idVar = $this->put('id_var');
        //if($this->app->appExists($idApp) && $this->user->userHaveApp($useremail, $idApp))//validar el usuario y el app
        {
            $variable = $this->variable->obtenerVariable($idVar);
            if ($variable != null) {
                $class = $variable->class;
                if ($class == "A/B") {
                    $conds = $this->put('conds');
                    $values = $this->put('values');
                    $result = $this->variable->modifyABVariable($idVar, $idabVar, $conds, $values);
                    if ($result == 1) {
                        // $this->prepareAndResponse("200", "Success", array("Variable Updated" => "true"));
                        $this->response(['status' => true, 'message' => "Success", "Variable Updated" => "true"], REST_Controller::HTTP_ACCEPTED);
                    } else {
                        $this->response(['status' => true, 'message' => "Success", "Variable Updated" => "FALSE"], REST_Controller::HTTP_NOT_MODIFIED);
                    } //$this->prepareAndResponse("200", "The dataset doesn't match", array("Variable Updated" => "false"));
                } else {
                    // $this->prepareAndResponse("200", "Variable not AB", array("result" => "false"));
                    $this->response(['status' => true, 'message' => "Variable not AB", "Variable Updated" => "FALSE"], REST_Controller::HTTP_NOT_MODIFIED);
                }
            } else {
                //$this->prepareAndResponse("200", "The dataset doesn't match", array("Variable Updated" => "false"));
                $this->response(['status' => true, 'message' => "The dataset doesn't match", "Variable Updated" => "FALSE"], REST_Controller::HTTP_NOT_MODIFIED);
            }
        }/* else{
          $this->prepareAndResponse("200","The dataset doesn't match",array("Variable Updated"=>"false1"));
          } */
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
        //$this->load->model(ETHVERSION."Variable","variable");
        $versionEthAppsSystem = $this->delete('versionEthAppsSystem');
        $useremail = $this->delete('useremail');
        $idabVar = $this->delete('id_abvar');
        $idVar = $this->delete('id_var');
        $res = $this->variable->deleteABCond($idVar, $idabVar);
        if ($res == 1) {
            // $this->prepareAndResponse("200","Variable deleted",array("deleted"=>"true"));
            $this->response(['status' => FALSE, 'message' => "Variable deleted"], REST_Controller::HTTP_OK);
        } else {
            //$this->prepareAndResponse("200","The dataset doesn't match",array("deleted"=>"false"));            
            $this->response(['status' => FALSE, 'message' => "The dataset doesn't match"], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    private function deleteVar() {
        $idApp = $this->delete('idApp');
        $versionEthAppsSystem = $this->delete('versionEthAppsSystem');
        $useremail = $this->delete('useremail');
        $id_variable = $this->delete('id_variable');
        if ($this->app->appExists($idApp) && $this->user->userHaveApp($useremail, $idApp)) {

            $result = $this->var->delete($id_variable, $idApp);
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
        $idapp = $this->getUrlData('idApp', 'base64');
        $idDownload = $this->getUrlData('idDownload', 'base64');
        $idDevice = $this->getUrlData('idDevice', 'base64');
        $model = $this->getUrlData('model', 'base64');
        $name = $this->getUrlData('name', 'base64');
        $platformName = $this->getUrlData('platformName', 'base64');
        $platformVersion = $this->getUrlData('platformVersion', 'base64');
        $ip = $_SERVER['REMOTE_ADDR'];
        $idApp = $this->getUrlData('idApp', 'base64');
        $additionalInfo = $this->getUrlData('additionalInfo', 'base64');

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

        $this->prepareAndResponse("200", "Success", array("success" => "true", "vars" => $var));
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

}
