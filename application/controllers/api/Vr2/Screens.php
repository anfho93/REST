<?php


/**
 * Controlador encargado de manejar todas las peticiones relacionadas con el reporte y consulta de datos
 * sobre las pantallas de un dispositivo. 
 */
defined('BASEPATH') OR exit('No direct script access allowed');

require 'EthRESTController.php';

class Screens extends EthRESTController {

    private $useremail;
    private $idApp;
    private $initialDate;
    private $finalDate;
    private $tipoRetencion;
    public $segment = null;

    /**
     * constructor de la clase, verifica que los metodos existan
     * y carga los modelos requeridos 
     */
    public function __construct() {
        parent::__construct();
        $methodname = strtolower("index_" . $this->request->method);
        if (method_exists($this, $methodname)) {
            $this->$methodname();
        }
        $this->load->model(ETHVERSION . "screenstatistics");
        $this->load->model(ETHVERSION . "appstatistics");
    }

    /**
     * Metodo que realiza la verificacion que el endpoint requerido exista
     * este metodo es llamado en una peticion tipo GET
     * 
     */
    public function index_get() {
        if ($this->_pre_get() != null) {
            switch ($this->_pre_get()) {
                case "views":
                    $this->getViews();
                    break;
                case "interactions":
                    $this->getInteractions();
                    break;
                case "reports":
                    $this->getScreensReport();
                    break;
                case "data":
                    $this->getScreensData();
            }
        } else {
            $this->getScreen();
        }
    }
    
    /**
     * Permite obtener estadisticas especifica de una pantalla registrada
     * parametros recibidos via GET
     * @before es necesario usar el metodo loaddata
     * @param   string screenname nombre de la pantalla especifica
     * @throw <403 forbiden>  si los datos son invalidos
     * @throw <201 accepted>  respuesta con los datos en formato JSON.
     */
    private function getScreensData(){        
        $this->loadData($this->screenstatistics);
        $result = null;
        if($this->validateData($this->useremail, $this->idApp ))
        {
            $screename = $this->get('screenname');
            if($screename != null && $screename != "" )
            {   
                $this->screenstatistics->addCondition($this->segment);
                $result =  $this->screenstatistics->getSpecificScreenData($this->idApp, $this->initialDate, $this->finalDate,$screename); 
            }else{
                 $result = $this->screenstatistics->getScreensData($this->idApp, $this->initialDate, $this->finalDate); 
            }                  
            $this->response(['status' => TRUE, "result"=>$result], REST_Controller::HTTP_ACCEPTED);
        }else{
            // datos invalidos
            $this->response(['status' => FALSE], REST_Controller::HTTP_FORBIDDEN);
        }
    }

