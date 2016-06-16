<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of User
 *
 * @author andres
 */

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
//require APPPATH . '/libraries/REST_Controller.php';
require 'EthRESTController.php';

class Users extends EthRESTController{

    
    
    public function __construct() {
        parent::__construct();
        $usermodel = $this->load->model(ETHVERSION.'User', "usuario");
        $methodname = strtolower("index_" . $this->request->method);
        if (method_exists($this, $methodname)) {
            $this->$methodname();
        }
    }

    public function index_get() {
        $this->response([
            'status' => FALSE,
            'message' => "No implementado aun"
                ], REST_Controller::HTTP_BAD_REQUEST);
    }

    /**
    * Funcion principal del servicio, aqui se se reciben los parametros via peticion http.
    * @param string $email en base64, 
    * @param string $userCompanyName en base64, 
    * @param string $versionEthAppsSystem en base64, Numero de la version de la API
    * @param string $username en base64, versionn de la aplicacion que obtiene las variables.
    * @param string $zipcode en base64, nombre de la variable a obtener.
    * @param string $password en base64, obtiene la variable con detalles.
    * @return  json  string , respuesta por parte del servidor si se puedo o no registrar el usuario.
    */  
    public function index_post() {      
        
        if ($this->_pre_get() != null) {
            switch ($this->_pre_get()) {
                case "login" : 
                       $this->login();                    
                    break;                
            }
        } else {
            $this->registerUser();
        }
       
    }
    
      /**
    * Funcion principal del servicio, aqui se se reciben los parametros via peticion http.
    * @param string $userName en base64, nombre del usuario
    * @param string $password en base64, contraseña del usuario
    * @return json string|, respuesta con la autenticacion o el mensaje de error.
    *
    */     
    private function login(){
        $userName = $this->post('username');
	$password = $this->post('password');
        $this->load->model(ETHVERSION."User","usuario");
        $result =$this->usuario->login($userName, $password);        
        if($result){
            //$this->prepareAndResponse("200","Success",array("autenticated"=>"true"));
            $this->response([
            'status' => TRUE,
            'message' => "Success",
            "autenticated"=>"true"
                ], REST_Controller::HTTP_OK);
        }else{
            //$this->prepareAndResponse("200","Success",array("autenticated"=>"false"));    
            $this->response([
            'status' => false,
            'message' => "Fail",
            "autenticated"=>"false"
                ], REST_Controller::HTTP_NOT_ACCEPTABLE);
        } 
    }
    
    private function registerUser(){      
        //print_r($this->post());
        $email = $this->post('useremail');
        
        $userCompanyName =$this->post('companyname');
        $username = $this->post('username');
        $userLastName = $this->post('userlastname');
        $zipcode = "630003"; $country = "Colombia"; $state ="quindio"; $city =  "armenia";
        $password  = $this->post('password');      
        $pass = $this->create_hash($password);
        $result = $this->usuario->registerUser($email, $pass, $username, $userLastName, $userCompanyName, $zipcode, $country, $state, $city );        
        if($result){
           // $this->prepareAndResponse("200", "Success", array("registred"=>"true"));
             $this->response([
            'status' => TRUE,
            'message' => "REGISTRED"
                ], REST_Controller::HTTP_OK);
        }else{
            //$this->prepareAndResponse("200", "Success", array("registred"=>"false"));    
             $this->response([
            'status' => FALSE,
            'message' => "Not registred"
                ], REST_Controller::HTTP_BAD_REQUEST);
        } 
    }
    
    
    
   
    /**
     * 
     * @param type $password
     * @return type
     */
    private function create_hash($password)
    {
        // format: algorithm:iterations:salt:hash
        $salt = base64_encode(mcrypt_create_iv(PBKDF2_SALT_BYTE_SIZE, MCRYPT_DEV_URANDOM));
        return PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" .  $salt . ":" . 
            base64_encode($this->pbkdf2(
                PBKDF2_HASH_ALGORITHM,
                $password,
                $salt,
                PBKDF2_ITERATIONS,
                PBKDF2_HASH_BYTE_SIZE,
                true
            ));
    }
    
    
    /**
     * Genera una encripcion basado en varios algoritmos
     * @param String $algorithm algoritmo de encripcion
     * @param String $password contraseña a encriptar
     * @param String $salt codigo salt
     * @param String $count cantidad de iteraciones a encriptar
     * @param String $key_length longitud de la llave
     * @param String $raw_output salida completa
     * @return String contraseña encriptada
     */
      private function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false)
        {
            $algorithm = strtolower($algorithm);
            if(!in_array($algorithm, hash_algos(), true))
                trigger_error('PBKDF2 ERROR: Invalid hash algorithm.', E_USER_ERROR);
            if($count <= 0 || $key_length <= 0)
                trigger_error('PBKDF2 ERROR: Invalid parameters.', E_USER_ERROR);

            if (function_exists("hash_pbkdf2")) {
                // The output length is in NIBBLES (4-bits) if $raw_output is false!
                if (!$raw_output) {
                    $key_length = $key_length * 2;
                }
                return hash_pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output);
            }

            $hash_length = strlen(hash($algorithm, "", true));
            $block_count = ceil($key_length / $hash_length);

            $output = "";
            for($i = 1; $i <= $block_count; $i++) {
                // $i encoded as 4 bytes, big endian.
                $last = $salt . pack("N", $i);
                // first iteration
                $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
                // perform the other $count - 1 iterations
                for ($j = 1; $j < $count; $j++) {
                    $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
                }
                $output .= $xorsum;
            }

            if($raw_output)
                return substr($output, 0, $key_length);
            else
                return bin2hex(substr($output, 0, $key_length));
        }
        
        // Compares two strings $a and $b in length-constant time.
    private function slow_equals($a, $b)
    {
        $diff = strlen($a) ^ strlen($b);
        for($i = 0; $i < strlen($a) && $i < strlen($b); $i++)
        {
            $diff |= ord($a[$i]) ^ ord($b[$i]);
        }
        return $diff === 0; 
    }
    
    private function validate_password($password, $correct_hash)
    {
        $params = explode(":", $correct_hash);
        if(count($params) < HASH_SECTIONS)
           return false; 
        $pbkdf2 = base64_decode($params[HASH_PBKDF2_INDEX]);
        return slow_equals(
            $pbkdf2,
            pbkdf2(
                $params[HASH_ALGORITHM_INDEX],
                $password,
                $params[HASH_SALT_INDEX],
                (int)$params[HASH_ITERATION_INDEX],
                strlen($pbkdf2),
                true
            )
        );
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
