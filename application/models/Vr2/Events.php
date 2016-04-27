<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once BASEPATH . 'libraries/aws/aws-autoloader.php';

/**
*
* Clase que representa un Modelo para Enlazar las Estadisticas de las aplicaciones.
*
* Esta clase mapea la tabla ethas_applications de  la base de datos no relacional de DynamoDB.
*
* @author Andres Felipe Herrera <anfho93@gmail.com>
*  @version 1.1
*/
class events extends CI_Model {

 	/**
    * @var string Variable que representa el HashKey de la tabla id_app
    */
    var $hashname = "app_id";
    var $rangename = "cat_sub_lab";


    /**
    *@var  string nombre de la tabla
    */
    var $tablename = "ethas_events";    
    /**
    * @var Array    utilizado para almacenas valores de las llaves.
    */
    var $key = array();   
    /**
    *@var Array     utilizado para almacenas valores de los atributos a agregar a un Item de la tabla..
    */  
    var $attributes = array();
    /**
    * Constructor de la clase.
    */
    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }

   

}