<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class WebServiceControllerModel extends CI_Model {

    private $isLive = false;
    private $response = "";
 

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }

    public function getUrlData($dataName,$typeEncoding = "none") {

        $responseData = "";

        if(!$this->isLive) {
            $responseData = $this->input->get_post($dataName);
        }
        else
        {
            $responseData = $this->input->post($dataName);          
        }

        switch ($typeEncoding) {
            case 'base64':
                return base64_decode($responseData);
                break;
            
            default:
                return $responseData;
                break;
        }
    }

    public function getResponseString($data) {
        return json_encode($data);
    }

    public function prepareResponse($code,$message,$arrParams = array()){
        $response = array();
        $result = array(
            "result"=>$code,
            "message"=>$message
        );     

        $result = array_merge($result, $arrParams);

        $this->response = $this->getResponseString($result);        
    }  

    public function respond() {
        $this->load->view('webServices/webServiceResponse',array("response"=>$this->response));
    }
}
?>