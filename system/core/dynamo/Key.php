<?php


/**
 * Description of Key
 * This class works with the amazon aws driver.
 *
 * @author AndresHerrera
 */
class CI_Key {
    var $KeyType = "";//HASH OR RANGE
    var $attributeName = "";
    var $attributeDataType = "";
    var $value=null;
    
    public function __construct($name, $type = FALSE, $dataType = "S") {
        $this->attributeDataType = $dataType;
        if($name!== '')
        {
            $this->attributeName = $name;
            if($type === FALSE)
            {
                $this->KeyType =  "HASH";
            }else{
                $this->KeyType =  "RANGE";
            }
        }else{
              show_error('Attribute Name information is required.');
        }        
    }
    
    
}
