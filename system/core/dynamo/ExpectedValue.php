<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ExpectedValue
 *
 * @author AHerrera
 */
define("EQUALS","EQ");
define("NOT_EQUALS","NE");
define("IN","IN");
define("LESS_EQ","LE");
define("LESS_THAN","LT");
define("GREATER_THAN","GT");
define("GREATEREQ","GE");
define("BETWEEN","BETWEEN");
define("NOT_NULL","NOT_NULL");
define("NULL","NULL");
define("CONTAINS","CONTAINS");
define("NOT_CONTAINS","NOT_CONTAINS");
define("BEGINS_WITH","BEGINS_WITH");
 
class CI_ExpectedValue {
    
    var $keyName;
    var $comparisonOperator;
    var $attributeValueList;
    public function __construct($keyName, $comparisonOperator, $AttributeValueList ) {
        $this->keyName =  $keyName;
        $this->comparisonOperator = $comparisonOperator;
        $this->attributeValueList = $AttributeValueList;
    }
}
