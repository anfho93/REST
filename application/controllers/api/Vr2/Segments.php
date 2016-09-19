<?php


/**
 * Controlador encargado del manejo de las peticiones de los segmentos 
 *
 * @author andres
 */
defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
//require APPPATH . '/libraries/REST_Controller.php';
require 'EthRESTController.php';

class Segments extends EthRESTController {

    public function __construct() {
        parent::__construct();
        $methodname = strtolower("index_" . $this->request->method);
        $this->load->model(ETHVERSION . 'segment', "segment");
        if (method_exists($this, $methodname)) {
            $this->$methodname();
        }
    }

    public function index_get() {
        if ($this->_pre_get() != null) {
            switch ($this->_pre_get()) {
                case "types":
                    $this->getTipos();
                    break;
                case "id":
                    $this->getSegment();
                    break;
                case "operands":
                    $this->getOperandos();
                    break;
                case "properties":
                    $this->getProperties();
                    break;
            }
        } else {
            $this->getUserSegments();
        }
    }

    /**
     * Esta funcion obntiene todas las propiedades 
     * disponibles para los dispositivos
     */
    private function getProperties() {
        $this->load->model(ETHVERSION . 'segment', "segment");
        $result = $this->segment->getAllProperties();
        $this->response([
                'status' => TRUE,
                'result' => $result,
                    ], REST_Controller::HTTP_ACCEPTED);
        
    }

    /**
     * Funcion que permite obtener los operandos disponibles.
     */
    private function getOperandos() {
        $this->load->model(ETHVERSION . 'segment', "segment");
         $this->response([
                'status' => TRUE,
                'result' => $this->segment->getAllOperandos(),
                    ], REST_Controller::HTTP_ACCEPTED);
      
    }

    
    /**
     * Funcion que obtiene un segmento por ID y correo
     * @param String $user_email correo electronico
     * @param String $idSeg identificador del segmento.
     * @return array datos del segmento buscado
     */
    private function getByID($user_email, $idSeg){
        $this->load->model(ETHVERSION.'segment', "segment");
        return $this->segment->getSegmentByID($user_email, $idSeg);
    }
    
      
    
    
    /**
     *  Funcionalidad que permite obtener un segmento
     *  de un usuario basado en el id y correo del usuario 
     *  de la aplicacion
     */
    private function getSegment() {
        $user_email = $this->get('useremail');
        $idSeg = $this->get('idsegment');
        $result = $this->getByID($user_email, $idSeg);
        if (!empty($result)) {
            $this->response([
                'status' => TRUE,
                'result' => $result,
                    ], REST_Controller::HTTP_ACCEPTED);
            //$this->prepareAndResponse("200","Success",array( "result"=> $result));
        } else {
            $this->response([
                'status' => FALSE,
                'message' => "Fail",
                    ], REST_Controller::HTTP_BAD_REQUEST);
            //$this->prepareAndResponse("200","Fail",array( "result"=> "No such segment"));
        }
    }

    private function getTipos() {
        $result = $this->segment->getTipos();
        if (!empty($result)) {
            $this->response([
                'status' => TRUE,
                "result" => $result
                    ], REST_Controller::HTTP_ACCEPTED);
            //$this->prepareAndResponse("200","Success",array( "result"=> $result));
        } else {
            $this->response([
                'status' => FALSE,
                'message' => "No such element",
                    ], REST_Controller::HTTP_FORBIDDEN);
            //$this->prepareAndResponse("200","Fail",array( "result"=> "No such segment"));
        }
    }

    /**
     * Permite obtener los segmentos de un usuario
     */
    private function getUserSegments() {
        $user_email = $this->get('useremail');
        $result = $this->segment->getSegments($user_email);
          $segments = array();
        foreach ($result as $key => $seg) {
            $segments[$seg['categoria']][] = $seg;
        }
        $this->response([
            'status' => TRUE,
            'message' => "success",
            "segments" => json_encode($segments),
            "result" => json_encode($result)
                ], REST_Controller::HTTP_ACCEPTED);
        // $this->prepareAndResponse("200","Success",array( "result"=> json_encode($result)));
    }

    public function index_post() {
        $user_email = $this->post('useremail');
        $name = $this->post('name');
        $title = $this->post('title');
        $value = $this->post('value');
        $category = "User";
        $result = $this->add($user_email, $category, $name, $title, $value);
        if ($result) {
            $this->response([
                'status' => TRUE,
                'message' => "Success",
                "result" => $result
                    ], REST_Controller::HTTP_ACCEPTED);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => "Not added"
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
        $user_email = $this->query('useremail');
        $id_seg = $this->query('idsegment');
       // print_r($this->query());
        $result = $this->remove($user_email, $id_seg);
        if ($result) {
            $this->response([
                'status' => TRUE,
                "result" => $result
                    ], REST_Controller::HTTP_ACCEPTED);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => "No se puede eliminar el segmento"
                    ], REST_Controller::HTTP_BAD_REQUEST);
            
        }
    }

    /**
     * Permite eliminar un segmento de un usuario
     */
    private function remove($useremail, $id_seg) {
        $result = $this->segment->delete($useremail, $id_seg);
        return $result;
    }

    /**
     * Permite agregar un segmento de un usuario determinado
     */
    private function add($user_email, $category, $name, $title, $value) {
        $result = $this->segment->add($user_email, $category, $name, $title, $value);
       // print_r($result);
        return $result;
    }

}
