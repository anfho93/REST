<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
//require APPPATH . '/libraries/REST_Controller.php';
require 'EthRESTController.php';

class Apps extends EthRESTController {

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
                case "url" :
                    $this->getURLDownload();
                    break;
            }
        } else {
            $this->getUserApps();
        }
    }

    private function getURLDownload() {
        $this->load->model(ETHVERSION . 'App', "application");
        $id_app = $this->get('idApp');
        $platform = $this->get('platform');
        if ($this->application->appExists($id_app)) {
            $url = $this->application->isPlatform($id_app, $platform);
            //$this->prepareAndResponse("200", "Success", array("result" => "$url"));
            $this->response([
                'status' => TRUE,
                'response' => array('message' => "success",
                    "result" => "$url")
                    ], REST_Controller::HTTP_ACCEPTED);
        } else {
            $url = "none";
            //$this->prepareAndResponse("200", "Failed", array("result" => "$url"));
            $this->response([
                'status' => TRUE,
                'response' => array('message' => "success",
                    "result" => "$url")
                    ], REST_Controller::HTTP_CONFLICT);
        }
    }

    private function getUserApps() {
        $this->load->model(ETHVERSION . 'app', "application");
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
       // print_r($this->post());
        $this->load->model(ETHVERSION . 'App', "application");
        $name_app = ($this->post('appname'));
        $description = ($this->post('description'));
        $type = ($this->post('inputtype'));
        $user_email = ($this->post('useremail'));
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
        //print_r($this->put());
        $this->load->model(ETHVERSION . 'App', "application");
        $idApp = ($this->post('idapp'));
        $name_app = ($this->put('appname'));
        $description = ($this->put('description'));
        $type = ($this->put('inputtype'));
        $user_email = ($this->put('useremail'));
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
        $this->load->model(ETHVERSION . "App", "app");
        $this->load->model(ETHVERSION . "User", "user");
        $idApp = ($this->query('idapp'));
        $email = ($this->query('useremail'));
        $status = 0;
        $appname = ($this->query('appname'));
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
                    "result" => "App Updated"
                        ], REST_Controller::HTTP_ACCEPTED);
            } else {
                $this->response([
                    'status' => FALSE,
                    'message' => "App not Updated"
                        ], REST_Controller::HTTP_BAD_REQUEST);
            }
        } else {
           // echo $idApp . "asdasd";

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
