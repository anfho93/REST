<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';

class Downloads extends REST_Controller{
    
    public function __construct() {
        parent::__construct();        
        $methodname = strtolower("index_".$this->request->method);
        if(method_exists($this, $methodname)){
            $this->$methodname();
        }
    }
    
    private function _pre_get(){
        $segmento = $this->uri->segment(4);
        return $segmento;
    }
    public function index_get()
    {
       
        if($this->_pre_get()!=null)
        {
            switch ($this->_pre_get())
            {
                case "statistics":
                    $this->statistics();
                    return;
                default :
                {
                   echo "default";
                }
                break;                
            }
        }else{
            echo "asdasd";
        }
      
    }

    public function index_post()
    {
     echo "soy post" ;
    }
    
    public function index_put(){
        echo "soy put";
    }
    
    public function index_delete(){
        
    }
    
    private function statistics(){
        echo "estas son als estadisticas";
    }
}
