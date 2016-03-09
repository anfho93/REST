<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		Esen Sagynov
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 2.0.2
 * @filesource
 */
// ------------------------------------------------------------------------

/**
 * DynamoDB Database Adapter Class
 *
 * Note: _DB is an extender class that the app controller
 * creates dynamically based on whether the active record
 * class is being used or not.
 *
 * @package		CodeIgniter
 * @subpackage	Drivers
 * @category	Database
 * @author		Andres Felipe Herrera
 * @link		http://codeigniter.com/user_guide/database/
 */
require_once BASEPATH . 'libraries/aws/aws-autoloader.php';

use Aws\Common\Aws;
use Aws\DynamoDb\Exception\ConditionalCheckFailedException;

class CI_DB_dynamo_driver extends CI_DB {

    var $dbdriver = 'dynamo';
    var $dynamoClient = null;

    /**
     * This function creates a conection between the app an Dynamo
     * @return DynamoClient , client to connect with dynamo
     */
    function db_connect() {
        if ($this->key != '' && isset($this->key) && isset($this->secret) && $this->secret != "" && isset($this->region) && $this->region != "") {
            $aws = Aws::factory(array("key" => $this->key, "secret" => $this->secret, "region" => $this->region));
            $this->dynamoClient = $aws->get('DynamoDb');
            return $this->dynamoClient;
        }
    }

    function getItem($tablename, $keys) {
         if ($this->dynamoClient == null) {
            $this->db_connect();
        }
        $result = $this->dynamoClient->getItem(array(
            'ConsistentRead' => true,
            'TableName' => $tablename,
            'Key' => $keys
        ));
        return $result;
    }

    /**
     * 
     * @param type $query
     * @return type
     */
    function _execute($query) {
        //$sql = $this->_prep_query($sql);
        //return @mysql_query($sql, $this->conn_id);
        return $query;
    }

    function count_all($table = '') {
        
    }

    function scanTable($paramos)
    {
        if ($this->dynamoClient == null) {
            $this->db_connect();
        }
          $result = $this->dynamoClient->scan($paramos);
          return $result;
    }

    /**
     * List table query
     * Generates a platform-specific query string so that the table names can be fetched
     *
     * @access	private
     * @param	boolean
     * @return	string
     */
    function _list_tables() {
        if ($this->dynamoClient == null) {
            $this->db_connect();
        }
        $result = $this->dynamoClient->listTables();
        return $result;
    }

    /**
     * This method says if a table exist
     * @param string $tablename , 
     * @return boolean return true if the table name as parameter exist
     */
    function tableExist($tablename) {
        if ($this->dynamoClient == null) {
            $this->db_connect();
        }
        $result = $this - _list_tables();
        // TableNames contains an array of table names
        foreach ($result['TableNames'] as $tName) {
            if ($tName == $tablename) {
                return true;
            }
        }
        return false;
    }

    function itemExist($table, $keys) {
        
    }

    /**
     * 
     * @param String $tablename name of the table which we want its description.  
     * @return array ,  description from the table
     */
    function describe_table($tablename = "") {
        if ($this->tableExist($tablename)) {
            return $this->dynamoClient->describeTable(array(
                        'TableName' => $tablename
            ));
        } else {
            return "The table doesn't exist";
        }
    }

    /**
     * this method 
     * @param type $table
     * @param type $Attributes
     * @return type
     */
    function insert($table, $Attributes) {
       // echo json_encode($Attributes);
        //echo "\n";
        //echo json_encode($this->dynamoClient->formatAttributes($Attributes));
        try {
            $result = $this->dynamoClient->putItem(array(
                'TableName' => $table,
                'Item' => $this->dynamoClient->formatAttributes($Attributes),
                'ReturnConsumedCapacity' => 'TOTAL'
            ));
            // The result will always contain ConsumedCapacityUnits
            return true;
        } catch (Exception $e) {
            echo "error " . $e->getMessage();
        }
        return false;
    }
    
