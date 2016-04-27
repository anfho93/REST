<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
*
* Clase que representa un Modelo para Enlazar las Estadisticas de las aplicaciones.
*
* Esta clase mapea la tabla ethas_applications de  la base de datos no relacional de DynamoDB.
*
* @author Andres Felipe Herrera <anfho93@gmail.com>
*  @version 1.1
*/
class Sessionstatistics extends BaseStatistics {

   
    //private $otherdb;
    
    private $separador ="-";
    /**
    * Metodo constructor del modelo.
    */
    function __construct()
    {
      parent::__construct();
      /*$this->otherdb = $this->load->database('impala', TRUE); 
      $this->otherdb->query("refresh dailyuserdata");
      $this->otherdb->query("refresh sessions");
      $this->otherdb->query("refresh downloads");
      $this->otherdb->query("refresh screen");*/
      
    }
    
    
    public function getSessionsByLanguage($idApp, $initialDate, $finalDate){
       @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
       @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
       
        $query = "select systemlanguage, SUM(sessions) from dailyuserdata where id_app='546681e01f5c56.75661540' 
            and ((day >=$d1 and day <=31 and month =$m1 and year = $y1 ) or (day >=01 and day <=$d2 and month =$m2 and year = $y2 ))
        group by systemlanguage";
       
        $result = $this->otherdb->query($query);
        //print_r($result);
        $titles = array("UsuariosNuevos");   
        $labels = $this->prepareResultSet($titles, $initialDate, $finalDate, $result->result_array());
        return $labels;
    }
  
}
?>

