<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once BASEPATH . 'libraries/aws/aws-autoloader.php';
use Aws\DynamoDb\Iterator\ItemIterator;
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
class Appstatistics extends BaseStatistics {

    /**
    *@var string  Variable que representa el HashKey de la tabla id_app
    */
    var $hashname = "id_app";
    /**
    * @var string Variable que representa el RangeKey de la tabla
    */
    var $rangename = "timestamp";
    /**
    *@var string nombre de la tabla
    */
    var $tablename = "";
    /**
    * @var Array utilizado para almacenas valores de las llaves.
    */
    var $keys = array(); 

    /**
    *@var Array utilizado para almacenas valores de los atributos a agregar a un Item de la tabla..
    */   
    var $attributes = array();
    
    //private $otherdb;
    
    private $separador ="-";
    /**
    * Metodo constructor del modelo.
    */
    function __construct() 
    {
      parent::__construct();
      $this->otherdb = $this->load->database('impala', TRUE); 
     // 
      //S$this->otherdb->query("invalidate metadata;");
     /* 
      $this->otherdb->query("refresh sessions");
      $this->otherdb->query("refresh downloads");
      $this->otherdb->query("refresh screen");*/
      
    }
   
      /**
    * Esta funcion determina los usuarios nuevos de una aplicacion en un dia determinado.
    * basandose en la fecha de descarga y reporta esto en las estadisticas de esta aplicacion.
    *
    * @param string  $idApp, identificador de la aplicacion que se analizara.
    * @param String $initialDate fecha en formato yyyy-MM-dd
    * @param String $finalDate fecha en formato yyyy-MM-dd
    * @return array respuesta con los nuevos usuarios durante esas fechas.
    */
    public function getNewUsers($idApp, $initialDate, $finalDate){
       @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
       @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
        $this->db->select('year, month, day , count(distinct(iddevice)) as UsuariosNuevos ');
        $this->db->from('ethas_downloads');
        $this->db->where( "id_app='$idApp' and "
                                . "((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                             (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))
                                ");
        $this->addSegmentQuery($this->db);
        $this->db->group_by('year, month, day ');
        $result = $this->db->get();
   
        $titles = array("UsuariosNuevos");   
        $labels = $this->prepareResultSet($titles, $initialDate, $finalDate, $result->result_array());
        return $labels;
    }
    

       /**
     * Obtiene la cantidad total de usuarios nuevos durante una fecha determinada.
     * @param string $idApp id de la aplicacion a analizar
     * @param String $initialDate fecha en formato yyyy-MM-dd
     * @param String $finalDate fecha en formato yyyy-MM-dd
     * @return int cantidad de usuarios nuevos
     */
    function getTotalNewUsers($idApp,  $initialDate=null, $finalDate=null){
         if($initialDate==null || $finalDate==null){
           $this->db->select(' count(distinct iddevice) as usuarios ');
           $this->db->from("ethas_downloads");
           $this->db->where("id_app",$idApp);
           $this->addSegmentQuery($this->otherdb);
          
        }else{
           @list($d1, $m1, $y1) = explode( $this->separador, $initialDate,3);
           @list($d2, $m2, $y2) = explode( $this->separador, $finalDate, 3);
           //muestra los usuarios activos por dia
           $c=$this->getConector($y1, $y2);
           $this->db->select(' count(id_download) as usuarios ');
           $this->db->from("ethas_downloads as downloads");
           $this->db->where("id_app",$idApp);
           $this->db->where("((downloads.year = $y1 and ( $m1 < downloads.month or ($m1=downloads.month and downloads.day >= $d1 and downloads.day <= 31 )))
            $c 
            (downloads.year = $y2 and ( $m2 > downloads.month or ($m2=downloads.month and downloads.day >= 01 and $d2 >= downloads.day ))))");
           $this->addSegmentQuery($this->db);
           
        }
        
        $result = $this->otherdb->get();
        return $result->first_row()->usuarios;
    }
        /**
        * Permite determinar la cantidad de pantallas vistas por sesion.
        * @param string  $idAppp identificador de la aplicacion que se analizara.
        * @param String $initialDate fecha en formato yyyy-MM-dd
        * @param String $finalDate fecha en formato yyyy-MM-dd
        *  @return array Datos listos para ser graficados por un google chart.
        */
       function getUserInteraction($idApp, $initialDate, $finalDate){
           //descargar la cantidad de sesiones diarias.
        @list($d1, $m1, $y1) = explode( $this->separador, $initialDate,3);
        @list($d2, $m2, $y2) = explode( $this->separador, $finalDate, 3);
        $c=$this->getConector($y1, $y2);
        $this->otherdb->select("primerset.day as day, primerset.month as month, primerset.year as year, (SUM(segundoset.pantalla) / SUM(primerset.sesions)) as promedio");
        $this->otherdb->from("( select day, year, month , 
                      sum(sessions) as sesions from dailyuserdata 
                      where id_app = '$idApp' and
                          
                       ((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                    (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))
                    
                      group by day, year, month 
                    ) as primerset                                       
                    ");
        $this->otherdb->join("(
                      select day, year, month , 
                      count(screen) as pantalla from screen 
                      where id_app = '$idApp' and
                          
                       ((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                    (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))

                      group by day, year, month 

                    ) as segundoset", "primerset.day = segundoset.day and  primerset.month = segundoset.month and primerset.year = segundoset.year");
        $this->otherdb->group_by(array("primerset.day", "primerset.month", "primerset.year"));
        $this->otherdb->order_by("day, month, year");
        $this->addSegmentQuery($this->otherdb);
           
            $result =  $this->otherdb->get();
            $titles = array("promedio");   
            $labels = $this->prepareResultSet($titles, $initialDate, $finalDate, $result->result_array());
            return $labels;  
           
    }
    
    /**
     * Metodo quepermite obtener un consolidado de la cantidad de usuarios
     * y de sesiones durante una fecha determinada.
     * @param string $idApp id de la aplicacion a analizar
     * @param String $initialDate fecha en formato yyyy-MM-dd
     * @param String $finalDate fecha en formato yyyy-MM-dd
     * @return array Conjunto de resultados de usuarios, sesiones y promedio de ambos
     * 
     */     
    function getStatistics($idApp, $initialDate, $finalDate){
        $usuarios =  $this->getTotalUsers($idApp, $initialDate, $finalDate);
        $sesiones =  $this->getTotalSessions($idApp, $initialDate,$finalDate);
        if($sesiones == null)
            $sesiones = 0;
        if($usuarios == 0)
        {$average = 0;}
        else
        {$average = $sesiones / $usuarios;}
        $result = array("users"=>$usuarios, "sessions" => $sesiones, "average"=>$average);         
        return $result;         
    } 
    
    /**
     * Permite obtener los usuarios activos de una aplicacion durante un tiempo determinado.
     * @param string $idApp id de la aplicacion a analizar
     * @param String $initialDate fecha en formato yyyy-MM-dd
     * @param String $finalDate fecha en formato yyyy-MM-dd
     * @return array Conjunto de usuarios activos por cada dia analizado
     */
    function getActiveUsers($idApp, $initialDate, $finalDate){
        $usuariosActivos = $this->activeUsers($idApp, $initialDate, $finalDate);
        $titles = array("UsuariosActivos");        
        $labels = $this->prepareResultSet($titles, $initialDate, $finalDate, $usuariosActivos);
        return $labels;
    }
    
    /**
     * Calcula la cantidad de usuarios de una aplicacion basado en una fecha
     * @param string $idApp id de la aplicacion a analizar
     * @param String $initialDate fecha en formato yyyy-MM-dd
     * @param String $finalDate fecha en formato yyyy-MM-dd
     * @return int cantidad de usuarios
     */
    function getTotalUsers($idApp,  $initialDate, $finalDate){
      
           @list($d1, $m1, $y1) = explode( $this->separador, $initialDate,3);
           @list($d2, $m2, $y2) = explode( $this->separador, $finalDate, 3);
           $c=$this->getConector($y1, $y2);
           $this->otherdb->select("count(distinct dailyuserdata.id_download) as usuarios ");
           $this->otherdb->from("dailyuserdata ");
           $this->otherdb->where("dailyuserdata.id_app", $idApp);
           $this->otherdb->where("((dailyuserdata.year = $y1 and ( $m1 < month or ($m1=dailyuserdata.month and dailyuserdata.day >= $d1 and dailyuserdata.day <= 31 ) ))  $c 
                                (dailyuserdata.year = $y2 and ( $m2 > dailyuserdata.month or ($m2=dailyuserdata.month and dailyuserdata.day >= 01 and $d2 >= dailyuserdata.day ))))");
  
        $this->addSegmentQuery($this->otherdb);
        $result = $this->otherdb->get();
        return $result->first_row()->usuarios;
    }
    
 
    /**
     * Total de sesiones de una aplicacion durante un rango de fechas
     * @param string $idApp id de la aplicacion a analizar
     * @param String $initialDate fecha en formato yyyy-MM-dd
     * @param String $finalDate fecha en formato yyyy-MM-dd
     * @return int cantidad de sesiones
     */
    function getTotalSessions($idApp, $initialDate, $finalDate){
    
           @list($d1, $m1, $y1) = explode( $this->separador, $initialDate,3);
           @list($d2, $m2, $y2) = explode( $this->separador, $finalDate, 3);
           $this->otherdb->select(" SUM(dailyuserdata.sessions) as Sesiones ");
           $this->otherdb->from("dailyuserdata");
           $this->otherdb->where("id_app", $idApp);
           $c=$this->getConector($y1, $y2);
           $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                    (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
         $this->addSegmentQuery($this->otherdb);
        $result = $this->otherdb->get();
       return $result->first_row()->sesiones; 
    }
    /**
     * Esta funcion permite obtener los usuarios activosen un rango de fechas
     * @param String  $idApp Variable con el Id de la aplicacion a la cual obtener los datos
     * @param String  $initialDate fecha inicial en el formato d-m-Y
     * @param String  $finalDate fecha inicial en el formato d-m-Y
     * @return array  Conjunto de usuarios activos indexados por fecha
     */
    function activeUsers($idApp, $initialDate, $finalDate){
       @list($d1, $m1, $y1) = explode( $this->separador, $initialDate,3);
       @list($d2, $m2, $y2) = explode( $this->separador, $finalDate, 3);
        $c=$this->getConector($y1, $y2);
        //$this->otherdb->query("refresh dailyuserdata");
        $this->otherdb->select(" id_app, year, month, day , count( distinct dailyuserdata.id_download) as UsuariosActivos  ");
        $this->otherdb->from("dailyuserdata");
        $this->otherdb->where("id_app", $idApp);
        $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
        $this->otherdb->group_by("id_app, year, month, day ");
      
        $this->addSegmentQuery($this->otherdb);
        $result =  $this->otherdb->get();
        return $result->result_array();
    }
     
    /**
     * Permite obtener los usuarios que iniciaron sesion almenos una vez.
     * @param String  $idApp Variable con el Id de la aplicacion a la cual obtener los datos
     * @param String  $initialDate fecha inicial en el formato d-m-Y
     * @param String  $finalDate fecha inicial en el formato d-m-Y
     * @return array  usuarios que iniciaron sesion almenos una vez indexados por fecha
     */
    function  getUsers($idApp, $initialDate, $finalDate){
        @list($d1, $m1, $y1) = explode( $this->separador, $initialDate,3);
        @list($d2, $m2, $y2) = explode( $this->separador, $finalDate, 3);
        $c=$this->getConector($y1, $y2);
        $this->otherdb->select(" year, month, day ,  count(distinct dailyuserdata.id_download) as Users   ");
        $this->otherdb->from("dailyuserdata");
        $this->otherdb->where("id_app", $idApp);
        $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
        $this->otherdb->group_by("year, month, day ");
        $this->addSegmentQuery($this->otherdb);
        $result = $this->otherdb->get();
        $titles =array("Users");
        $labels = $this->prepareResultSet($titles, $initialDate, $finalDate, $result->result_array()); 
        return $labels;
    }




    /**
     * Funcion que permite ver los datos de la cantidad de pantallas visitadas en un rango de dias.
     * @param string $idApp  identificador de la aplicacion.
     * @param string $initialDate  fecha inicial del rango
     * @param string $finalDate  fecha final del rango
     * @return Array Listado de  dias con su respectivo conteo de pantallas
     */    
    function getGeneralScreensData($idApp, $initialDate, $finalDate){
       @list($d1, $m1, $y1) = explode( $this->separador, $initialDate,3);
       @list($d2, $m2, $y2) = explode( $this->separador, $finalDate, 3);
        $c = $this->getConector($y1, $y2);
        $db = null;
        if($this->getSegmentQuery()!=null){ 
            $db = $this->otherdb;
            $db->select(" screen,  count(screen) as pantallas");
        }
        else{
          $db = $this->db;
          $db->select(" screen,  sum(cant) as pantallas");
        }
        $db->from("screen");
        $db->where("id_app", $idApp);
        $db->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
        $db->group_by("screen");
        $db->order_by("pantallas");        
        $this->addSegmentQuery($db);
        $result = $db->get();       
        return $result->result_array();
    }
     /**
     * Funcion que permite ver los datos de la cantidad de sessiones  en un rango de dias.
     * @param string $idApp  identificador de la aplicacion.
     * @param string $initialDate  fecha inicial del rango
     * @param string $finalDate  fecha final del rango
      * @return Array Listado de  dias con su respectivo conteo de sesiones
     */
    function getGeneralSessionData($idApp, $initialDate, $finalDate)
    {
       @list($d1, $m1, $y1) = explode( $this->separador, $initialDate,3);
       @list($d2, $m2, $y2) = explode( $this->separador, $finalDate, 3);
        $c=$this->getConector($y1, $y2);
         $this->otherdb->select("year, month, day,  count(id_session) as Sessions");
        $this->otherdb->from("sessions");
        $this->otherdb->where("id_app", $idApp);
        $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
        $this->otherdb->group_by(" year, month, day ");
         
        $this->addSegmentQuery($this->otherdb);
        $result = $this->otherdb->get();  
        $titles =array("Sessions");
        $labels = $this->prepareResultSet($titles, $initialDate, $finalDate, $result->result_array());
       return $labels; 
    }
    
    
    
    /**
     * permite hacer un conteo de las pantallas visitadas durante un rango de tiempo
     * @param string $idApp  identificador de la aplicacion.
     * @param string $initialDate  fecha inicial del rango
     * @param string $finalDate  fecha final del rango
     * @return int cantidad de pantallas totales visitadas.
     */
    function countScreens($idApp, $initialDate, $finalDate){
       @list($d1, $m1, $y1) = explode( $this->separador, $initialDate,3);
       @list($d2, $m2, $y2) = explode( $this->separador, $finalDate, 3);
        $c=$this->getConector($y1, $y2);
         $db=null;
        if($this->getSegmentQuery()!=null)
        { 
            $db = $this->otherdb;
            $db->select("count(screen) as pantallas ");
        }
        else{
          //  echo "entre";
          $db = $this->db;
          $db->select(" sum(cant) as pantallas");
        }
        
         $db->from("screen");
         $db->where("id_app", $idApp);
         $db->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                   (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
        
        $this->addSegmentQuery($db);
        $result =  $db->get(); 
        return $result->first_row()->pantallas;
    }


 

    /**
    * Funcion que obtiene el promedio de sessiones por todos los usuarios de la aplicacion
    *
    * Para esto se obtiene primero la cantidad de descargas que posee la aplicación.
    * luego se hace una busqueda de todas las estadisticas diarias generadas pra esta aplicacion.
    * se suma la cantidad de sesiones diarias obtenidas y se dividen entre la cantidad de usuarios 
    * @param string  $idApp identificador de la aplicacion.
    * @param string $initialDate fecha inicial bajo la cual se realizara la busqueda.
    * @param string $finalDate fecha final bajo la cual se realizara la busqueda.
    * @return Array String Contiene los usuarios y sesiones por cada usuario.
    */
    function getAverageUserSession($idApp, $initialDate, $finalDate ){
       //formato de las fecha  dd-mm-yyyy
       @list($d1, $m1, $y1) = explode( $this->separador, $initialDate,3);
       @list($d2, $m2, $y2) = explode( $this->separador, $finalDate, 3);
       //validar datos enviados
        $c=$this->getConector($y1, $y2);
        $this->otherdb->select("year, month, day, sum(sessions) as sesiones, count(distinct id_download) as usuarios");
        $this->otherdb->from("dailyuserdata");
        $this->otherdb->where("id_app", $idApp);
        $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
        $this->otherdb->group_by("year, month, day");
       
        $this->addSegmentQuery($this->otherdb);
        $result = $this->otherdb->get();
       return $result->result_array();
         
    }


  

    /**
    * Esta funcion genera un conteo de sessiones durante un dia terminado para una aplicacion.
    * para esto se analiza las sesiones de la aplicacion para que puedan ser procesadas.
    * @param string  $idApp , identificador de la aplicacion.
    * @param string $initialDate , fecha inicial bajo la cual se realizara la busqueda.
    * @param string $finalDate , fecha final bajo la cual se realizara la busqueda.
    * @return Array Resultado de la consulta
    */
    function  generateSessionCount($idApp, $initialDate, $finalDate)
    {
       @list($d1, $m1, $y1) = explode( $this->separador, $initialDate, 3);
       @list($d2, $m2, $y2) = explode( $this->separador, $finalDate,   3);
        $c=$this->getConector($y1, $y2);
        $this->otherdb->select("year, month, day , SUM(sessions) as sesiones ");
        $this->otherdb->from("dailyuserdata");
        $this->otherdb->where("id_app", $idApp);
        $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                    (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))");
        $this->otherdb->group_by("year, month, day");
       
        $this->addSegmentQuery($this->otherdb);
       $result = $this->otherdb->get();
       
       return $result->result_array();
    }

     
  
}
?>

