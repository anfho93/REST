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
include_once 'baseStatistics.php';
class Eventstatistics extends BaseStatistics {
    private $separador ="-";
    
    /**
    * Metodo constructor del modelo.
    */
    function __construct()
    {
      parent::__construct();
      $this->otherdb = $this->load->database('impala', TRUE); 
      try{
        //$this->otherdb->query("invalidate metadata downloads");      
          $this->otherdb->query("refresh downloads");      
        if($this->otherdb->_error_number())
        {
         return;   
        }
        $this->otherdb->query("refresh default.logs");
        if($this->otherdb->_error_number())
        {
         return;   
        }
      }catch(Exception $e)
      {
          return;
      }
    }
   /**
    * Retorna un arreglo indexado por fechas  de los eventos de una aplicacion
    * @param String $idApp Nombre de la aplicacion.
     * @param string $initialDate fecha inicial
     * @param String $finalDate fecha final
    * @return array datos indexados por fechas
    */
    public function getEventsDailyData($idApp, $initialDate, $finalDate){
       @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
       @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
        if($this->getSegmentQuery()!=null)
        {          
            //$this->otherdb->query("invalidate metadata downloads");
            //$this->otherdb->query("refresh logs");            
            $this->otherdb->select(" logs.year, logs.month, logs.day, count(logs.log) as Events");
            $this->otherdb->from("logs");
            $this->otherdb->join('downloads', ' logs.id_app= downloads.id_app     and  downloads.id_download= logs.id_download');
            $this->otherdb->where("logs.id_app", $idApp);
            $this->otherdb->where("((logs.year = $y1 and ( $m1 < logs.month or ($m1= logs.month and logs.day >= $d1 and logs.day <= 31 ) ))  $c 
                                 (logs.year = $y2 and ( $m2 > logs.month or ($m2=logs.month and logs.day >= 01 and $d2 >= logs.day ))))");
            $this->otherdb->group_by("logs.year, logs.month , logs.day");
            $this->otherdb->order_by("logs.year, logs.month , logs.day");
            $this->addSegmentQuery($this->otherdb);
            $result = $this->otherdb->get(); 
        }else{
            $this->db->select(" year, month, day, SUM(events) as events");
            $this->db->from("dailylogs");
            $this->db->where("id_app", $idApp);
            $this->db->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                       (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
            $this->db->group_by("year, month , day");
            $this->db->order_by("year, month , day");
            $result = $this->db->get();          
        }       
        $titles = array("Events");   
        $labels = $this->prepareResultSet($titles, $initialDate, $finalDate, $result->result_array());
        return  $labels;
    }
    /**
    * Obtiene las catergorias de una aplicacion y la cantidad de eventos
     * registrados en un rango de tiempo
    * @param String $idApp Nombre de la aplicacion.
    * @param string $initialDate fecha inicial
    * @param String $finalDate fecha final
    * @return array tabla con las diferentes categorias y numero de eventos
    */
    public function getEventsCategories($idApp, $initialDate, $finalDate){
        @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
        @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
        if($this->getSegmentQuery()!=null)
        {
             $this->otherdb->select("category, count(id_session) as events");
             $this->otherdb->from("logs");
             $this->otherdb->where("id_app", $idApp);
             $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                  (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
             $this->otherdb->group_by("category");
             $this->addSegmentQuery($this->otherdb); 
             $result = $this->otherdb->get(); 
        }else{
             $this->db->select("category, SUM(events) as events");
             $this->db->from("dailylogs");
             $this->db->where("id_app", $idApp);
             $this->db->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                  (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
             $this->db->group_by("category");
             $result = $this->db->get(); 
        }
        return   $result->result_array();
    }
    
    
    /**
    *Obtiene los tipos de eventos de una aplicacion y la cantidad de eventos
    * registrados en un rango de tiempo 
    * @param String $idApp Nombre de la aplicacion.
    * @param string $initialDate fecha inicial
    * @param String $finalDate fecha final
    * @return array tabla con las diferentes categorias y numero de eventos
    */
    public function getTypes($idApp, $initialDate, $finalDate){
        @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
        @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
        if($this->getSegmentQuery()!=null){
             $this->otherdb->select("type , count(id_session)");
             $this->otherdb->from("logs");
             $this->otherdb->where("id_app", $idApp);
             $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                         (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
             $this->otherdb->group_by("type");
             $this->addSegmentQuery($this->otherdb);
             $result = $this->otherdb->get(); 
        }else{
            $this->db->select("type , sum(events)");
            $this->db->from("dailylogs");
            $this->db->where("id_app", $idApp);
            $this->db->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                             (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
            $this->db->group_by("type");
            $result = $this->db->get(); 
        }
        return   $result->result_array();
    }
    /**
    *Obtiene los nombres de eventos de una aplicacion y la cantidad de eventos
    * registrados en un rango de tiempo 
    * @param String $idApp Nombre de la aplicacion.
    * @param string $initialDate fecha inicial
    * @param String $finalDate fecha final
    * @return array tabla con las diferentes nombres de eventos y numero de eventos
    */
     public function getLogs($idApp, $initialDate, $finalDate){
        @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
        @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
        if($this->getSegmentQuery()!=null){
            $this->otherdb->select(" log , count(id_session)");
            $this->otherdb->from("logs");
            $this->otherdb->where("id_app", $idApp);
            $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                             (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
            $this->otherdb->group_by("log");
            $this->addSegmentQuery($this->otherdb);
            $result = $this->otherdb->get(); 
        }else{
            $this->db->select(" log , sum(events)");
            $this->db->from("dailylogs");
            $this->db->where("id_app", $idApp);
            $this->db->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                             (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
            $this->db->group_by("log"); 
            $result = $this->db->get(); 
        }
        return   $result->result_array();
    }
    /**
    *Obtiene los tipos de eventos de una categoria especifica y la cantidad de eventos
    * registrados en un rango de tiempo 
    * @param String $idApp Nombre de la aplicacion.
    * @param string $initialDate fecha inicial
    * @param String $finalDate fecha final
    * @return array tabla con las diferentes categorias y numero de eventos
    */
    public function getCategoryTypes($idApp, $initialDate, $finalDate, $categoryname){
        @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
        @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
         if($this->getSegmentQuery()!=null){
            $this->otherdb->select(" type, count(id_session) as events");
            $this->otherdb->from("logs");
            $this->otherdb->where("id_app", $idApp);
            $this->otherdb->where("category", $categoryname);
            $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                         (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
            $this->otherdb->group_by("type");
            $this->addSegmentQuery($this->otherdb);
            $result = $this->otherdb->get();  
         }else{
            $this->db->select(" type, SUM(events) as events");
            $this->db->from("dailylogs");
            $this->db->where("id_app", $idApp);
            $this->db->where("category", $categoryname);
            $this->db->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                         (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
            $this->db->group_by("type");
            $result = $this->db->get();
         }
         return   $result->result_array();
    }
    
    /**
    *Obtiene los eventos de un tipo especifico y la cantidad de eventos
    * registrados en un rango de tiempo 
    * @param String $idApp Nombre de la aplicacion.
    * @param string $initialDate fecha inicial
    * @param String $finalDate fecha final
    * @param string $category nombre de la categoria del evento,
     * @param string $type nombre del tipo de evento
    * @return array tabla con las diferentes eventos y numero de ocurrencias
    */
    public function getLogsFromCategoryTypes($idApp, $initialDate, $finalDate, $category, $type){
        @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
        @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
         if($this->getSegmentQuery()!=null){
                $this->otherdb->select("log, count(id_session) as events");
                $this->otherdb->from("logs");
                $this->otherdb->where("id_app", $idApp);
                $this->otherdb->where("category", $category);
                $this->otherdb->where("type", $type);
                $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                 (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
                $this->otherdb->group_by("log");  
                $this->addSegmentQuery($this->otherdb);
                
                $result = $this->otherdb->get(); 
        }else{
                $this->db->select("log, sum(events) as events");
                $this->db->from("dailylogs");
                $this->db->where("id_app", $idApp);
                $this->db->where("category", $category);
                $this->db->where("type", $type);
                $this->db->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                 (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
                $this->db->group_by("log");  
                
                $result = $this->db->get(); 
        }
             
        
        return   $result->result_array();
    }
 /**
    *Obtiene los valores de un evento especifico y la cantidad de eventos
    * registrados con dicho valor en un rango de tiempo 
    * @param String $idApp Nombre de la aplicacion.
    * @param string $initialDate fecha inicial
    * @param String $finalDate fecha final
    * @param string $category, nombre de la categoria del evento,
     * @param string $type nombre del tipo de evento
  * * @param string $log nombre del evento
    * @return array tabla con las diferentes eventos y numero de ocurrencias
    */
    public function getValuesFromLogs($idApp, $initialDate, $finalDate, $category, $type, $log){
        @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
        @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
        if($this->getSegmentQuery()!=null){
            $this->otherdb->select("value, count(id_session) as events");
            $this->otherdb->from("logs");
            $this->otherdb->where("id_app", $idApp);
            $this->otherdb->where("category", $category);
            $this->otherdb->where("type", $type);
            $this->otherdb->where("log", $type);
            $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                             (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
            $this->otherdb->group_by("log, value");
            $result = $this->otherdb->get(); 
        }else{
             $this->db->select("value, SUM(events) as events");
             $this->db->from("dailylogs");
             $this->db->where("id_app", $idApp);
             $this->db->where("category", $category);
             $this->db->where("type", $type);
             $this->db->where("log", $type);
             $this->db->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                         (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
             $this->db->group_by("log, value");
             $result = $this->db->get(); 
        }        
        return   $result->result_array();
    }
    
    /**
     * Permite obtener datos de los eventos generados por los 
     * usuarios basados en el nombre de una categoria
     * @param String $idApp Nombre de la aplicacion.
     * @param string $initialDate fecha inicial
     * @param String $finalDate fecha final
     * @param String $category nombre de la categoria 
     * @return array resultado de la consulta agrupado por year, month, day
     */
    public function getCategoryDailyData($idApp, $initialDate, $finalDate, $category){
        @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
        @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        
        $c=$this->getConector($y1, $y2);
        if($this->getSegmentQuery()!=null){
           $this->otherdb->select("year, month, day, category, count(id_session) as Events");
            $this->otherdb->from("logs");
            $this->otherdb->where("id_app", $idApp);
            $this->otherdb->where("category", $category);
            $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                         (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
            $this->otherdb->group_by("year, month, day, category");
            $this->otherdb->order_by(" month, day, category");
            $result = $this->otherdb->get();  
        }else{
            $this->db->select("year, month, day, category, sum(events) as events");
            $this->db->from("dailylogs");
            $this->db->where("id_app", $idApp);
            $this->db->where("category", $category);
            $this->db->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                         (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
            $this->db->group_by("year, month, day, category");
            $this->db->order_by(" month, day, category");  
            $result = $this->db->get();  
        }
        
         $titlearray = array("Events");
         return   $this->prepareResultSet($titlearray, $initialDate, $finalDate, $result->result_array());  
    }
    /**
     * Permite obtener datos de los eventos generados por los 
     * usuarios basados en el nombre de una categoria y un tipo de evento
     * @param String $idApp Nombre de la aplicacion.
     * @param string $initialDate fecha inicial
     * @param String $finalDate fecha final
     * @param String $type nombre de la categoria 
     * @param String $category nombre de la categoria 
     * @return array resultado de la consulta agrupado por year, month, day
     */
    public function getTypeDailyData($idApp, $initialDate, $finalDate, $type, $category =  null){
        @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
        @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
          if($this->getSegmentQuery()!=null){
               $this->otherdb->select("year, month, day, type, count(id_session) as events");
               $this->otherdb->from("logs");
               $this->otherdb->where("id_app", $idApp);
               $this->otherdb->where("type", $type);
               $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                    (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
                if($category != null){   
                  $this->otherdb->where("category", $category);
                }
                $this->otherdb->group_by("year, month, day, type");
                $this->otherdb->order_by("year, month, day");
                $this->addSegmentQuery($this->otherdb);
                $result = $this->otherdb->get(); 
          }else{
               $this->db->select("year, month, day, type, SUM(events) as events");
                $this->db->from("dailylogs");
                $this->db->where("id_app", $idApp);
                $this->db->where("type", $type);

                $this->db->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                        (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
              if($category != null)
                   {   
                     $this->db->where("category", $category);
                   }
                $this->db->group_by("year, month, day, type");
                $this->db->order_by("year, month, day");
                $this->addSegmentQuery($this->db);
                $result = $this->db->get(); 
          }
         $titlearray = array("Events");
         return   $this->prepareResultSet($titlearray, $initialDate, $finalDate, $result->result_array()); 
    }
    /**
     * Permite obtener datos de los eventos generados por los 
     * usuarios basados en el nombre de un evento
     * @param String $idApp Nombre de la aplicacion.
     * @param string $initialDate fecha inicial
     * @param String $finalDate fecha final
     * @param String $log nombre de la categoria 
     * @return array resultado de la consulta agrupado por year, month, day
     */
    public function getLogDailyData($idApp,  $initialDate, $finalDate, $log){
        @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
        @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
        if($this->getSegmentQuery()!=null){
            $this->otherdb->select(" year, month, day, type, count(id_session) as events");
            $this->otherdb->from("logs");
            $this->otherdb->where("id_app", $idApp);
            $this->otherdb->where("log", $log);
            $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");            
            $this->otherdb->group_by("year, month, day, type");
            $this->otherdb->order_by("month, day, type");   
            $this->addSegmentQuery($this->otherdb);
            $result = $this->otherdb->get(); 
        }else{
            $this->db->select(" year, month, day, type, SUM(events) as events");
            $this->db->from("dailylogs");
            $this->db->where("id_app", $idApp);
            $this->db->where("log", $log);
            $this->db->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");            
            $this->db->group_by("year, month, day, type");
            $this->db->order_by("month, day, type");              
            $result = $this->db->get(); 
        }
        
         $titlearray = array("Events");
         return   $this->prepareResultSet($titlearray, $initialDate, $finalDate, $result->result_array());
    }
}