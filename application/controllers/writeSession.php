<?php
     
class WriteSession extends CI_Controller{
    public function index()
    {
            $ch = curl_init();  
            $ip = $_GET["ip"];
            $datos = $_GET["data"];
            $jsondata = "{}";
            if( isset($_GET["ip"]) && $_GET["ip"]!=null )
            {
                $url = "http://freegeoip.net/json/$ip";
                curl_setopt($ch,CURLOPT_URL,$url);
                curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
                curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
                $jsondata = curl_exec($ch);	 
                $data = json_decode($jsondata);
                if(json_last_error()== JSON_ERROR_NONE)
                {
                    file_put_contents ( DATAROUTE."session",  $datos.",".$data["ip"],$data["country_code"],$data["country_name"],$data["city"]."\n", FILE_APPEND );
                    return $jsondata;	
                }else{
                    return  "{}";
                }
            }else{
                    return  "error";
            }
    }
}
    
?>

