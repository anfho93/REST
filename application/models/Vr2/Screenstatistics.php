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
class Screenstatistics extends BaseStatistics {

   
    //private $otherdb;
    
    private $separador ="-";
    /**
    * Metodo constructor del modelo.
    */
    function __construct()
    {
      parent::__construct(); 
      
      //$this->otherdb->query("invalidate metadata");
      
    }
    
    
    /**
     * Cantidad de pantallas visitadas durante el dia en la aplicacion
     * @param string $idApp  identificador de la aplicacion.
     * @param string $initialDate  fecha inicial del rango
     * @param string $finalDate  fecha final del rango
     * @return array Cantidad de pantallas visitadas indexadas por fecha.
     */
    function getScreensData($idApp, $initialDate, $finalDate){
       @list($d1, $m1, $y1) = explode( $this->separador, $initialDate,3);
       @list($d2, $m2, $y2) = explode( $this->separador, $finalDate, 3);
        $c=$this->getConector($y1, $y2);
        $db = null;
        $segmentquery = $this->getSegmentQuery();
        if($this->getSegmentQuery()==null){
           // echo "entre";
            $db = $this->db;  
            $db->select("year, month, day , sum(cant) as pantallas ");
            
        }
        else{
            $db = $this->otherdb;        
            $db->select("screen.year, month, screen.day , count(screen) as pantallas ");
            
        }
        //$db->select("screen.year, screen.month, screen.day , count(screen) as pantallas ");
        $db->from("screen");
        $db->where("id_app", $idApp);
        $db->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                      (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
        $db->group_by(" year, month, day ");
        if($this->getSegmentQuery()!=null)
        {   
            $db->join("(select id_download from downloads where downloads.id_app='$idApp' and $segmentquery  group by id_download) as tabla ", " screen.id_download = tabla.id_download");
        }
        $result = $db->get(); 
        $titles = array("Pantallas");        
        $labels = $this->prepareResultSet($titles, $initialDate, $finalDate,  $result->result_array());
        return $labels;
    }
    
    
    /**
     * Metodo que permite ver un conteo de las pantallas visitadas
     * por los usuarios durante un rango de fechas en una aplicacion.
     * @param String $idApp Nombre de la aplicacion.
     * @param string $initialDate fecha inicial
     * @param String $finalDate fecha final
     */
    public function getScreenViews($idApp, $initialDate, $finalDate){
       @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
       @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
        $segmentquery = $this->getSegmentQuery();
        if($segmentquery!=null){
            $this->otherdb->select(" screen.year, screen.month, screen.day, count(screen) as Pantallas");
            $this->otherdb->from("screen");
            $this->otherdb->join("(select id_download from downloads where id_app='$idApp' and $segmentquery  group by id_download) as tabla ", " screen.id_download = tabla.id_download");
            $this->otherdb->where("id_app", $idApp);
            $this->otherdb->where("((screen.year = $y1 and ( $m1 < screen.month or ($m1=screen.month and screen.day >= $d1 and screen.day <= 31 ) ))  $c 
                                    (screen.year = $y2 and ( $m2 > screen.month or ($m2=screen.month and screen.day >= 01 and $d2 >= screen.day ))))");
            $this->otherdb->group_by("screen.year, screen.month, screen.day");
            
        }else{            
            $this->otherdb->select(" year, month, day, count(screen) as Pantallas");
            $this->otherdb->from("screen");
            $this->otherdb->where("id_app", $idApp);
            $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                    (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
            $this->otherdb->group_by("year, month, day");
          
        }
        $result = $this->otherdb->get();
        $titles = array("Pantallas");   
        return  $this->prepareResultSet($titles, $initialDate, $finalDate, $result->result_array()); 
            
               
    }
     /**
     * Metodo que permite ver un conteo de visitas de una pantalla especifica
     * por los usuarios durante un rango de fechas en una aplicacion.
     * @param String $idApp Nombre de la aplicacion.
     * @param string $initialDate fecha inicial
     * @param String $finalDate fecha final
     * @param String  $screen Nombre de  la pantalla
     */
    public function getSpecificScreenData($idApp, $initialDate, $finalDate, $screen){
       @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
       @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
       $name=str_replace(' ', '', $screen);
        $c=$this->getConector($y1, $y2);
        $segmentquery = $this->getSegmentQuery();
        
           $this->otherdb->select("year, month, day, count(screen) as $name");
           $this->otherdb->from("screen");
         
           $this->otherdb->where("id_app", $idApp);
           $this->otherdb->where("screen", $screen);
           $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                   (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
           $this->otherdb->group_by("year, month, day");
         if( $segmentquery!=null){
               $this->otherdb->join("(select id_download from downloads where id_app='$idApp' and $segmentquery  group by id_download) as tabla ", " screen.id_download = tabla.id_download");
         }
        $result = $this->otherdb->get(); 
        $titles = array($name);   
        
       // $labels =  $result->result_array();
        return  $this->prepareResultSet($titles, $initialDate, $finalDate, $result->result_array());
    }
    
    
     /**
     * Metodo que permite ver un promedio de las pantallas visitadas por sesion
     * de los usuarios durante un rango de fechas en una aplicacion.
     * @param String $idApp Nombre de la aplicacion.
     * @param string $initialDate fecha inicial
     * @param String $finalDate fecha final
     */
    public function getInteraction($idApp, $initialDate, $finalDate){
         $this->otherdb->query("refresh sessions");
        @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
        @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
        $segmentquery = $this->getSegmentQuery();
        $this->otherdb->select(" year, month, day, count(screen) as Pantallas");
        $this->otherdb->from("screen");
        $this->otherdb->where("id_app", $idApp);
        $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
        $this->otherdb->group_by("year, month, day");
        if( $segmentquery!=null){
          $this->otherdb->join("(select id_download from downloads where id_app='$idApp' and $segmentquery  group by id_download) as tabla ", " screen.id_download = tabla.id_download");
        }
        $result = $this->otherdb->get(); 
        
        $titles = array("Pantallas");
        
        $datos =  $this->prepareResultSet($titles, $initialDate, $finalDate, $result->result_array());
        
        $this->otherdb->select(" year, month, day, count(id_session) as Sesiones");
        $this->otherdb->from("sessions");
        $this->otherdb->where("id_app", $idApp);
        $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
        $this->otherdb->group_by("year, month, day");
        if( $segmentquery!=null){
          $this->otherdb->join("(select id_download from downloads where id_app='$idApp' and $segmentquery  group by id_download) as tabla ", " sessions.id_download = tabla.id_download");
        }
        $result2 = $this->otherdb->get();    
        for($i = 0; $i < count($datos);$i++ ){
            foreach ($result2->result_array() as $row) {
                $day =$row['day'];
                $month = $row['month'];
                if($day<10)
                    $day = '0'.$day;
                if($month<10)
                    $month = '0'.$month;
                
                $date = $row['year']."-".$month."-".$day;
                if($datos[$i][0]== $date )
                {
                    
                    if($datos[$i][1] ==0 || $row['sesiones'] ==0){
                        $datos[$i][1] =0;
                    }
                    else{
                        $datos[$i][1] =  (double)($datos[$i][1] / $row['sesiones']);                       
                    }
                    break;
                }
            }
        }
        return ($datos);        
    }
    
    
    
  
}
?>

