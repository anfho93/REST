<?php


/**
 * Controlador creado para manejar todas las peticiones relacionadas
 * con el reporte, y estadisticas de los eventos de las aplicaciones.
 *
 * 
 */
defined('BASEPATH') OR exit('No direct script access allowed');
require 'EthRESTController.php';
class Logs extends EthRESTController {

    /**
     * constructor de la clase
     */
    public function __construct() {
        parent::__construct();
        $this->load->model(ETHVERSION . "log", "logs");
        $methodname = strtolower("index_" . $this->request->method);
        if (method_exists($this, $methodname)) {
            $this->$methodname();
        }
    }


    /**
     * metodo que funciona como un filtro para las 
     * peticiones get
     * @throw <400 bad request> error en la peticion del usuario
     */
    public function index_get() {
        if ($this->_pre_get() != null) {
            switch ($this->_pre_get()) {
                case "categories":
                    $this->getCategories();
                    return;
                case "types":
                    $this->getTypes();
                    break;
                case "logs":
                    $this->getLogs();
                    break;
                case "values":
                    $this->getValues();
                    break;
            }
        } else {
            $this->response([
                'status' => FALSE,
                'message' => "indique que desea obtener"
                    ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

     /**
     * Esta funcion permite ver las categorias registradas 
     * por una aplicacion a la hora de enviar eventos. 
      * los parametros sera recibidos via GET
     * @param string idApp identificador de la aplicacion 
     * @param string  appVersion version de la aplicacion que se desea obtener
     * @param string  useremail correo electronico del usuario del sistema
     * @throw <201 accepted> respuesta con las categorias encontradas en el sistema 
     */
    private function getCategories() {
        $idApp = $this->get('idApp');
        $appVersion = $this->get('appVersion');
        $email = $this->get('useremail');
        $result = $this->logs->getEventCategories($appVersion, $idApp);
        $this->response(['status' => TRUE, "result" => $result], REST_Controller::HTTP_ACCEPTED);
    }
    /**
     * Esta funcion permite ver los tipos de eventos registrados por una aplicacion
     * estos tipos son parte de los datos reportados desde las aplicaciones.
     * los parametros sera recibidos via GET
     * @param string idApp identificador de la aplicacion 
     * @param string  appVersion version de la aplicacion que se desea obtener
     * @param string  useremail correo electronico del usuario del sistema
     * @param string category  categoria a la cual se le desean buscar los tipos
     * @throw <201 accepted> respuesta con los tipos encontrados en el sistema 
     */
    private function getTypes() {       
        $idApp = $this->get('idApp');
        $appVersion = $this->get('appVersion');
        $email = $this->get('useremail');
        $category = $this->get('category');
        $result = $this->logs->getCategoriesTypes($appVersion, $idApp, $category);
        $this->response(['status' => TRUE, "result" => json_encode($result)], REST_Controller::HTTP_ACCEPTED);
    }

     /**
     * Esta funcion permite ver  eventos registrados por una aplicacion
     * estos tipos son parte de los datos reportados desde las aplicaciones.
     * los parametros sera recibidos via GET
     * @param string idApp identificador de la aplicacion 
     * @param string  appVersion version de la aplicacion que se desea obtener
     * @param string  useremail correo electronico del usuario del sistema
     * @param string category  categoria a la cual se le desean buscar los tipos
     * @param string type categoria a la cual se le desean buscar los tipos
     * @throw <201 accepted> respuesta con los eventos encontrados en el sistema 
     */
    private function getLogs() {
        
        $idApp = $this->get('idApp');
        $appVersion = $this->getCategories('appVersion');
        $email = $this->get('useremail');
        $category = $this->get('category');
        $type = $this->get('type');
        $result = $this->logs->getTypesLogs($appVersion, $idApp, $category, $type);
        $this->response(['status' => TRUE, "result" => json_encode($result)], REST_Controller::HTTP_ACCEPTED);
    }

     /**
     * Permite obtener todos los valores de los eventos resportados por el sistema
     * los parametros sera recibidos via GET
     * @param string idApp identificador de la aplicacion 
     * @param string  appVersion version de la aplicacion que se desea obtener
     * @param string  useremail correo electronico del usuario del sistema
     * @param string category  categoria a la cual se le desean buscar los tipos
     * @param string type categoria a la cual se le desean buscar los tipos
     * @param string log categoria a la cual se le desean buscar los tipos
     * @throw <201 accepted> respuesta con los tipos encontrados en el sistema      
     */
    private function getValues() {
       
        $idApp = $this->get('idApp', 'base64');
        $appVersion = $this->get('appVersion', 'base64');
        $email = $this->get('useremail', 'base64');
        $category = $this->get('category', 'base64');
        $type = $this->get('type', 'base64');
        $log = $this->get('log', 'base64');
        $result = $this->logs->getTypesLogs($appVersion, $idApp, $category, $type, $log);
        $this->response(['status' => TRUE, "result" => json_encode($result)], REST_Controller::HTTP_ACCEPTED);
    }

    /**
     * Metodo encargado de registrar un evento en el sistema de analiticas
     * @param string idApp identificador de la aplicacion 
     * @param string  iddownload identificador de la descarga o usuario de una app
     * @param string  versionEthAppsSystem correo electronico del usuario del sistema
     * @param string idversion version de la aplicacion que reporta el evento
     * @param string idsession identificador de la sesion inicia
     * @param string log categoria a la cual se le desean buscar los tipos
     * @param string category  categoria a la cual se le desean buscar los tipos
     * @param string type categoria a la cual se le desean buscar los tipos
     * @param string log categoria a la cual se le desean buscar los tipos
     * @param string value categoria a la cual se le desean buscar los tipos
     
     */
    public function index_post() {
        $idApp = $this->post('idapp');
        $idDownload = $this->post('iddownload');
        $versionEthAppsSystem = $this->post('versionEthAppsSystem');
        $idVersion = $this->post('idversion');
        $idSession = $this->post('idsession');
        $log = $this->post('log');
        $category = $this->post('category');
        $type = $this->post('type');
        $value = $this->post('value');

        $idApp = str_replace("|", "", $idApp);
        $idDownload = str_replace("|", "", $idDownload);
        $log = str_replace("|", "", $log);
        $category = str_replace("|", "", $category);
        $type = str_replace("|", "", $type);
        $value = str_replace("|", "", $value);

        if ($this->verifySession($idDownload, $idApp, $idSession))
                        {
            $this->report($versionEthAppsSystem, $idVersion, $idDownload, $idApp, $idSession, $log, $category, $type, $value);
            $this->response(['status' => TRUE, "message" => "Success"], REST_Controller::HTTP_ACCEPTED);
        } else
        {
         $this->response(['status' => FALSE, "message" => "Fail"], REST_Controller::HTTP_FORBIDDEN);
        }        
    }

    /**
     * Esta funcion realiza un reporte de un evento en la base de datos.
     * @param string $versionEthAppsSystem version la api.
     * @param string $idVersion version de la aplicacion
     * @param string $idDownload  identificador de la descarga.
     * @param string $idApp  identificador de la aplicacion.
     * @param string $idSession  id de la session
     * @param string $log evento a registrar.	
     * @param string $category  categoria del evento a registrar.	, por defecto =  default
     * @param string $type  que tipo de evento es.	
     * @param string $value valor del evento a registrar.	
     * @return void
     */
    private function report($versionEthAppsSystem, $idVersion, $idDownload, $idApp, $idSession, $log, $category = "Default", $type = "Default", $value = "none") {
        $this->logs->reportLog($versionEthAppsSystem, $idVersion, $idDownload, $idApp, $idSession, $log, $category, $type, $value);
    }

    /**
     * Metodo que maneja la actualizacion de los datos
     */
    public function index_put() {
        $this->response([
            'status' => FALSE,
            'message' => "No implementado aun"
                ], REST_Controller::HTTP_BAD_REQUEST);
    }
    /**
     * Metodo que maneja la eliminacion de los datos
     */
    public function index_delete() {
        $this->response([
            'status' => FALSE,
            'message' => "No implementado aun"
                ], REST_Controller::HTTP_BAD_REQUEST);
    }

}
