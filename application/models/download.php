<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Download extends CI_Model {

     /**
    * @var string Variable que representa el HashKey de la tabla id_dwonload
    */
    var $hashname = "id_download";
    /**
    * @var string Variable que representa el nombre de la tabla a mapear 
    */
    var $tablename = "downloads";
    /**
    * @var Array    utilizado para almacenas valores de las llaves.
    */
    var $key = array();  
    /**
    * @var Array    utilizado para almacenas valores de los atributos
    */  
    var $attributes = array();
    

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
         
    }
    
    /*-------------------------------------------------------------------------------------------------------------
            GET ID DOWNLOAD FUNCTIONS  
    -------------------------------------------------------------------------------------------------------------*/

   
    /**
     * Retorna el actual IdDownload de un dispositivo si este ya a sido registrado en la aplicación. 
     * @param string $idApp, Id de la aplicación.
     * @param string $idDevice, EMEI del dispositivo.
     * @return string , ID de la descarga.
     */
    function getCurrentIdDownload($idApp,$idDevice) {
        
       $otherdb = $this->load->database('impala', TRUE);      
       $otherdb->query("refresh downloads");
       $result = $otherdb->query("select id_download from downloads where id_app = '$idApp' and idDevice = '$idDevice'");
       //$result = $otherdb->get_where($this->tablename, array('id_app' => $idApp, "idDevice" => $idDevice ));
       if($result!=null && $result->first_row()!=null)
       {
            $item = $result->first_row();
            $id = $item->id_download;
            
            return $id;
       }
       return "-1";
    }




    /**
    * Esta funcion le asigna un download, a un dispositivo que recien descarga e inicia la aplicacion.
    *
    * Esta funcion determina por medio del ID de la App, el numero de la descarga que se le asignara a este dispositivo.
    * @param string $idDevice, identificador unico del dispositivo que descarga la aplicacion. 
    * @param string $model, modelo especifico del dispositivo.
    * @param string $name, nombre del dispositivo
    * @param string $platformName, nombre de la plataforma, windows, android, PC entre otros.
    * @param string $platformVersion, version de la plataforma.
    * @param string $ip, Ip desde la cual se hizo la descarga.
    * @param string $idApp, aplicacion que se descargo
    * @param string $additionalInfo, informacion adicional del dispositivo.
    * @return  Id asignado a esta descarga.
    */
    function assignIdDownload($idDevice,$model,$name,$platformName,$platformVersion,$ip,$idApp,$additionalInfo, $appversion=null) {
        $model = str_replace(",", ".", $model);
        $name = str_replace(",", ".", $name);
        $idDevice = str_replace(",", ".", $idDevice);
        $platformName = str_replace(",", ".", $platformName);
        $platformVersion = str_replace(",", ".", $platformVersion);
        $ip = str_replace(",", ".", $ip);
        $idApp = str_replace(",", ".", $idApp);
        if($appversion==null){
            $appversion = "1";
        }
        $otherdb = $this->load->database('impala', TRUE);
        // $this->load->model(ETHVERSION."appstatistics");
       // $this->addPartition();
        $this->load->model(ETHVERSION."app");
        $newId = $this->getCurrentIdDownload($idApp,$idDevice);
        //echo "currentId ".$newId;
        if ($newId !== "-1") {
            return $newId;
        }

        //$newId = uniqid("", true); //$this->getNextIdDownload($idApp)."";
        $newId = $this->getNextIdDownload($idApp)."";
        $this->attributes = array(
            'idDevice' => $idDevice,
            'model' => $model,
            'name' => $name,
            'platformName' => $platformName,
            'platformVersion' => $platformVersion,
            'ipDownload' => $ip,
            'id_app' => $idApp,
            'id_download' => $newId,
            "downloadtime" => time(),
            "day" => (int)date("d"),
            "month" =>(int)date("m"), 
            "year" =>(int)date("Y")
            //"lastConnection" => time()
        );

        $variable = explode(";",$additionalInfo);
        
        foreach ($variable as $value) {
          $var =  explode(":", $value);
          if(count($var)==2)
          {
             if($var[1] == "False")
                $this->attributes[$var[0]] = false ;
            else 
              if($var[1] == "True"){
                  $this->attributes[$var[0]] = true; 
                }
              else if (is_numeric($var[1])) {
                    $this->attributes[$var[0]] = (int)$var[1];            
                } else
                    $this->attributes[$var[0]] = $var[1];
          }                   
        }
        if($platformName=='' || $platformName==null)
        {
            $platformName = "<unknown>";
        }
       
        $otherdb->query("insert into downloads "
                . "(systemLanguage,processorCount,systemMemorySize,supportsGyroscope,supportsAccelerometer,id_app,ipDownload,idDevice,graphicsDeviceVendor,id_download,supportsLocationService,supportsVibration,platformVersion,name,model,downloadtime,platformName, year, month, day) "
                . "values ("
                . "'".@$this->attributes['systemLanguage']."',"
                . "'".@$this->attributes['processorCount']."',"
                . "'".@$this->attributes['systemMemorySize']."',"
                . "'".@$this->attributes['supportsGyroscope']."',"
                . "'".@$this->attributes['supportsAccelerometer']."',"
                . "'$idApp',"
                . "'$ip',"
                . "'$idDevice',"
                . "'".@$this->attributes['graphicsDeviceVendor']."',"
                . "'$newId',"
                . "'".@$this->attributes['supportsLocationService']."',"
                . "'".@$this->attributes['supportsVibration']."',"
                . "'$platformVersion',"
                . "'$name',"
                . "'$model',"
                . time()
                . ",'$platformName',"
                . (int)date("Y").","
                . (int)date("m").","
                . (int)date("d")
                . ")"
                );
   

        return "".$newId;
    }   

        /**
    * Esta funcion retorna el ultimo id + 1 de las descargas de una aplicacion.
    * @param string $idApp, Identificador de la aplicacion.
    * @return int siguiente id de descarga.
    */
    function getNextIdDownload($idApp){
        
        $query = "select total_users as count from ethas_application WHERE id_app = '$idApp'";
        $result = $this->db->query($query);
        $result = (int)($result->first_row()->count) + 1 ;
        $query  =  "UPDATE ethas_application SET total_users = $result where id_app = '$idApp'";
        $this->db->query($query);
        return $result;
    }


      /**
    * Funcion que  retorna los de usuarios activos, 
    * Para facilidad esta funcionalidad debe ser calculada solo en rangos de un mes,
    * @param  string $idApp identificadorde la aplicacion.
    * @param  string $initialTime fecha inicial en formato Y-m-d.
    * @param  String $finalTime fecha final en formato Y-m-d.
    */
    function calculateActiveUsers($idApp, $initialTime, $finalTime){
        $query = "SELECT * from ethas_download where  id_app = '$idApp' and lastConnection BETWEEN '$initialTime' and '$finalTime'";
        //$data = $this->db->query($query);
        //return $data->result_array();
    }


  




    /**
    * Obtiene un Item de Download, si este existe.
    *  
    * @param string $id_app , identificador de la aplicacion.
    * @param string $id_download, identificador de la descarga.
    * @return  array|null item con los datos de download.
    */
    function getDownload($idApp, $id_download){
        //$otherdb = $this->load->database('impala', TRUE);
       // $otherdb->query("refresh downloads");
        //$this->db->query("refresh downloads");
        
        $otherdb = $this->load->database('impala', TRUE); 
        $otherdb->query("refresh downloads");
        $query = "Select *  from downloads WHERE id_app='$idApp' and id_download='$id_download'";
        $result = $otherdb->query($query);
        //se supone que solo existe una downloadcon ese id download y con ese id_app
        return $result->result_array()[0];     
    }

    /**
    * Esta funcion determina si existe o no una descarga basado en su IdDownload.
    * @param string $idApp, Id de la aplicacion.
    * @param string $idDownload, id de la descarga.
    * @return boolean, si la descarga ya fue registrada o no.
    */
    function existDownload($idApp, $idDownload){
         $otherdb = $this->load->database('impala', TRUE); 
         $otherdb->query("refresh downloads");
        //$result = $this->db->get_where($this->tablename, array("id_app" => $idApp, "id_download" => $idDownload));
        $query = "Select *  from $this->tablename WHERE id_app = '$idApp' and id_download = '$idDownload'";
        $result = $otherdb->query($query);
        //se supone que solo existe una downloadcon ese id download y con ese id_app
        if($result->first_row() == null)
        {
            return false;
        }else{
          return  true;
        }        
    }
    /**
    * Obtiene la fecha de una descarga.
    * @param string  $id_download , id de la descarga
    * @return string 
    */
    function getDownloadDate($idApp, $idDownload){
        
         $otherdb = $this->load->database('impala', TRUE); 
        $query = "Select *  from $this->tablename WHERE id_app = '$idApp' and id_download = '$idDownload'";
        $result =  $otherdb->query($query);
        //$result = $this->db->get_where($this->tablename, array("id_app" => $idApp, "id_download" => $idDownload));
        if($result->first_row() !=null)
        {
           // print_r($result->first_row());
            return $result->first_row()->downloadtime;
        }else{
          return  null;
        }        
    }

    

      /**
      * Funcion queobtiene la cantidad de usuarios que han descargado la aplicaciones.
      * @param string $idApp , Id de la aplicacion a obtener los usuarios.
      */
      function getTotalUsers($idApp, $filters =null){
          
         $query = "Select COUNT(*) as count from ethas_download WHERE id_app = '$idApp'";
         $result = $this->db->query($query);
         
         return (int)($result->first_row()->count) ;
      }

      /**
      * Funcion que permite obtener el promedio de sesiones por usuario.
      * @param string $idApp identificador de la aplicacion
      * @param string $initialDate fechas inicial de la cual se quiere obtener los datos.
      * @param string $finalDate fechas final de la cual se quiere obtener los datos.
      * @return  int conteo de sesiones.
      */
      function getAverageUserSession($idApp, $initialDate=null, $finalDate=null){
          
         $otherdb = $this->load->database('impala', TRUE);
         $otherdb->query("refresh downloads");
          if($initialDate!=null && $finalDate != null)
          {
              $query = "SELECT id_download, count(*) as sessions from sessions where id_app = '$idApp' and  start_date >= $initialDate and start_date <= $finalDate GROUP BY id_download";          
          }
          else{
              $query = "SELECT id_download, count(*) as sessions from sessions where id_app = '$idApp' GROUP BY id_download";
          }
          $result = $otherdb->query($query);
          //se toman la cantidad de id_downloads como la cantidad de usuarios que pertenecen a esa APP.
          $array = $result->result_array();
          $usuarios = count($array);
          $sesiones = 0;
          foreach ($array  as $key => $value) {
              $sesiones += (int)$value["sessions"]; 
          }
          return $sesiones/$usuarios;
      }
      
      

      /**
      * Funcion que permite obtener todas las descargas de una aplicacion.
      * @param string $idApp, identificador de la aplicacion.
      * @return  array $data, conjutno de descargas realizadas en una aplicación.
      * 
      */
      function getDownloads($idApp){    
        $otherdb = $this->load->database('impala', TRUE);
        $otherdb->query("refresh downloads");
        return  $otherdb->query("select  * from $this->tablename where id_app = '$idApp'")->result_array();        
      }

      /*function registerStateVariable($id_download, $id_app, $name, $value){

          $keys = array("id_download" => array("S"=>$id_download ),
                        "id_app"=> array("S"=>$id_app));

          $this->db->updateItemById("ethas_download", $keys, array("variables"=>array("Value"=>array("SS"=>array($name.":".$value)), "Action"=>"PUT")));
      }*/



      /**
      * Esta funcion entrega todas las descargas de la tabla y migra la informacion de AdditionalInfo a campos externos.
      * @return array , que contiene todas las descargas. 
      */
      function getAllDownloads(){  
        $otherdb = $this->load->database('impala', TRUE);
        $otherdb->query("refresh downloads");
        return  $otherdb->get($this->tablename)->result_array();
      }

}
?>
