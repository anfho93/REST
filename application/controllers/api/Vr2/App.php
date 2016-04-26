<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';

class App extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $methodname = strtolower("index_" . $this->request->method);
        if (method_exists($this, $methodname)) {
            $this->$methodname();
        }
    }

    public function index_get() {
            $this->load->model(ETHVERSION.'App', "application");
            $user_email = $this->getUrlData('useremail','base64');
            $result =$this->application->getApps($user_email);
            if(count($result)>0)
            {
                 $this->response([
                'status' => FALSE,
                'message' => "No implementado aun"
                    ], REST_Controller::HTTP_BAD_REQUEST);
               // $this->prepareAndResponse("200","Success",array("appRegistred"=>"true", "result"=> json_encode($result)));

            }else 
            {
               // $this->prepareAndResponse("201","Success",array("appRegistred"=>"false", "result"=>json_encode(array())));    
            } 
       
    }

    public function index_post() {
        $this->response([
            'status' => FALSE,
            'message' => "No implementado aun"
                ], REST_Controller::HTTP_BAD_REQUEST);
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
