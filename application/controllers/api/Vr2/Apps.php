<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';

class Apps extends REST_Controller {

    public function __construct() {
        parent::__construct();
       
        $methodname = strtolower("index_" . $this->request->method);
        if (method_exists($this, $methodname)) {
            $this->$methodname();
        }
    }

    public function index_get() {
        
        $this->load->model(ETHVERSION .'app', "application");
        $user_email = ($this->get('useremail'));
        $result = $this->application->getApps($user_email);
        if (count($result) > 0) {
            $this->response([
                'status' => TRUE,
                'response' => array('message' => "success",
                    "result" => json_encode($result))
                    ], REST_Controller::HTTP_ACCEPTED);
        } else {
            $this->response([
                'status' => TRUE,
                'response' => array('message' => "success",
                    "result" => json_encode(array()))
                    ], REST_Controller::HTTP_ACCEPTED);
        }
    }

    public function index_post() {
        
        $this->load->model(ETHVERSION . 'App', "application");
        $name_app = ($this->post('name_app'));
        $description = ($this->post('description'));
        $type = ($this->post('inputType'));
        $user_email = ($this->post('usermail'));
        $platforms = ($this->post('platforms'));
        
        if ($this->canRegisterApp($user_email)) {
            $result = $this->application->registerApp($type, $name_app, $description, $user_email, $platforms);
            if ($result !== false) {
                $this->response([
                    "appRegistred" => "true",
                    "result" => "$result"
                        ], REST_Controller::HTTP_CREATED);
            } else {
                $this->response([
                    "appRegistred" => "false",
                    "result" => "$result"
                        ], REST_Controller::HTTP_CREATED);
            }
        } else {
           $this->response([
                "appRegistred" => "false",
                "result" => "the user can't register app"
                    ], REST_Controller::HTTP_BAD_REQUEST);
            
        }
    }

    //falta desactivar app    
    public function index_put() {
        
        $this->load->model(ETHVERSION . 'App', "application");
        $idApp = ($this->post('idApp'));
        $name_app = ($this->put('name_app'));
        $description =($this->put('description'));
        $type = ($this->put('inputType'));
        $user_email = ($this->put('usermail'));
        $platforms = ($this->put('platforms'));

        if ($this->canRegisterApp($user_email)) {
            $result = $this->application->updateApp($idApp, $type, $name_app, $description, $user_email, $platforms);
            if ($result !== false) {
                $this->response([
                    "appUpdated" => "true",
                    "result" => "Modified"
                        ], REST_Controller::HTTP_CREATED);
            } else {
                $this->response([
                    "appUpdated" => "false",
                    "result" => "Not Modified"
                        ], REST_Controller::HTTP_CREATED);
            }
        } else {
            $this->response([
                "appUpdated" => "false",
                "result" => "the user can't update app"
                    ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function index_delete() {
        $idApp = ($this->query('idApp'));
        $email = ($this->query('usermail'));
        $status = ($this->query('active'));
        $appname = ($this->query('appname'));
        $this->load->model(ETHVERSION . "App", "app");
        $this->load->model(ETHVERSION . "User", "user");
        if ($this->user->userHaveApp($email, $idApp)) {
            
            if ($status) {
                $result = $this->app->activateApplication($idApp, $appname);
            } else {
                $result = $this->app->deactivateApplication($idApp, $appname);
            }
            
            if ($result) {
                $this->response([
                    'status' => TRUE,
                    'message' => "Success",
                    "result"=> "App Updated"
                        ], REST_Controller::HTTP_ACCEPTED);
                
            } else {
                $this->response([
                    'status' => FALSE,
                    'message' => "App not Updated"
                        ], REST_Controller::HTTP_BAD_REQUEST);
               
            }
        } else {
           echo $idApp."asdasd";
            
            $this->response([
                'status' => FALSE,
                'message' => "user doesn't have permission"
                    ], REST_Controller::HTTP_BAD_REQUEST);
            
        }
    }

    private function canRegisterApp($email) {
        $this->load->model(ETHVERSION . 'User', "user");
        if ($this->user->userExists($email)) {
            return true;
        } else {
            return false;
        }
    }

}
