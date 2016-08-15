<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of funnel
 *
 * @author Proyecto672
 */
class funnel extends CI_Model {
   
    var $ar_select = array();
    /**
     * 
     * @var String , Representa el nombre dado a la tabla en MYSQL 
     */
    var $tablename = "Funnel";  
    /**
     * Metodo constructor del funnel model
     * Crea una instacia nueva a la base de datos usando impala
     * y refresca algunos metadatos.
     */
    public function __construct() {
        parent::__construct();
        $this->otherdb = $this->load->database('impala', TRUE); 
        $this->otherdb->query("refresh funnels;");
    }
    /**
     *  Agrega un nuevo evento de funnel
     *  @param String $select , condicion a agregar para funnel
     */
    public function addFunnelEvent($select){
        $this->ar_select[]= $select;        
    }
    
    /**
     * Busca en la base de datos MYSQL el estado
     * de un funnel consultado  y lo modifica para su visualización.
     * @param String $status nuevo estado.
     * @param String $funnelID id del funnel a modficar.
     */
    function setFunnelState($status ,$funnelID ){
        $this->db->where('id_funnels', $funnelID);
        $data = array("status"=>$status);
        $this->db->update($this->tablename, $data); 
        
    }
    
    /**
     * Obtiene el nombre de un funnel basado en su ID
     * @param String $id  identificador del funel
     * @return mixed, string con el nombre o null si no fue encontrado.
     */
    function getFunnelNameById($id){
        $this->db->where('id_funnels', $id);        
        $this->db->select("name");
        $query = $this->db->get($this->tablename);
        $row = $query->first_row();
        //print_r($row);
        if($row!=null){
            $name = $row->name;
            return $name;
        }
        return null;
    }
    /**
     * Obtiene la informacion especifica de un funnel
     * ya procesado, esta información contiene los elementos
     * o pasos de que cada usuario asignó.
     * @param String $idApp identificador de la aplicación propietaria del funnel
     * @param String $funelName nombre del funnel
     * @param String $funnelID identificador del funnel
     * @return mixed informacion sobre el funnel existente, null si no existe.
     */
    function getFunnelData($idApp, $funelName, $funnelID){
        
        $this->otherdb->where('idapp', $idApp);
        $this->otherdb->where('idfunnel', $funelName);        
        //$this->otherdb->where('id_funnels', $funnelID);
        $this->otherdb->select("*");
        $query = $this->otherdb->get("funnels");
        $row = $query->first_row();
        
        if($row!=null){           
            return $row;
        }
        return null;
    }
    /**
     * Agrega un registro de funnel a la base de datos en MYSQL
     * @param type $workID id del funnel 
     * @param type $email correo del propietario del funnel
     * @param type $name nombre del funnel a registrar
     * @return boolean  true si registra false si no.
     */
    public function addFunnelRegister($workID, $email, $name ){
        $data = array(
            'id_funnels' => $workID ,
            'user_email' => $email,
            'name' => $name ,
            "json_value" => ""
        );
        $result = $this->db->insert($this->tablename, $data); 
        return $result;        
    }
    
    /**
     * Obtiene todos los funnels registrados de un usuario 
     * basado en su email y un estado  
     * @param String $email, correo electronico del usuario
     * @return mixed resultado de los funnels
     */
    public function getFunnelByEmail($email, $status = ""){
        $this->db->select('id_funnels,name, date, status');
        if($status!="")
        {
            $this->db->where('status',$status);
        }
        $this->db->where('user_email',$email);
        $query = $this->db->get($this->tablename);
        //$query = $this->db->get_where($this->tablename, array("user_email" => $email));
        return $query->result_array();
    }
    
    /**
     * Funcion que permite concatenar elementos 
     * @param array $array  conjunto de elemenos a concatenas
     * @return retorna una string concatenado.
     */
    private function getConcat($array){        
        $q = "concat(";
        $sep = ",'|',";
        if(sizeof($array)>0)
        {
            $q.= $array[0];
            if(sizeof($array)>=2)
            {
                for($i=1; $i<sizeof($array);$i++)
                {
                    $q.= $sep.$array[$i];
                }
            }
        }
        return $q.")" ;
                
    }
    
