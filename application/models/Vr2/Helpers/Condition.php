<?php 



 class Condition {
	
	var $attribute ;
	var $condition ;
	var $expectedValue ;
	var $secondexpectedValue ;
	var $dataType;

	function __construct($attribute, $condition, $dataType, $expectedValue, $expectedValue2=null){
		$this->attribute = $attribute;
		$this->condition = $condition;
		$this->expectedValue =  $expectedValue;
		$this->dataType = $dataType;
		$this->secondexpectedValue =  $expectedValue2;
	}


	



}

 ?>