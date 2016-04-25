<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of api
 *
 * @author Proyecto672
 */
class Api extends CI_Controller {

    private $api = null;

    public function index() {

        // echo $this->uri->segment(2);
        if ($this->load_version($this->uri->segment(2), $this->uri->segment(3))) {
            
        } else {
            exit("Version no encontrada");
        }
    }

    private function load_version($version = "v2", $class = "def") {
        try {
            //echo $version." - ".$class;
            $class = ucfirst($class);
            if ($class == "def")
                exit("El recurso no existe2");
            switch ($version) {
                case "v1":

                    if (file_exists(APPPATH. "controllers/api/Vr1/$class.php")) {
                        require ("api/Vr1/$class.php");
                        $this->api = new $class();
                        return true;
                    } else
                        return false;
                    break;
                case "v2":
                   
                    if (file_exists(APPPATH."controllers/api/Vr2/$class.php")) {
                        require ("api/Vr2/$class.php");
                        $this->api = new $class();
                        return true;
                    } else
                        return false;
                    break;
                default:
                    if (file_exists(APPPATH."controllers/api/Vr1/$class.php")) {
                        require ("api/Vr1/$class.php");
                        $this->api = new $class();
                        return true;
                    } else
                        return false;
                    
                    break;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

}
