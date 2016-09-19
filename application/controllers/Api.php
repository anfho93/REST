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
        if ($this->load_version($this->uri->segment(2), $this->uri->segment(3))) {
           //funciona cada metodo ejecutado responde
        } else {
         //faltan datos no se responde.
        }
    }
    /**
     * Permite versionar las funcionalidades de la api, por defecto la version actual es la 2, 
     * debe tambien definirse el nombre del servicio a consumir, para poder continuar con el
     * proceso.
     * @param string $version , identificador e la sesion
     * @param string $class , nombre del endpoint del servicio.
     * @return boolean , true si se puedo ejecutar la funcionalidad, false de lo contrario.
     */
    private function load_version($version = "v2", $class = "def") {
        try {
            
            $class = ucfirst($class);
            if ($class == "def")
              return false;
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
                        define("ETHVERSION", "Vr2/");
                        
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
