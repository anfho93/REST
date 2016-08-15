<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Funnel
 *
 * @author andres
 */
defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
//require APPPATH . '/libraries/REST_Controller.php';
require 'EthRESTController.php';

class Funnels extends EthRESTController{
    
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
                case "resume":
                    
                    $this->resume();
                    return;
                case "data":
                    $this->funnelData();
                    break;
                case "status" : 
                    $this->funnelStatus();
                    break;
            }
        }else{        
            $this->response([
                'status' => FALSE,
                'message' => "No implementado aun"
                    ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    private function funnelData() {
        $idApp = $this->get('idapp');
        $funnelID = $this->get('funnelid');
        $this->load->model(ETHVERSION."funnel", "funnel");
        $funelName = $this->funnel->getFunnelNameById($funnelID);
        if($funelName!=null){            
            $result = $this->funnel->getFunnelData($idApp, $funelName, $funnelID);
            if($result!=null)            {
                $this->response(['status' => TRUE, 'message' => "success", "result"=>$result], REST_Controller::HTTP_ACCEPTED);                
            }else{
                //$this->prepareAndResponse("200", "Failed", array("result" => "Aun no hay datos del funnel"));
                $this->response(['status' => FALSE, 'message' => "failed"], REST_Controller::HTTP_BAD_REQUEST);
            }            
        }else{           
                $this->response(['status' => FALSE, 'message' => "Something went wrong"], REST_Controller::HTTP_BAD_REQUEST);
        }       
    }
    
    
    private function funnelStatus(){
         $funnelID = $this->get('funnelid');
        $url = "http://ethgame.com:11000/oozie/v2/job/" . $funnelID . "?show=status";
        $ch = curl_init($url);
        $output = curl_exec($ch);
        curl_close($ch);
        //verificar la respuesta
        //$this->response(['status' => FALSE, 'message' => "Something went wrong"], REST_Controller::HTTP_BAD_REQUEST);
    }
    
     private function resume() {
        $user_email = $this->get('useremail');
        $cond = $this->get('cond');
        switch ($cond){
            case 0:
                $cond= "";
                break;
            case 1:
                $cond= "SUCCEEDED";
                break;
            case 2:
                $cond= "Processing";
                break;
            case 3:
                $cond= "KILLED";
                break;
            default :
                $cond= "";
                break;
        }
        if ($user_email == ""){
            $this->response(['status' => FALSE, 'message' => "email needed"], REST_Controller::HTTP_BAD_REQUEST);
        }
        else {            
            $this->load->model(ETHVERSION."funnel", "funnel");
            $result = $this->funnel->getFunnelByEmail($user_email, $cond);
            $this->updateFunnelState($user_email, $result);$aux = array();
            foreach ($result as $row) {
                unset($row["id_funnels"]);
                $aux[] = $row;
            }
            $this->response(['status' => TRUE, 'message' => "Success", "result"=>  json_encode($aux), "titles" => array("ID", "Date", "Status")], REST_Controller::HTTP_ACCEPTED);
        }
    }

        
      private function updateFunnelState($user_email, $funnel) {
        $this->load->model(ETHVERSION."funnel", "funnel");
        foreach ($funnel as $row) {
            $funnelID = $row["id_funnels"];
            if ($row["status"] === "Processing" || $row["status"] == "RUNNING") {
                $url = "http://ethgame.com:11000/oozie/v2/job/" . $funnelID . "?show=status";              
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $output = curl_exec($ch);
                try {
                    $json = json_decode($output);
                    $this->funnel->setFunnelState($json->status, $funnelID);                    
                } catch (Exception $e) {
                    //error no actualizará el estado.
                }
            }
        }
    }

    public function index_post() {        
        $user_email = $this->post('useremail');
        $keys = $this->post('keys');
        $id_app = $this->post('idApp');
        $eventos = json_decode($this->post('events'));
        $this->initialDate = $this->post('initialDate'); //fecha en formato (2013-00-00), la diferencia entre esta y finalDate no debe exeder 3 meses o 90 dias
        $this->finalDate = $this->post('finalDate'); //fecha en formato (2013-00-00), esta fecha no debe ser mayor al dia actual.        
        $this->load->model(ETHVERSION."funnel", "funnel");
        $array = $this->funnel->generateQuery($eventos, $this->initialDate, $this->finalDate, $id_app, $user_email, $keys);
        if($array != null && count($array)>=2) {
            $query = $array[1];
            $this->idfunnel = $array[0];
            $ruta = $this->createNewHql($query);
            if ($ruta != null) {
                $workflowID = exec("java -jar /home/anfho/OozieHiveJob2.0.jar $ruta $user_email");
                if ($this->endsWith($workflowID, "W")) {
                    $result = $this->addFunnelRegister($workflowID, $user_email, $this->idfunnel);                  
                   $this->response(['status' => TRUE, 'message' => "Success", "result"=>  json_encode($result)], REST_Controller::HTTP_ACCEPTED);                   
                } else {                    
                    $this->response(['status' => TRUE, 'message' => "Something went wrong creating the funnel!!!"], REST_Controller::HTTP_BAD_REQUEST);                    
                }
            }else {
               $this->response(['status' => FALSE, 'message' => "Error", "result"=> "can't create funnel"], REST_Controller::HTTP_BAD_REQUEST);
            }
        } else {
               $this->response(['status' => FALSE, 'message' => "Error", "result"=> "can't create funnel"], REST_Controller::HTTP_BAD_REQUEST);
        }
        
        
    }

    public function index_put() {
       $this->response([
            'status' => FALSE,
            'message' => "Not Available"
                ], REST_Controller::HTTP_FORBIDDEN); 
    }

    public function index_delete() {
       $this->response([
            'status' => FALSE,
            'message' => "Not Available"
                ], REST_Controller::HTTP_FORBIDDEN); 
    }
    
    /**
     * Verifica si un texto termina con un texto determinado
     * @param type $haystack
     * @param type $needle
     * @return type 
     */
    private function endsWith($haystack, $needle) {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
    }

    private function addFunnelRegister($wokflowID, $email, $idfunnel) {
        $this->load->model(ETHVERSION."funnel", "funnel");
        $result = $this->funnel->addFunnelRegister($wokflowID, $email, $idfunnel);
        return $result;
    }
    /**
     * metodo utilizado para realizar una consulta de fechas real
     * @param int $y1 fecha inicial
     * @param int $y2 fecha final
     * @return string tipo de conector logico.
     */
    protected function getConector($y1, $y2) {
        if ($y2 > $y1) {
            return 'or';
        }
        return 'and';
    }
    /**
     * Crea un nuevo codigo HQL y lo envia a el HDFS
     * @param String $content contenido del HQL
     * @return mixed ruta o null en caso de que no se consiga subir el archivo
     */
    private function createNewHql($content) {
        $filename = uniqid("query");
        $password = "ethereal";
        $username = "anfho";
        $url = "http://ethgame.com:14000/webhdfs/v1/user/ethereal/funnels/queries/$filename.hql?op=CREATE&user.name=ethereal";
        $route = "/user/ethereal/funnels/queries/$filename.hql";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/octet-stream'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpcode == 201) {              //tuvo exito
            if ($this->agregarContenidoArchivo($filename, $content)) {
                curl_close($ch);
                return $route;
            } else {
                curl_close($ch);
                return null;
            }
        } else {
            //ocurre en problema creando el archivo
            curl_close($ch);
            return null;
        }
    }

    /**
     * Funcion que permite agregar el codigo HQL a el archivo
     * recien subido al HDFS
     * @param String $filename nombre del archivo
     * @param String  $contenido codigo HQL
     * @return boolean  true si registro false si no
     */
    private function agregarContenidoArchivo($filename, $contenido) {
        /* $password= "ethereal";
          $username="ethereal"; */
        $url = "http://ethgame.com:14000/webhdfs/v1/user/ethereal/funnels/queries/$filename.hql?op=APPEND&user.name=ethereal";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $contenido);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/octet-stream'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        //curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");            
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpcode == 200) {
            curl_close($ch);
            return true;
        } else {
            // responder con error
            curl_close($ch);
            return false;
        }
    }

}