    /**
     * Genera una sentencia select para los funels
     * @return string string con la sentencia select.
     */
    public function getFunnelSelect($keys){
        $aux = $this->ar_select;
        unset($aux[0]);
        
        return $this->getConcat(array($this->ar_select[0], $this->to_json_format($aux,$keys)));
    }
    /**
     * Genera una version de un select en formato JSON
     * @param array $selectarray, consulta
     * @return String consulta usnado metodo to_json de impala
     */
    public function to_json_format($selectarray, $keys){
        $q = "to_json( named_struct( ";
        $c = 0;
        $comma = "";
        foreach ($selectarray as $value) {
            //$key = '"key'.$c.'"';
            $key = "'$keys[$c]'";
            
            $q.=$comma.$key.",".$value;
            $c++;
            $comma = ",";
        }
        return $q."))";
    }
    /**
     * 
     * @param type $array, conjunto de pasos del funnel
     * @param type $initial fecha inicial
     * @param type $final fecha final 
     * @param type $idapp  id de la aplicacion 
     * @param type $email correo electronico del usuario
     * @return mixed string con la consulta, o null en caso de no cumplir con las reglas
     */
    public function generateQuery($array, $initial, $final, $idapp,$email="'noemail'"){
       @list($d1, $m1, $y1) = explode( "-" , $initial,3);
       @list($d2, $m2, $y2) = explode( "-" , $final,3);
       $idFunnel = uniqid();
       $this->addFunnelEvent("'$email'");
       $keys = array();
        try{
            $METODO = "from";
            if(sizeof($array)>1)
            {  
                for($cont=0; $cont < sizeof($array); $cont++){
                    $obj = $array[$cont];
                   
                    if($this->validateTable($obj->t)){
                      $keys[] = $obj->n;
                      $query = "q".$cont;
                      switch ($obj->t){
                        case "logs":
                         $this->addFunnelEvent("count(distinct $query.d)");
                        if($METODO=="from")
                        { 
                            $this->db->from("(select distinct d$cont.id_download as d, d$cont.log_date as dt
                                                from logs as d$cont where d$cont.category = '$obj->category' and d$cont.type = '$obj->type' and d$cont.log = '$obj->log' and d$cont.id_app = '$idapp'
                                                and ((d$cont.year = $y1 and ( $m1 < d$cont.month or ($m1=d$cont.month and d$cont.day >= $d1 and d$cont.day <= 31 ) ))
                        and (d$cont.year = $y2 and ( $m2 > d$cont.month or ($m2=d$cont.month and d$cont.day >= 01 and $d2 >= d$cont.day ))))) as $query");
                     
                        }
                        else {
                            $this->db->join("(select distinct d$cont.id_download as d, d$cont.log_date as dt
                                                from logs as d$cont where d$cont.category = '$obj->category' and d$cont.type = '$obj->type' and d$cont.log = '$obj->log' and d$cont.id_app = '$idapp'
                                                and ((d$cont.year = $y1 and ( $m1 < d$cont.month or ($m1=d$cont.month and d$cont.day >= $d1 and d$cont.day <= 31 ) ))
                        and (d$cont.year = $y2 and ( $m2 > d$cont.month or ($m2=d$cont.month and d$cont.day >= 01 and $d2 >= d$cont.day ))))) as $query", "q".($cont-1).".d = q".$cont.".d" ,"left");
                       
                        }
                        break;
                      }
                    }
                    
                    $METODO = "join";
                }
            }           
        $this->db->select($this->getFunnelSelect($keys),FALSE);
        $q = "INSERT OVERWRITE DIRECTORY '/user/ethereal/funnels/db/$idapp/$idFunnel'". $this->db->getSQL();
        $q = "ALTER TABLE funnels ADD  IF NOT EXISTS PARTITION  (idapp='$idapp', idfunnel='$idFunnel') location '/user/ethereal/funnels/db/$idapp/$idFunnel'; ".$q.";";
        return array($idFunnel, $q);
        }catch(Exception $e){
            //echo $e->getTraceAsString();
            return null;
        }
    }
    
    /**
     * Metodo que valida que la tabla escogida en los funnnels se pueda analizar
     * @param string $table nombre de la tabla
     * @return boolean
     */
    public function validateTable($table){
        return true;
    }
    
    
    /**
     * Sube un archvivo con el contenido de la consulta SQL.
     * @param String $content   Consulta HQL
     * @return mixed, null si no se pudo completar el proceso, String con la ubicacion.
     */
    public function submitFunnelHql($content){
        $filename = uniqid("query");
        $password= "ethereal";
        $username="ethereal";
        $url = "http://ethgame.com:14000/webhdfs/v1/user/ethereal/funnels/queries/$filename.hql?op=CREATE&user.name=ethereal";
        $ch = curl_init($url);
        $route = "/user/ethereal/funnels/queries/$filename.hql";
        // curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);al:8020] not allowed, not in Oozie's whitelist. Allowed values are: [ethdemos.com
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
        curl_setopt($ch, CURLOPT_PUT, true);           
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/octet-stream' ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");            
        $output = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        echo $httpcode;
        if ($httpcode == 201) {
            //tuvo exito
            if( $this->agregarContenidoArchivo($filename, $content)){
                curl_close($ch);
                return $route;
            }else {
                curl_close($ch);
                return null;
            }

        }else{
            print_r("Error creando el archivo intente de nuevo");
            curl_close($ch); 
            return null;

        }            
    }
    
    /**
     * Agrega contenido a un archivo recien subido al HDFS
     * @param String  $filename nombre del archivo
     * @param String  $contenido  contenido del archivo
     * @return boolean
     */
    public function agregarContenidoArchivo($filename, $contenido){
            $url = "http://ethgame.com:14000/webhdfs/v1/user/ethereal/funnels/queries/$filename.hql?op=APPEND&user.name=ethereal";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
            curl_setopt($ch, CURLOPT_POST, true);           
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_NOBODY, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $contenido);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/octet-stream' ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            $output = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
             if ($httpcode == 200) {                
                curl_close($ch);
                return true;
            }else{
            
                curl_close($ch);
                return false;
            }
        
        }
   
}
