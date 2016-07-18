<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');



class Log extends CI_Model {

    /**
    * @var string Variable que representa el HashKey de la tabla, id_session
    */
    var $hashname = "id_session";
    /**
    * @var string Variable que representa el RangeKey de la tabla id_app
    */
    var $rangename = "time_stamp";
    /**
    * @var string Variable que representa el nombre de la tabla 
    */
    var $tablename = "logs";
    /**
    * @var Array    utilizado para almacenas valores de las llaves.
    */
    var $key = array();   
    
    var $attributes = array();


    /**
    * Constructor de la clase.
    */
    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
        $this->otherdb = $this->load->database('impala', TRUE);
    }
    
    /**
    * Esa funcion reporta un evento de la aplicacion en la base de datos.
    *
    *@param string $versionEthAppsSystem , version de la API
    *@param string $appversion version de la aplicacion que genera el evento.
    *@param sring $idDownload, ID de la descarga asignada al dispositivo que realiza el evento.
    *@param string $idApp , ID de la aplicacion que genera el evento.
    *@param string $idSession, durante que sesion se esta generando dicho evento.
    *@param string  $log, evento que genera la aplicacion.
    *@param string $category, categoria a la cual pertenece el evento, esta categoria se registra automaticamente en caso de no estarlo.
    *@param string $valor  del evento.
    *
    * @return string, confirmacion del reporte del evento o log.
    */
    function reportLog($versionEthAppsSystem,$appversion,$idDownload,$idApp,$idSession,$log, $category, $type, $value)
    {
        str_replace(",", ".", $log);
        str_replace(",", ".", $category);
        str_replace(",", ".", $type);
        str_replace(",", ".", $value);
        str_replace(",", ".", $versionEthAppsSystem);
           
        $this->load->model(ETHVERSION.'Download', "download");
        //$this->load->model(ETHVERSION.'Session', "sess");

       // if($this->verifySession($idDownload,$idApp)) 
        $stringdate  = date("Ynj");
        $row = $idApp.",".$idDownload.",".$appversion.",".$log.",".$idSession.",".$versionEthAppsSystem.",".$category.",".$type.",".$value.",".time().PHP_EOL;
            //$row = $idDownload.",".$category.",".$idSession.",".$value.",".$appversion.",".$idApp.",".$type.",".$log.",".time().",".$versionEthAppsSystem.PHP_EOL;
                if ( !file_exists(DATAROUTE) ) {
                  mkdir (DATAROUTE, 0744);
                 }
            if(file_put_contents ( DATAROUTE."log",  $row, FILE_APPEND ) > 0){
                file_put_contents ( DATAROUTE."log".$stringdate,  $row, FILE_APPEND );
                return "report log";
                
            }
            return "didnt report log";
        
    }
    
    public function getEventCategories($appversion,$idApp){
        //select   category from logs where id_app="546681e01f5c56.75661540" group by  category
        $this->otherdb->select("category");
        $this->otherdb->from("logs");
        $this->otherdb->where("id_app","$idApp");
        $this->otherdb->group_by("category");
        $result = $this->otherdb->get();
        //print_r($result->result_array());
        return  $result->result_array();   
    }
    
    public function getCategoriesTypes($appversion,$idApp, $category){
        //select type from logs where id_app="546681e01f5c56.75661540" and category="Menu1" group by  type
        $this->otherdb->select("type");
        $this->otherdb->from("logs");
        $this->otherdb->where("id_app","$idApp");
        $this->otherdb->where("category", "$category");
        $this->otherdb->group_by("category");
        $result = $this->otherdb->get();
        return  $result->result_array();     
    }
    
    public function getTypesLogs($appversion,$idApp, $category, $type){
        //select logs.log from logs where id_app="546681e01f5c56.75661540" and category="Menu1" and type="tipo1" group by  log
        $this->otherdb->select("log");
        $this->otherdb->from("logs");
        $this->otherdb->where("id_app","$idApp");
        $this->otherdb->where("category", "$category");
        $this->otherdb->where("type", "$type");
        $this->otherdb->group_by("log");
        $result = $this->otherdb->get();
        return  $result->result_array();        
    }
    
    public function getLogsValues($appversion,$idApp, $category, $type, $log){
        $this->otherdb->select("value");
        $this->otherdb->from("logs");
        $this->otherdb->where("id_app","$idApp");
        $this->otherdb->where("category", "$category");
        $this->otherdb->where("type", "$type");
        $this->otherdb->where("log", "$log");
        $this->otherdb->group_by("value");
        $result = $this->otherdb->get();
        return  $result->result_array();
    }
    
}
?>
