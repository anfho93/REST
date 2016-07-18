<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Clase que representa al Modelo de las Screen.
*
* Esta clase mapea la tabla ethas_screeb de  la base de datos no relacional de DynamoDB.
*
*  @author Andres felipe Herrera <anfho93@gmail.com>
*  @version 1.1
*/
class Screen extends CI_Model {

    /**
    * @var string Variable que representa el HashKey de la tabla id_app
    */
    var $hashname = "id_session";
      /**
    * @var string Variable que representa el RangeKey de la tabla id_app
    */
    var $rangename = "timestamp";
     /**
    *@var  string nombre de la tabla
    */
    var $tablename = "screen";
     /**
    * @var Array    utilizado para almacenas valores de las llaves.
    */
    var $key = array();   
    /**
    *@var Array     utilizado para almacenas valores de los atributos a agregar a un Item de la tabla..
    */  
    var $attributes = array();
    /**
    * Constructor de la clase.
    */
    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    

    /**
    * Reporta un evento de una pantalla en el sistema.
    * @param string $versionEthAppsSystem , version de la API
    * @param string $appversion, version de laaplicacion que genera el reporte del screen 
    * @param string $idDownload, id de la descarga
    * @param string $idApp,  id de la aplicacion que registra la screen
    * @param string $idSession, id de la session del dispositivo.
    */
    function reportScreen($versionEthAppsSystem,$appversion,$idDownload,$idApp,$idSession,$screen)
    {
        str_replace(",", ".", $screen);
        $stringdate = date("Ynj");
        $row = time() . "|" . $idDownload . "|" . $screen . "|" . $idApp . "|" . $idSession . "|0|" . $appversion . "|" . $versionEthAppsSystem . PHP_EOL;
        if (!file_exists(DATAROUTE)) {
            mkdir(DATAROUTE, 0744);
        }

        if (file_put_contents(DATAROUTE . "screen", $row, FILE_APPEND) > 0) {
            file_put_contents(DATAROUTE . "screen" . $stringdate, $row, FILE_APPEND);
            return "report screen";
        }
        return "didnt report screen";
    }
}
?>
