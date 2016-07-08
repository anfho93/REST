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
include_once 'BaseStatistics.php';
class Variablestatistics extends BaseStatistics {
    
    
    /**
    * Metodo constructor del modelo.
    */
    function __construct()
    {
      parent::__construct();
 
    }
    /**
     * Funcion que permite obtener informacion de las variables de estado de una aplicacion 
     * @param string    $idApp identificador de la aplicacion.
     * @return array resultado de la consulta con el conjunto de datos del array
     */
    function getStateVariables($idApp){
        $this->db->select('id_variable, name');
        $this->db->from('ethas_variables');
        $this->db->where('class', 'STATE');
        $this->db->where('id_app', $idApp);
        $query = $this->db->get();
        return ($query->result_array());
    }
    /**
     *Permite ver los datos de las variable sde estado actuales
     * es decir que obtenemos los valores que tienen estas variables en los usuariios
     * y sus respectivas cantidades.
     * @param string $idApp   
     * @return array resultados con los datos de las variables de estado.
     */
    function getActualStateVariables($idApp){
       $this->db->select('ethas_variables.name, ethas_statevariable.value, count( ethas_variables.id_variable )');
       $this->db->from('ethas_variables');
       $this->db->where("ethas_variables.id_app", $idApp);
       $this->db->group_by("ethas_statevariable.value");
       $this->db->join('ethas_statevariable', 'ethas_variables.id_variable = ethas_statevariable.id_variable', 'inner');
       $result = $this->db->get();
       /* $result = $this->db->query("SELECT ethas_variables.name, ethas_statevariable.value, count( ethas_variables.id_variable )
                        FROM ethas_variables
                        INNER JOIN ethas_statevariable ON ethas_variables.id_variable = ethas_statevariable.id_variable
                        WHERE ethas_variables.id_app = '$idApp'
                        GROUP BY ethas_statevariable.value");*/
        return $result->result_array();
    }
    /**
     * Obtiene los datos de una variable especifica con un valor determinado
     * @param string $idApp identificador de la app
     * @param stirng $idVariable id de la variable 
     * @param type $value valor a buscar en las variables de estado.
     * @return array datos con los usuarios de que tienen la variable y el valor especificado.
     */
    function getSpecificStateVariable($idApp,$idVariable, $value){
        $this->db->select('ethas_statevariable.value,  count(ethas_statevariable.id_download)');
        $this->db->from('ethas_variables');
        $this->db->where("ethas_variables.id_app", $idApp);
        $this->db->where("ethas_variables.id_variable", $idVariable);
        $this->db->where("ethas_statevariable.value", $idVariable);
        $this->db->join('ethas_statevariable', 'ethas_variables.id_variable = ethas_statevariable.id_variable', 'inner');
        $result = $this->db->get();
        /*$result = $this->db->query("SELECT  ethas_statevariable.value,  count(ethas_statevariable.id_download)
                        FROM ethas_variables
                        INNER JOIN ethas_statevariable ON ethas_variables.id_variable = ethas_statevariable.id_variable
                        WHERE ethas_variables.id_app = '$idApp' and ethas_variables.id_variable =  '$idVariable'
                        and ethas_statevariable.value = '$value'");*/
        return $result->result_array();
    }
    
    /**
     *Permite ver los datos de las variable sde estado actuales
     * es decir que obtenemos los valores que tienen estas variables en los usuariios
     * y sus respectivas cantidades.
     * @param string $idApp   
     * @return array resultados con los datos de las variables de estado.
     */
    function getStateVariableData($idApp,$idVariable){
        $this->db->select('ethas_variables.name as name, ethas_statevariable.value as value, count( ethas_statevariable.id_download ) as downloads');
        $this->db->from('ethas_variables');
        $this->db->where("ethas_variables.id_app", $idApp);
        $this->db->where("ethas_variables.id_variable", $idVariable);
        $this->db->join('ethas_statevariable', 'ethas_variables.id_variable = ethas_statevariable.id_variable', 'inner');
        $this->db->group_by(ethas_statevariable.value);
        $result = $this->db->get();
       /* $result = $this->db->query("SELECT ethas_variables.name as name, ethas_statevariable.value as value, count( ethas_statevariable.id_download ) as downloads
                        FROM ethas_variables
                        INNER JOIN ethas_statevariable ON ethas_variables.id_variable = ethas_statevariable.id_variable
                        WHERE ethas_variables.id_app = '$idApp'
                           and ethas_variables.id_variable = '$idVariable'
                        GROUP BY ethas_statevariable.value");*/
        return $result->result_array();
    }
    
    

}