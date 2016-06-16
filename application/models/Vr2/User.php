<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of user
 *
 * @author AHerrera
 */

//require_once BASEPATH . 'libraries/aws/aws-autoloader.php';
//use Aws\DynamoDb\Iterator\ItemIterator;

/**
* Clase que representa al Modelo de las usuarios.
*
* Esta clase mapea la tabla ethas_users de  la base de datos no relacional de DynamoDB.
*
*  @author Andres felipe Herrera <anfho93@gmail.com>
*  @version 1.1
*/

class User extends CI_Model {

     /**
    * @var string Variable que representa el HashKey de la tabla id_app
    */
    var $hashname = "user_email";
      /**
    *@var  string nombre de la tabla
    */
    var $tablename = "ethas_users";
    /**
    * @var Array    utilizado para almacenas valores de las llaves.
    */
    var $key = array();    
    
   
    
    var $attributes = array();
  
    /**
    * Constructor de la clase.
    */        
    function __construct()
    {
        parent::__construct();
       
    }
 

    /**
    * Funcion que permite realizar una autenticacion a el Frontend
    *
    * @param string $email, correo electronico del usuario propietario de las apps.
    * @param string $password, contraseña del usuario
    * @return boolean si existe o no un usuario con estas dos caracteristicas, en la base de datos.
    *
    */
   public  function login($email,$password){
    
        $result = $this->db->get_where( $this->tablename, array('user_email' => $email));//, "password" => $password ) );
        if($result->num_rows()>0)
        {
           //actualizamos la ultima fecha de acceso 
          $data = array(
               'lastConnection' => date("Y-m-d")              
          );          
          $userpass = $result->result_array()[0]["password"];          
          if($this->validate_password($password, $userpass)){
                $this->db->where('user_email', $email);
                $this->db->update($this->tablename, $data);
                return true;
          }else{
              return false;          
          }          
        }else
        {
          return false;        
        }
          
    }
    
    /**
     * Genera un hash de un string
     * @param String $password contraseña del usuario.
     * @return String hash de la contraseña.
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
     * Valida un hash creado con el algoritmo seleccionado y un 
     * String normal para verificar que coincidan
     * @param String $password contraseña del usuario
     * @param String $correct_hash hash de la contraseña almacenada
     * @return boolean true si son iguales los hash, false si no son iguales
     */
    private function validate_password($password, $correct_hash)
     {
         $params = explode(":", $correct_hash);
         if(count($params) < HASH_SECTIONS)
            return false; 
         $pbkdf2 = base64_decode($params[HASH_PBKDF2_INDEX]);
         return $this->slow_equals(
             $pbkdf2,
             $this->pbkdf2(
                 $params[HASH_ALGORITHM_INDEX],
                 $password,
                 $params[HASH_SALT_INDEX],
                 (int)$params[HASH_ITERATION_INDEX],
                 strlen($pbkdf2),
                 true
             )
         );
     }

        // Compares two strings $a and $b in length-constant time.
     /**
      * Compara dos strings 
      * @param string $a
      * @param string $b
      * @return boolean true si son iguales false de lo contrario.
      */
       private function slow_equals($a, $b)
        {
            $diff = strlen($a) ^ strlen($b);
            for($i = 0; $i < strlen($a) && $i < strlen($b); $i++)
            {
                $diff |= ord($a[$i]) ^ ord($b[$i]);
            }
            return $diff === 0; 
        }

        /*
         * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
         * $algorithm - The hash algorithm to use. Recommended: SHA256
         * $password - The password.
         * $salt - A salt that is unique to the password.
         * $count - Iteration count. Higher is better, but slower. Recommended: At least 1000.
         * $key_length - The length of the derived key in bytes.
         * $raw_output - If true, the key is returned in raw binary format. Hex encoded otherwise.
         * Returns: A $key_length-byte key derived from the password and salt.
         *
         * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
         *
         * This implementation of PBKDF2 was originally created by https://defuse.ca
         * With improvements by http://www.variations-of-shadow.com
         */
      private  function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false)
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
	
    /**
     * Obtiene los segmentos de un usuario junto con los creados por el
     * @param String $user_id, correo electronico del usuario.
     * @return array conjunto de segmentos predeterminados y del usuario.
     */
    public function getSegments($user_id){
        $this->db->select("titulo, id_segmento, nombre, categoria");
        $this->db->from("ethas_segment");
        //$this->db->where("id_user", $user_id);
       // $this->db->or_where("id_user", "default");
        $result = $this->db->get();
        return $result->result_array();
    }

    /**
    *Esta  funcion determina si un usuario esta registrado en la base de datos.
    * @param string $email , correo electronico del usuario a buscar.
    * @return boolean , el usuario existe o no.
    */  
    function userExists($email) {        
        $result = $this->db->get_where($this->tablename, array('user_email' => $email));
        //print_r($result);
        if($result->num_rows()>0){
           return true;
        }else{
           return false; 
        }          
    }  

   

    /**
    * 
    * Determina si un usuario tiene o no un app registrada a su nombre.
    * @param string $email, correo del usuario. 
    * @param string $idApp, id de la aplicacion a buscar.
    * @return boolean, si el usuario posee o no la aplicacion con $idApp registrado a su nombre
    */
    function userHaveApp($email, $idApp){
        if($this->userExists($email))
        {          
          $result = $this->db->get_where("ethas_application", array('user_email' => $email , "id_app" => $idApp) );
          if( $result->num_rows()>0) 
            return true;
        }
        return false;        
    }
    

    /**
    * Registra un nuevo usuario en la base de datos.
    * @param string $email, correo electronico del usuario.
    * @param string $password, contraseña del usuario.
    * @param string $username, nombre del usuario, es un nombre personal.
    * @param string $userLastName, Apellido del usuario.
    * @param string $userCompanyName, nombre de la empresa a la que pertenece el usuario.
    * @param string $zipcode,  codigo de zona postal.
    * @param string $country,  nombre del pais.
    * @param string $state, nombre de la departamento o cidudad.
    * @param string $city,  nombre de la ciudad. 
    * @return boolean de acuerdo si el usuario a sido registrado.
    */        
    function registerUser($email, $password, $username , $userLastName, $userCompanyName, $zipcode, $country, $state, $city){
        
        if(!$this->userExists($email))
        {   
            $date = date("Y-m-d");
            $this->key["user_email"] = $email;
            $this->attributes["password"] = $password;
            $this->attributes["active"] = false;
            $this->attributes["lastModification"] = $date;
            $this->attributes["lastConnection"] = $date;
            $this->attributes["registerDate"] = $date;            
            $this->attributes["userName"] = $username;
            $this->attributes["userLastName"] = $userLastName;
            $this->attributes["userCompanyName"] = $userCompanyName;
            $this->attributes["zip_code"] = $zipcode;
            $this->attributes["country"] = $country;
            $this->attributes["state"] = $state;
            $this->attributes["city"] = $city;
            $this->key = array_merge($this->key,$this->attributes);             

            $result = $this->db->insert( "ethas_users",$this->key);
            
            return $result;
        }
        return false;
    }

   
}