    /**
     * Permite obtener estadisticas de las pantallas registradas
     * parametros recibidos via GET
     * @before es necesario usar el metodo loaddata
     * @throw <403 forbiden>  si los datos son invalidos
     * @throw <201 accepted>  respuesta con los datos en formato JSON.
     */
    private function getScreensReport() {
        $this->loadData($this->appstatistics);
        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->appstatistics->getGeneralScreensData($this->idApp, $this->initialDate, $this->finalDate);
            $totalScreens = $this->appstatistics->countScreens($this->idApp, $this->initialDate, $this->finalDate);

            if ($totalScreens != 0) {
                for ($i = 0; $i < count($result); $i++) {
                    $result[$i]['Visualization(%)'] = (($result[$i]['pantallas'] / $totalScreens) * 100);
                }
            } else {
                for ($i = 0; $i < count($result); $i++) {
                    $result[$i]['Visualization(%)'] = 0;
                }
            }
            $titulo = array("Screen", "Visualization", "Visualization(%)");
            $this->response(['status' => TRUE, "titles" => $titulo, "result" => $result], REST_Controller::HTTP_ACCEPTED);
        } else {
            $this->response(['status' => FALSE], REST_Controller::HTTP_FORBIDDEN);
        }
    }

     /**
     * Permite obtener estadisticas en tabla de las pantallas registradas
     * parametros recibidos via GET
     * @before es necesario usar el metodo loaddata
     * @throw <403 forbiden>  si los datos son invalidos
     * @throw <201 accepted>  respuesta con los datos en formato JSON.
     */
    private function getScreen() {
        $this->loadData($this->appstatistics);
        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->appstatistics->getGeneralScreensData($this->idApp, $this->initialDate, $this->finalDate);
            $titulo = array("Screen", "Visualization");
            $this->response(['status' => TRUE, "titles" => $titulo, "result" => $result], REST_Controller::HTTP_ACCEPTED);
            
        } else {
            
            $this->response(['status' => FALSE], REST_Controller::HTTP_FORBIDDEN);
        }
    }

     /**
     * Permite obtener estadisticas sobre la interaccionde los usuarios 
     * parametros recibidos via GET
     * @before es necesario usar el metodo loaddata
     * @throw <403 forbiden>  si los datos son invalidos
     * @throw <201 accepted>  respuesta con los datos en formato JSON.
     */
    private function getInteractions() {
        $this->loadData($this->screenstatistics);
        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->screenstatistics->getInteraction($this->idApp, $this->initialDate, $this->finalDate);
            $this->response(['status' => TRUE, "result" => $result], REST_Controller::HTTP_ACCEPTED);
        } else {
            // datos invalidos           
            $this->response(['status' => FALSE], REST_Controller::HTTP_FORBIDDEN);
        }
    }

    /**
     * 
     * @param Ci_model $modelo modelo al que seran agregados los datos o parametros.
     * @param string  useremail correo electronico del usuario
     * @param string  string tipo de retencion  que se desea obtener
     * @param string  idApp identificador del correo electronico
     * @param string  initialDate fecha inicial del analisis
     * @param string  finalDate fecha final del analisis
     * @param string  segment segmento del usuario que se queire consultar
     */
    private function loadData($modelo = null) {
        $this->useremail = $this->get('useremail');
        $this->tipoRetencion = $this->get('type');
        $this->idApp = $this->get('idApp');
        $this->initialDate = $this->get('initialDate'); //fecha en formato (2013-00-00), la diferencia entre esta y finalDate no debe exeder 3 meses o 90 dias
        $this->finalDate = $this->get('finalDate'); //fecha en formato (2013-00-00), esta fecha no debe ser mayor al dia actual.
        $idSegment = $this->get('segment');

        if ($idSegment !== "" && $idSegment !== null) {
            $this->load->model(ETHVERSION . "segment", "seg");
            $segment = $this->seg->getSegment($idSegment);

            if ($segment != null && array_key_exists("valor", $segment) && $modelo != null) {
                $this->segment = $segment["valor"];
                $modelo->addCondition($this->segment);
            } else {
               
            }
        }
    }
    
     /**
     * Permite obtener estadisticas en tabla de las pantallas registradas
     * parametros recibidos via GET
     * @before es necesario usar el metodo loaddata
     * @throw <400 bad request>  si los datos son invalidos
     * @throw <201 accepted>  respuesta con los datos en formato JSON.
     */            
    private function getViews() {
        $this->loadData($this->screenstatistics);
        $result = null;
        if ($this->validateData($this->useremail, $this->idApp)) {
            $result = $this->screenstatistics->getScreenViews($this->idApp, $this->initialDate, $this->finalDate);
            $this->response([
                'status' => TRUE,
                'message' => "success",
                "result" => $result
                    ], REST_Controller::HTTP_ACCEPTED);
        } else {
            // datos invalidos
            $this->response([
                'status' => FALSE,
                'message' => "Fail",
                "result" => array()
                    ], REST_Controller::HTTP_BAD_REQUEST);
            
        }
    }

    /**
     * Funcion principal del servicio, aqui se se reciben los parametros via peticion http.
     * @param string idapp  identificador de la aplicacion.
     * @param string idDownload  identificador de la descarga.
     * @param string versionEthAppsSystem  Numero de la version de la API
     * @param string idVersion  versionn de la aplicacion que obtiene las variables.
     * @param string idSession  id de la sessio
     * @param string screen  texto o nombre de la pantalla que se registrara
     * @return json string  respuesta con las variables o el mensaje de error.
     * @throw <400 bad request>  si los datos son invalidos
     * @throw <201 accepted>  respuesta con los datos en formato JSON.
     */
    public function index_post() {
        print_r($this->post());
        $idApp = $this->post('idapp');
        $idDownload = $this->post('iddownload');
        $versionEthAppsSystem = $this->post('versionEthAppsSystem');
        $idVersion = $this->post('idversion');
        $idSession = $this->post('idsession');
        $screen = $this->post('screen');

        $screen = str_replace("|", "", $screen);
        $idVersion = str_replace("|", "", $idVersion);
        $idDownload = str_replace("|", "", $idDownload);
        $idApp = str_replace("|", "", $idApp);

        if ($this->verifySession($idDownload, $idApp, $idSession)) {
            $this->report($versionEthAppsSystem, $idVersion, $idDownload, $idApp, $idSession, $screen);
            $this->response([
                'status' => TRUE,
                'message' => "REPORTED"
                    ], REST_Controller::HTTP_ACCEPTED);

        } else {
            $this->response([
                'status' => FALSE,
                'message' => "no inicio sesion"
                    ], REST_Controller::HTTP_FORBIDDEN);
        }
    }

    /**
     * Esta funcion realiza un reporte de un evento en la base de datos.
     * @param string $versionEthAppsSystem version la api.
     * @param string $idVersion version de la aplicacion
     * @param string $idDownload  identificador de la descarga.
     * @param string $idApp  identificador de la aplicacion.
     * @param string $idSession  id de la session
     * @param string $screen pantalla  a registrar.	
     * @return void
     */
    private function report($versionEthAppsSystem, $idVersion, $idDownload, $idApp, $idSession, $screen) {
        $this->load->model(ETHVERSION . 'screen');
        $this->screen->reportScreen($versionEthAppsSystem, $idVersion, $idDownload, $idApp, $idSession, $screen);
    }

    /**
     * funcion encargada del manejo de la actualizacion
     */
    public function index_put() {
        $this->response([
            'status' => FALSE,
            'message' => "No implementado aun"
                ], REST_Controller::HTTP_BAD_REQUEST);
    }

    /**
     * funcion encargada del manejo de eliminacion
     */
    public function index_delete() {
        $this->response([
            'status' => FALSE,
            'message' => "No implementado aun"
                ], REST_Controller::HTTP_BAD_REQUEST);
    }

}
