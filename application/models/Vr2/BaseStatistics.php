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

class BaseStatistics extends CI_Model {
    
    protected $otherdb;
    //array
    protected $conditions = array();
    
    
    function __construct() { 
        parent::__construct();
          $this->otherdb = $this->load->database('impala', TRUE); 
         // $this->otherdb->query("refresh dailyuserdata");
          //$this->otherdb->query("refresh sessions");
         // $this->otherdb->query("refresh downloads");
         // $this->otherdb->query("refresh screen");
         // $this->otherdb->query("invalidate metadata");
    }
   
    
     public function addCondition($condition){
         $JSONCond = json_decode($condition,true);
        if($JSONCond != "" && $JSONCond!=null)
        {
            foreach ($JSONCond as $elem){//saca un obj o array con las cond
                foreach ($elem as $key => $value) {
                    //echo  $key." ".$elem["operando"]." '".$value."'";
                    $this->conditions[] = $key." ".$elem["operando"]." '".$value."'";
                    break;
                }
            }
            //$this->conditions[] = $condition;
        }       
    }
    
    /**
     * crea una consulta basandose en las condiciones previamente almacenadas.
     * @return type
     */
    public function getSegmentQuery(){
        $query = null;
        foreach ($this->conditions as  $key =>$condicion) {
                if($key > 0)
                {
                    $query.= " AND " . $condicion;
                }else{
                    $query.= " $condicion ";
                }
        }
        return $query;
    }
    /**
     * Agrega una nueva consulta al Active Record
     * @param CActiveRecord $activeRecord
     * @return CActiveRecord
     */
    protected function addSegmentQuery(&$activeRecord){
        $s = $this->getSegmentQuery();
        if($s!=null){
           // echo $s;
            $activeRecord->where($s);
        }
        return $activeRecord;       
    }
    
     /**
      * Perpara un arreglo q contendra informacion basado en dos fechas como limites de indices
      * @param string $initialDate  fecha inicial
      * @param string $finalDate  fecha final de la aplicacion.
      * @return array arrelgo con las fechas diarias entre los limites.
      */
    protected function generateLabels($initialDate, $finalDate){
        $daylength = 60*60*24;
        $daysBetween = abs((strtotime($initialDate)-strtotime($finalDate))/$daylength);
        $labels =  array(array("Date"));
       // if($daysBetween < 89)
        {
            $labels[1] = array(date("Y-m-d", strtotime($initialDate )));
            for ($i=2; $i-1 <= $daysBetween; $i++) { 
                $v = $i-1;
                    $labels[$i] = array(date("Y-m-d", strtotime($initialDate . "+$v day" )));	
            }			
        }
        return $labels;
    }
       protected function getConector($y1, $y2) {           
           if($y2>$y1)
               return 'or';
            return 'and';
    }
    /**
     * Prepara un formato de respuesta estilo tabla donde
     * la tabla inicial es dada por las fechas y el resto por los datos enviados por los usuarios,
     * @param array $titlearray, titulos que tendrá la tabla.
     * @param string $initialDate, fecha inicial
     * @param string $finalDate, fecha final
     * @param array $dataset, conjunto de datos resultantes de una consulta SQL. agrupada por year, month, day
     * @param array $sdataset, seugndo conjunto de datos resultantes de una consulta SQL. agrupada por year, month, day
     * @return array  Resultado final que sera enviado al usuario.
     */
    protected function prepareResultSet($titlearray, $initialDate, $finalDate, $dataset, $sdataset=null){
        $labels =  $this->generateLabels($initialDate, $finalDate);         
        for($i=1; $i<=count($titlearray);$i++){
            $labels[0][$i] = $titlearray[$i-1];          
        }        
        for($i=1;$i<count($labels);$i++){            
            for($j=1; $j<=count($titlearray);$j++){
                  $labels[$i][$j] = 0;
            }
            foreach ($dataset as $registro)
            {  
                if($registro["month"]<10)
                    $registro["month"]= "0".$registro["month"];
                if($registro["day"]<10)
                    $registro["day"]= "0".$registro["day"];
                $fecha = $registro["year"]."-".$registro["month"]."-".$registro["day"];  
                if($labels[$i][0] == $fecha){
                    for($k=1;$k<=count($titlearray); $k++){
                       $index = strtolower($titlearray[$k-1]);
                        $labels[$i][$k] = $registro["$index"];
                    }
                    break;
                }
            }
         }
         return $labels; 
    }
}