    function elementExistByID($table, $hashname, $hashvalue)
    { 
        if ($this->dynamoClient == null) {
            $this->db_connect();
        }
        $result = $this->dynamoClient->query(array(
           'TableName'     => $table,
           'Select'        => 'COUNT',
           'KeyConditions' => array(
               "$hashname" => array(
                   'AttributeValueList' => array(
                       array('S' => $hashvalue)
                   ),
                   'ComparisonOperator' => 'EQ'
               )
           )
          ));        
        if ($result['Count'] > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    function searchElement($table, $filter, $columnsToGet=null, $select=null){
         if ($this->dynamoClient == null) {
            $this->db_connect();
         }
         $params =  array();
         if($columnsToGet != null)
         {
            $params = array('TableName'     => $table,"AttributesToGet"=>$columnsToGet , 'ScanFilter' => $filter);
         }else{
            $params = array('TableName'     => $table,'ScanFilter' => $filter);
            }

         if($select!=null)
         {
               $params = array_merge($params, array("Select"=>$select));
         }   
         

          
        $iterator = $this->dynamoClient->getIterator('Scan', $params);
        return $iterator;
       
    }

    function getQueryIterator($params){
        if ($this->dynamoClient == null) {
            $this->db_connect();
        }
         $result = $this->dynamoClient->getQueryIterator($params);
        return $result;
    }

    function countElementsByAttributes($table, $filter){
        if ($this->dynamoClient == null) {
            $this->db_connect();
        }



        $params = array("Select"=>"COUNT",'TableName' => $table,'ScanFilter' => $filter);
          
        $result = $this->dynamoClient->scan($params);
        return $result;
    }

    function insert_batch($params) {
        if ($this->dynamoClient == null) {
            $this->db_connect();
        }

        $this->dynamoClient->batchWriteItem($params);
    }

    function query($tableName, $filter, $columnsToGet = null, $select= null, $LastEvaluatedKey){
         if($this->dynamoClient == null) {
            $this->db_connect();
         }
         $params =  array();
         if($columnsToGet != null)
         {  
            if($LastEvaluatedKey == null)
                $params = array('TableName'     => $tableName, "AttributesToGet"=>$columnsToGet , 'KeyConditions' => $filter);
            else $params = array('TableName'     => $tableName, "LastEvaluatedKey"=>$LastEvaluatedKey , "AttributesToGet"=>$columnsToGet , 'KeyConditions' => $filter);
         }else{
            if($LastEvaluatedKey == null)
                $params = array('TableName'     => $tableName, 'KeyConditions' => $filter);
            else 
                $params = array('TableName'     => $tableName,  "LastEvaluatedKey"=>$LastEvaluatedKey ,'KeyConditions' => $filter);
         }

         if($select!=null)
         {
               $params = array_merge($params, array("Select"=>$select));
         }   
         

          
        $result = $this->dynamoClient->query($params);
        return $result;

    }

    function queryIndex($tableName, $filter, $index , $QueryFilter){
        if($this->dynamoClient == null) {
            $this->db_connect();
        }
        $params =  array();
        $params["TableName"]  = $tableName;
        $params["IndexName"]  = $index;
        $params["KeyConditions"]  = $filter;
        $params["QueryFilter"]  = $QueryFilter;

         $result = $this->dynamoClient->query($params);

        return $result;

    }

    function queryFilter($tableName, $filter,$queryFilter, $count =null){
         if ($this->dynamoClient == null) {
            $this->db_connect();
         }
          $params =  array("TableName" => $tableName, "KeyConditions"=>$filter, "QueryFilter" => $queryFilter);
          if($count !=null)
          {
            $params["Select"] = $count;
          }
          $result = $this->dynamoClient->query($params);
         return $result;
    }

    function updateItemById($tablename, $keys, $updates) {

        if ($this->dynamoClient == null) {
            $this->db_connect();
        }
   

       return $this->dynamoClient->updateItem(array(
            // TableName is required
            'TableName' => $tablename,
            // Key is required
            'Key' => $keys,
            'AttributeUpdates' =>$updates,
            'ReturnConsumedCapacity'=>'TOTAL'
            )
        );
    }

    function _close($conn_id) {
        //@mssql_close($conn_id);
    }

    /**
     * this method delets an item search matches by primary key.
     * @param array $Keys , this is an array with the Keys to look for.
     * 
     */
    function _deleteByPrimaryKey($tableName, $Keys) {
        try {
            if ($this->dynamoClient == null) {
                $this->db_connect();
            }
     
            return $this->dynamoClient->deleteItem(array(
                'TableName' => $tableName,
                'Key' => $Keys
            ));
        } catch (ConditionalCheckFailedException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 
     * @param type $tableName
     * @param type $keys, arrelgo con las llaves principales de las tablas
     * @param type $expected, array cn los datos esperados, su estructura es similar a array(array("N"=>"3"),array("N"=>"3")
     * si se espera comprar un atributo con los valores en el array
     * @param String $operadorCondicion Si la cantidad de attributos que se van a analizar es mayor a 1 se requiere este argumento.
     */
    function _delete($tableName, $keys, $expected, $operadorCondicion = "AND") {
        try {
            if ($this->dynamoClient == null) {
                $this->db_connect();
            }
            $array = array();
            foreach ($keys as $key) {
                $array[$key->attributeName] = array($key->attributeDataType => $key->value);
            }
            $deleteItemArray = array('TableName' => $tableName,
                "Key" => $array);
            if (isset($expected) and is_array($expected)) {
                $exp = array();
                foreach ($expected as $x) {
                    $exp[$x->keyName] = array("ComparisonOperator" => $x->comparisonOperator,
                        "AttributeValueList" => $x->attributeValueList);
                }
                $deleteItemArray["Expected"] = $exp;
            }
            if (is_array($expected) and count($expected) > 1) {
                $deleteItemArray["ConditionalOperator"] = $operadorCondicion;
            }
            $this->dynamoClient->deleteItem($deleteItemArray);
        } catch (ConditionalCheckFailedException $e) {
            echo $e->getMessage();
        }
    }

}
