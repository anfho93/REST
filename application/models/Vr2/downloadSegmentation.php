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

require_once BASEPATH . 'libraries/aws/aws-autoloader.php';
use Aws\DynamoDb\Iterator\ItemIterator;
require_once APPPATH.'models/'.ETHVERSION.'Helpers/Condition.php';
/**
* Clase que representa al Modelo de las usuarios.
*
* Esta clase mapea la tabla ethas_users de  la base de datos no relacional de DynamoDB.
*
*  @author Andres felipe Herrera <anfho93@gmail.com>
*  @version 1.1
*/
/**
* A continuacion se presentan los tipos de datos. que se usan en la tabla de downloads.
* 
*/
define("STRING", "S");
define("NUMBER", "N");
define("BOOLEAN", "S");
/**
* la valores permitidos para la comparacion son.
* EQ | NE | LE | LT | GE | GT | NOT_NULL | NULL | CONTAINS | NOT_CONTAINS | BEGINS_WITH | IN | BETWEEN
*/


class DownloadSegmentation extends CI_Model {

      /**
    * @var string Variable que representa el HashKey de la tabla id_dwonload
    */
    var $hashname = "id_app";
    /**
    * @var string Variable que representa el nombre de la tabla a mapear 
    */
    var $tablename = "ethas_segments";
    /**
    * @var Array    utilizado para almacenas valores de las llaves.
    */
    var $key = array();  
    /**
    * @var Array    utilizado para almacenas valores de los atributos
    */  
    var $attributes = array();
      
    var $conditions = array();

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }

    /**
    * 
    */
    function parseSegmentation(){
        return $this->conditions;
    }


    function registerSegment($idApp, $name, $arrayJsonSegment){
        /*$keys = array('id_app' => array('S' => "1"),
                          "name" => array('S' => $name ));

            $updates = array('conditions' => array(
                                'Value' => array(
                                    'SS' => $arrayJsonSegment
                                ),
                                'Action' => 'PUT'));
                          

           $this->db->updateItemById($this->tablename, $keys, $updates); */
    }

    /**
    * Genera una condicion de busqueda para agregarla un segmento.
    */
    function addSystemLanguage($attribute, $condition, $expectedValue){

        $condicion =  new Condition( "systemLanguage", $condition, STRING, $expectedValue );
        $data = $condicion->parseConditionIntoAWS();
     
        $this->conditions[] = $data;
    }
    

}
