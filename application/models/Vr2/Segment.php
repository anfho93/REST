<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Clase que representa al Modelo de las Session.
*
* Esta clase mapea la tabla ethas_segments de  la base de datos en Mysql
*
*  @author Andres felipe Herrera <anfho93@gmail.com>
*  @version 1.1
*/
class Segment extends CI_Model {
    
    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    /**
     * Esta funcionalidad permite crear una nuevo
     * segmento personalizado por el usuario utilizado un formato de valor
     * determinado
     * @param String $user_email correo electronico del usuario registrado
     * @param String $category nombre de la categoria 
     * @param String $name nombre dado al segmento.
     * @param String $title titulo dado a dicho segmento
     * @param JSON $value formato de entrada de los valores del segmento
     * @return boolean
     */
    public function add($user_email, $category, $name, $title, $value){
        if($this->isJson($value) )
        {
        
            $value = $this->processSegmentValue($value);
            if($value != null){
                 $array = array("nombre" => $name,
                        "id_user" => $user_email,
                        "valor" => $value,
                        "titulo" => $title,
                        "categoria" => $category);
                    $result = $this->db->insert("ethas_segment", $array);
                  //  print_r($this->db->error());
                    if ($this->db->error()["code"] == 1062) {
                        return false;
                    }
                    return $result;
                } else {
                    return false;
                }
            }else {
                return false;        
            }       
    }
    
    /**
     * Funcion que permite procesa el JSON enviado por el usuario para 
     * poder crear un nuevo segmento.
     * @param JSON $value String con el JSON value para procesar
     * @return String nuevo formato para el segmento
     */
    private function processSegmentValue($value){
        $propiedades = json_decode($value);
       
        if (json_last_error() == JSON_ERROR_NONE && $propiedades!=null){
                $result =  array();
           
            foreach ($propiedades as $prop) {
                if(property_exists($prop, "propiedad") &&
                       property_exists($prop, "operando") ){
                    $propiedad = $this->getPropertyValue($prop->propiedad);    
                    $operando = $this->getOperando( $prop->operando);    
                    $valor = $prop->valor;
                    if($propiedad != null && $operando !=null && is_string($operando) && count($propiedad)==1)
                    {
                      $result[] = array("operando"=>$operando, "$propiedad"=>$valor );
                    }
                }            
            }
            if(!empty($result)){
                $result = json_encode($result);
                if (json_last_error() == JSON_ERROR_NONE) {
                    return $result;
                } else {
                    echo "json error";
                    return null;
                }
            }  else return null;
        }
        
        return null;             
    }
    
    /**
     * Funcion que permite obtener una de las propiedades permitidas para 
     * realiza segmentos 
     * @param int $id identificador de la propiedad.
     * @return Object objeto que mapea la propiedad.
     */
    private function getPropertyValue($id){
        $result = $this->db->get_where("ethas_downloadproperties", array("id_prop"=> $id));        
        return $result->result_array()[0]["name"];
    }
    /**
     * Permite obtener un tipo de operando basado 
     * en el ID
     * @param String $id identificador del operando
     * @return string operando logico
     */
    private function getOperando($id){
        switch ($id)
        {
            case 1:
                return "=";
            case 2: 
                return "!=";
            default:
                return null;
        }
    }
    
    public function getTipos(){
        return   $this->db->get("ethas_segmenttypes")->result_array();
    }
    
    /**
     * Obtiene un listado de todas las propiedades de un dispositivo
     * @return arrayt lista de las propiedades
     */
    public function getAllProperties(){
       return  $this->db->get("ethas_downloadproperties")->result_array();
    }
    
    /**
     * Retorna una lista con todos los operandos logicos permitidos
     * @return array
     */
    
    public function getAllOperandos(){
        return  $this->db->get("ethas_segmentoperands")->result_array();       
    }
    
    /**
     * 
     * @param type $string
     * @return type
     */
    private function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    
    
    /**
     * Obtiene un segmento registrado en la base de datos.
     * @param int $idSegment identificador del segmento.
     * @return array, retorna el segmento basado en el id, null si no existe.
     */
    function getSegment($idSegment){
        $result=  $this->db->get_where("ethas_segment", array("id_segmento"=>$idSegment));
        $array = $result->result_array();
        if(count($array)>0)
        {
            return $array[0];        
        }
        else{
            return null;
        }
        
    }
      
    /**
     * Obtiene todos los segmentos de un usuario
     * @param String $user_email correo electronico del usuario
     * @return array arreglo con los segmentos de un usuario
     */
    public function getSegments($user_email)
    {
        $this->db->select(" id_segmento, nombre,titulo, categoria, valor");
        $this->db->from("ethas_segment");
        $this->db->where("id_user = 'a@a.a' || id_user = '$user_email' ");       
        $result = $this->db->get();  
  
        return $result->result_array();
    }
    
    /**
     * Retorna los datos de un segmento de un usuario
     * @param String $user_email correo electronico del usuario
     * @param int $idseg identificador del segmento
     * @return array datos del semento
     */
    public function getSegmentByID($user_email, $idseg){
        $this->db->select(" id_segmento, nombre,titulo, categoria, valor");
        //$this->db->where("id_user", $user_email);
        $this->db->where("id_segmento", $idseg);
        $this->db->from("ethas_segment");
        $result = $this->db->get();
        return $result->result_array();
    }
    
    /**
     * Permite obtener todos los sementos desarrollados por 
     * defecto por los creadores de la aplicacion
     * @return array Datos de los segmentos.
     */
    public function getDefaultSegments(){
        $this->db->select(" id_segmento, nombre,titulo, categoria,valor");
        $this->db->from("ethas_defaultsegment");
        $result = $this->db->get();
        return $result->result_array();
    }
    
    /**
     * Funcion que permit obtener los segmentos por defecto 
     * y los segmentos de un usaurio
     * @param String $user_email
     * @return array conjunto de segmentos
     */
    public function getAllSegments($user_email){
        $this->db->select("titulo, id_segmento, nombre, categoria");
        $this->db->from("ethas_defaultsegment");
        $result1 = $this->db->get();
        
        $this->db->select("titulo, id_segmento, nombre, categoria");
        $this->db->from("ethas_segment");
        $this->db->where("id_user", $user_email);
        $result = $this->db->get();
        
        return array_merge($result->result_array(), $result1->result_array() );
    }
    
    /**
     * Elimina los datos de un segmento de usuario en la base de datos
     * @param String $useremail correo electronico del usuario
     * @param String $idSeg identificador del segmento de la base de datos.
     * @return String
     */
    public function delete($useremail, $idSeg){
        $result = $this->db->delete("ethas_segment", array("id_segmento"=>$idSeg, "id_user"=>$useremail, "categoria"=>"User"));        
        return $this->db->affected_rows()==1;        
    }
    
    
    public function getAllTypes(){
        $result = $this->db->delete("ethas_segment", array("id_segmento"=>$idSeg, "id_user"=>$useremail));
    }
}