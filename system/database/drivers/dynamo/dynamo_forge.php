<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CUBRID Forge Class
 *
 * @category	Database
 * @author		Andres Felipe Herrera Ospina
 * @link		http://codeigniter.com/user_guide/database/
 */
define("PRIMARY_KEY",     1);
define("SECONDARY_KEY",     2);
define("GLOBAL_KEY",     3);
class CI_DB_dynamo_forge extends CI_DB_forge {
    
    
    var $secondaryKeys = array();
    var $globalKeys = array();    
    var $attributeDefinitions = array();
    var $throughput = array();
    
    /**
     * 
     * @param type $name
     */
    function _create_database()
    {
        echo "This function is not supported by DynamoDB";
    }
    /**
     * This method creates a table based on 3 elements that you should previouslly
     * to set the values for the keys array , the attributes definitions and the throughtput
     * @param type $tableName
     */
    function _create_table($tableName)
    {   $this->_reset();
        $db = $this->db->db_connect();
        if(!$this->db->tableExist($tableName)){
            $db->createTable(array(
                'TableName' => $tableName,
                'AttributeDefinitions' => $this->attributeDefinitions,
                'KeySchema' => $this->keys,
                'ProvisionedThroughput' =>$this->throughput)
           );
            //echo "Cree la tabla";
        }else{
            echo("The table is already created");
        }         
    }
    
    /**
     * 
     * 
     * @param array $arrayKeys  set of key with its dataTypes, names, and keyTypes
     * @return type
     */
    function add_SchemaKeys($arrayKeys)
    {
        $keySchema = array();
        $definitions= array();
            if(is_array($arrayKeys) )
            {              
                foreach ($arrayKeys as $key ) {
                    if($key instanceof CI_Key)
                    {
                        $keySchema[] = array("AttributeName" => $key->attributeName,
                                            "KeyType" => $key->KeyType);
                        $definitions[] = array("AttributeName" => $key->attributeName,
                                            "AttributeType" => $key->attributeDataType);
                    }
                }
                $this->attributeDefinitions = $definitions;
                $this->keys = $keySchema;
            }else 
            {
                return ;            
            }
    }
    
    function defineThroughput($readCapacityUnits, $writeCapacityUnits){// ProvisionedThroughput is required
        if(isset($readCapacityUnits) OR isset($writeCapacityUnits) AND ($readCapacityUnits!=0 and $writeCapacityUnits!=0))
        {
            $this->throughput = array("ReadCapacityUnits" => $readCapacityUnits,
                                        "WriteCapacityUnits"  => $writeCapacityUnits);
        }else{
            echo ("You must define the throughput");
        }
    }
    
    function add_GlobalSecondaryIndexes(){
       
    }
    
     function add_SecondaryLocalKeys(){
       
    }
            
    
  /**
   * This method deletes a table is the table exist
   * @param string $tableName name of the table
   */
    function drop_table($tableName)
    {
         if($this->db->tableExist($tableName)){             
             if($this->db->dynamoClient==null)
             {$this->db->db_connect();}
             $this->db->dynamoClient->deleteTable(array(
                'TableName' => $tableName
                 ));
         }else{
             echo("Table doesn't exits");
         }
    }
    
    /**
     * 
     * @param type $tableName ,   name for the table (required)
     * @param type $readThroughput , (Optional) new value for the  throwghput
     * @param type $writeThroughput, (Optional) new value for the  throwghput
     * @param type $globalIndexes, (Optional) array new globalIndex
     */
    function alter_table($tableName , $readThrowgput=null, $writeThrowgput=null, $globalIndexes =null)
    {
        $db = $this->db->db_connect();
        if($this->db->tableExist($tableName)){
            $array = array( 'TableName' => $tableName);
            if($readThrowgput!=null and $writeThrowgput!=null and is_int($readThrowgput) and is_int($writeThrowgput) ){
                $array["ProvisionedThroughput"]= array(
                                                        'ReadCapacityUnits' => $readThrowgput,              
                                                        'WriteCapacityUnits' => $writeThrowgput);                        
            }
            if($globalIndexes!=null and is_array($globalIndexes))
            {
                 $array["GlobalSecondaryIndexUpdates"]= $globalIndexes;
            }
           // var_dump($array);
            $db->updateTable($array);
         
        }else{
            echo("The table is not created");
        }   
    }
        
        
        /**
	 * Rename a table
	 *
	 * Generates a platform-specific query so that a table can be renamed
	 *
	 * @access	private
	 * @param	string	the old table name
	 * @param	string	the new table name
	 * @return	string
	 */
	function _rename_table($table_name, $new_table_name)
	{
		//not implemented in dynamo
	}
        
        /**
	 * Reset
	 *
	 * Resets table creation vars
	 *
	 * @access	private
	 * @return	void
	 */
	function _reset()
	{
		$this->fields		= array();
		$this->keys			= array();
		$this->primary_keys	= array();
	}
}

