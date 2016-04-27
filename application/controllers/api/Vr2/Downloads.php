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
                default : {
                         $this->response([
                            'status' => FALSE,
                            'message' => "se ejecutó la accion por defecto"
                                ], REST_Controller::HTTP_BAD_REQUEST);
                    }
                    break;
            }
        } else {
            $this->response([
            'status' => FALSE,
            'message' => "indique que desea obtener"
                ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function index_post() {
        
        $this->response([
        'status' => TRUE,
        'message' => "elimine"
            ], REST_Controller::HTTP_ACCEPTED);
    }

    public function index_put() {
        $this->response([
        'status' => TRUE,
        'message' => "elimine"
            ], REST_Controller::HTTP_ACCEPTED);
    }

    public function index_delete() {
          $this->response([
            'status' => TRUE,
            'message' => "elimine"
                ], REST_Controller::HTTP_ACCEPTED);
    }

    private function statistics() {
        $this->response([
            'status' => TRUE,
            'result' => "estas son las estadisticas",
            'message'=> "message"
                ], REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }
    
    

}
