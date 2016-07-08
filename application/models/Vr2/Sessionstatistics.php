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
include_once 'BaseStatistics.php';
class Sessionstatistics extends BaseStatistics {

   
    //private $otherdb;
    
    private $separador ="-";
    /**
    * Metodo constructor del modelo.
    */
    function __construct()
    {
      parent::__construct();
      $this->otherdb = $this->load->database('impala', TRUE); 
     /* $this->otherdb->query("refresh dailyuserdata");
      $this->otherdb->query("refresh sessions");
      $this->otherdb->query("refresh downloads");
      $this->otherdb->query("refresh screen");*/
    }
    
    /**
     * Hace un coteo de sesiones basado en el idioma del usuario que visito la app.
     * @param String $idApp Nombre de la aplicacion.
     * @param string $initialDate fecha inicial
     * @param String $finalDate fecha final
     * @return array datos en forma de tabla con las sesiones y sus respectivos idiomas.
     */
    public function getSessionsByLanguage($idApp, $initialDate, $finalDate){
       @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
       @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
        $this->otherdb->select("systemlanguage, SUM(sessions) as sesiones");
        $this->otherdb->from("dailyuserdata");
        $this->otherdb->where("id_app", $idApp);
        $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
        $this->otherdb->group_by(" systemlanguage ");
        
        /*/$query = "select systemlanguage, SUM(sessions) as sesiones from dailyuserdata where id_app='$idApp' 
            and 
             ((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                    (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))
        group by systemlanguage";*/
        //$result = $this->otherdb->query($query);  
        $this->addSegmentQuery($this->otherdb);
        $result = $this->otherdb->get(); 
        //print_r($result);
        //$titles = array("UsuariosNuevos");   
        $labels =  $result->result_array();
        return $labels;
    }
    
    /**
     * Obtiene un conteo de  sesiones basados en La marca del Dispositivo
     * @param String $idApp Nombre de la aplicacion.
     * @param string $initialDate fecha inicial
     * @param String $finalDate fecha final
     * @return array datos en forma de tabla con el coteo de sesiones por marca de dispositivo.
     */
    public function getSessionsByMB($idApp, $initialDate, $finalDate){
       @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
       @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
        
        $this->otherdb->select(" model, SUM(sessions) as sesiones ");
        $this->otherdb->from("dailyuserdata");
        $this->otherdb->where("id_app", $idApp);
        $this->otherdb->where(" ((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
        $this->otherdb->group_by(" model ");
        
        /*$query = "select model, SUM(sessions) as sesiones from dailyuserdata where id_app='$idApp' 
            and  ((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                 (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))
        group by model";*/
       
        //$result = $this->otherdb->query($query);
        //print_r($result);
        $this->addSegmentQuery($this->otherdb);
        $result = $this->otherdb->get(); 
        //$titles = array("UsuariosNuevos");   
        $labels =  $result->result_array();
        return $labels;
    }
    /**
     * Obtiene un coteo de sesiones basados en el Sistema operativo del dispositivo.
     * @param String $idApp Nombre de la aplicacion.
     * @param string $initialDate fecha inicial
     * @param String $finalDate fecha final
     * @return array datos en forma de tabla con el coteo de sesiones por sistema operativo.
     */
    public function getSessionsByOS($idApp, $initialDate, $finalDate){
       @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
       @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
        $this->otherdb->select("  platformname , SUM(sessions) as sesiones  ");
        $this->otherdb->from("dailyuserdata");
        $this->otherdb->where("id_app", $idApp);
        $this->otherdb->where(" ((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
        $this->otherdb->group_by(" platformname ");       
        $result = $this->otherdb->get(); 
        $labels =  $result->result_array();
        return $labels;
    }
  
}
?>

