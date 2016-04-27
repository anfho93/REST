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
class Userstatistics extends BaseStatistics {

   
    //private $otherdb;
    
    private $separador ="-";
    /**
    * Metodo constructor del modelo.
    */
    function __construct()
    {
      parent::__construct();
    }
    
    /**
     * Permite calcular la cantidad de usuarios nuevos por Sistema operativo
     * @param String $idApp Nombre de la aplicacion.
     * @param string $initialDate fecha inicial
     * @param String $finalDate fecha final
     * @return array tabla con los datos de cantidad de usuarios por S.O
     */
    public function getNewUsersBySO($idApp, $initialDate, $finalDate){
       @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
       @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
       
        
        $this->otherdb->select('platformname,  count(id_download) as users');
        $this->otherdb->from('downloads');
        $this->otherdb->where( "id_app='$idApp' and "
                . "((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
             (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))
                ");
        $this->addSegmentQuery($this->otherdb);
        
        $this->otherdb->group_by('platformname');
        $result = $this->otherdb->get();
        //$result = $this->otherdb->query($query);
        $labels =  $result->result_array();
        return $labels;
    }
    /**
     * Obtiene los usuarios activos basados en un idioma del Sistema operativo
     * @param String $idApp Nombre de la aplicacion.
     * @param string $initialDate fecha inicial
     * @param String $finalDate fecha final
     * @param String $lang idioma bajo el cual obtener los usuarios activos.
     * @return array conjunto de usuarios 
     */
    public function getActiveUsersByLang($idApp, $initialDate, $finalDate, $lang){
        @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
        @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
        $this->otherdb->select('year, month, day , count(dailyuserdata.id_download) as UsuariosActivos  ');
        $this->otherdb->from('dailyuserdata');
        $this->otherdb->where("id_app", $idApp);
        $this->otherdb->where("systemlanguage", $lang);
        $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))" );
        $this->otherdb->group_by("year, month, day");
        $this->otherdb->order_by("year, month, day");
       /*$query = " select year, month, day ,
                    count(dailyuserdata.id_download) as UsuariosActivos   
                    from dailyuserdata where id_app='$idApp' and systemlanguage='$lang' and
                    ((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                    (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))
                    group by year, month, day
                    order by year, month ,day";*/
        $this->addSegmentQuery($this->otherdb);
        $result = $this->otherdb->get();
        //$result = $this->otherdb->query($query);
        $titlearray = array("UsuariosActivos");
        
        return $this->prepareResultSet($titlearray, $initialDate, $finalDate,$result->result_array());
    }
    /**
     * Obtiene un conjutnos de nuevos usuarios agrupados por dias entre las fechas especificadas,
     * basado en el sistema operativo.
     * @param String $idApp Nombre de la aplicacion.
     * @param string $initialDate fecha inicial
     * @param String $finalDate fecha final
     * @param String $os nombre del sistema operativo
     * @return array conjunto de datos indexados por dia.
     */
    public function getDailyNewUsersBySO($idApp, $initialDate, $finalDate, $os){
       @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
       @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
        $this->otherdb->select('year, month, day, platformname,  count(id_download) as Users');
        $this->otherdb->from('downloads');
        $this->otherdb->where("id_app", $idApp);
        $this->otherdb->where("platformname", $os);
        $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))" );
         $this->otherdb->group_by("year, month, day, platformname");
        /* $query = " select year, month, day, platformname,  count(id_download) as Users from downloads 
        where id_app='$idApp' and platformname = '$os' and
        ((downloads.year = $y1 and ( $m1 < downloads.month or ($m1=downloads.month and downloads.day >= $d1 and downloads.day <= 31 ) ))  $c
        (downloads.year = $y2 and ( $m2 > downloads.month or ($m2=downloads.month and downloads.day >= 01 and $d2 >= downloads.day ))))
        group by year, month, day, platformname";*/
        $this->addSegmentQuery($this->otherdb);
        $result = $this->otherdb->get();
        //$result = $this->otherdb->query($query);
        $titlearray = array("Users");
        
        return $this->prepareResultSet($titlearray, $initialDate, $finalDate,$result->result_array());
    }
    /**
     * Obtiene un conjutnos de usuarios  activos agrupados por dias entre las fechas especificadas,
     * basado en la marca del dispositivo.
     * @param String $idApp Nombre de la aplicacion.
     * @param string $initialDate fecha inicial
     * @param String $finalDate fecha final
     * @param String $mb nombre de la marca.
     * @return array conjunto de datos indexados por dia.
     */
    public function getDailyUsersByMB($idApp, $initialDate, $finalDate, $mb){
       @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
       @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
       $c=$this->getConector($y1, $y2);
       $this->otherdb->select('year, month, day, platformname,  count(id_download) as Users');
       $this->otherdb->from('dailyuserdata');
       $this->otherdb->where("id_app", $idApp);
       $this->otherdb->where("model", $mb);
       $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))" );
       $this->otherdb->group_by("year, month, day, platformname");
          /*$query = " select year, month, day, platformname,  count(id_download) as Users from dailyuserdata 
            where id_app='$idApp' and model = '$mb' and
            ((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c
            (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))
            group by year, month, day, platformname";*/
        $this->addSegmentQuery($this->otherdb);
        $result = $this->otherdb->get();
        //$result = $this->otherdb->query($query);
        $titlearray = array("Users");
        
        return $this->prepareResultSet($titlearray, $initialDate, $finalDate,$result->result_array());
    }
   /**
     * Obtiene un conjutnos de usuarios activos agrupados por dias entre las fechas especificadas,
     * basado en la marca del dispositivo.
     * @param String $idApp Nombre de la aplicacion.
     * @param string $initialDate fecha inicial
     * @param String $finalDate fecha final
     * @param String $os sistema operativo del dispositivo
     * @return array conjunto de datos indexados por dia.
     */
    public function getDailyUsersBySO($idApp, $initialDate, $finalDate, $os){
        @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
        @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
        $this->otherdb->select('year, month, day, platformname,  count(id_download) as Users');
        $this->otherdb->from('dailyuserdata');
        $this->otherdb->where("id_app", $idApp);
        $this->otherdb->where("platformname", $os);
        $this->otherdb->where("((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c 
                                (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))" );
         $this->otherdb->group_by(" year, month, day, platformname");
         /*$query = " select year, month, day, platformname,  count(id_download) as Users from dailyuserdata 
            where id_app='$idApp' and platformname = '$os' and
            ((year = $y1 and ( $m1 < month or ($m1=month and day >= $d1 and day <= 31 ) ))  $c
            (year = $y2 and ( $m2 > month or ($m2=month and day >= 01 and $d2 >= day ))))
            group by year, month, day, platformname";*/
         $this->addSegmentQuery($this->otherdb);
        $result = $this->otherdb->get();
        //$result = $this->otherdb->query($query);
        $titlearray = array("Users");
        
        return $this->prepareResultSet($titlearray, $initialDate, $finalDate,$result->result_array());
    }
    
    

    /**
     * Permite identificar los usuarios que iniciaron sesion un dia determinado despues de 
     * descargar la aplicacion.
     * @param String $idApp Nombre de la aplicacion.
     * @param string $initialDate fecha inicial
     * @param String $finalDate fecha final
     * @param int $rangeOfRetention, dias a ser calculada la retencion de usuarios
     * @return array conjunto de datos indexados por fecha desde el inicio hasta el final del rango ingresado
     * como parametro.
     */
    public function getUserRetention($idapp, $initialDate, $finalDate, $rangeOfRetention){
       @list($d1, $m1, $y1) = explode( $this->separador,$initialDate,3);
       @list($d2, $m2, $y2) = explode( $this->separador,$finalDate,3);
        $c=$this->getConector($y1, $y2);
        if($this->getSegmentQuery()!=null)
        {
            $cond =  "and ".$this->getSegmentQuery();
        }else{
            $cond ="";
        }
        $this->otherdb->select('mid.y as year, mid.m as month, mid.d as day, count(download) as downloads');
        $this->otherdb->from("( select DISTINCT downloads.year as y, downloads.month as m, downloads.day as d, downloads.id_download as download from downloads inner join sessions
        on ( sessions.id_app = downloads.id_app and sessions.id_download = downloads.id_download)
        where abs(datediff(from_unixtime( downloads.downloadtime,'yyyy-MM-dd' ), from_unixtime( sessions.start_date  ,'yyyy-MM-dd' )))=$rangeOfRetention
        and downloads.id_app='$idapp'
        and  ((downloads.year = $y1 and ( $m1 < downloads.month or ($m1=downloads.month and downloads.day >= $d1 and downloads.day <= 31 ) ))  $c 
             (downloads.year = $y2 and ( $m2 > downloads.month or ($m2=downloads.month and downloads.day >= 01 and $d2 >= downloads.day ))))
                 $cond
        ) as mid");
        
        $this->otherdb->group_by("mid.y, mid.m, mid.d");
        $this->otherdb->order_by("mid.y, mid.m, mid.d");
       /*$query = "select mid.y as year, mid.m as month, mid.d as day, count(download) as downloads from ( select DISTINCT downloads.year as y, downloads.month as m, downloads.day as d, downloads.id_download as download from downloads inner join sessions
        on ( sessions.id_app = downloads.id_app and sessions.id_download = downloads.id_download)
        where abs(datediff(from_unixtime( downloads.downloadtime,'yyyy-MM-dd' ), from_unixtime( sessions.start_date  ,'yyyy-MM-dd' )))=$rangeOfRetention
        and downloads.id_app='$idapp'
        and  ((downloads.year = $y1 and ( $m1 < downloads.month or ($m1=downloads.month and downloads.day >= $d1 and downloads.day <= 31 ) ))  $c 
             (downloads.year = $y2 and ( $m2 > downloads.month or ($m2=downloads.month and downloads.day >= 01 and $d2 >= downloads.day ))))
        ) as mid
        group by mid.y, mid.m, mid.d
        order by mid.y, mid.m, mid.d";*/
      // $this->addSegmentQuery($this->otherdb);
       $result = $this->otherdb->get();
       // $result = $this->otherdb->query($query);
       // print_r($result->result_array());
       $titlearray = array("downloads");
       return $this->prepareResultSet($titlearray, $initialDate, $finalDate, $result->result_array());       
    }
    
    
    
}
?>

