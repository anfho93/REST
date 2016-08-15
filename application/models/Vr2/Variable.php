<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
require_once BASEPATH . 'libraries/aws/aws-autoloader.php';

use Aws\DynamoDb\Iterator\ItemIterator;

/**
 * Clase que representa al Modelo de las Variables.
 *
 * Esta clase mapea la tabla ethas_variables de  la base de datos no relacional de DynamoDB.
 *
 *  @author Andres felipe Herrera <anfho93@gmail.com>
 *  @version 1.1
 */
class Variable extends CI_Model {

    /**
     * @var string Variable que representa el HashKey de la tabla id_app
     */
    var $hashname = "id_variable";

    /**
     * @var string Variable que representa el RangeKey de la tabla id_app
     */
    var $rangename = "id_app";

    /**
     * @var  string nombre de la tabla
     */
    var $tablename = "ethas_variables";
    var $tablenameab = "ethas_abvariable";

    /**
     * @var Array    utilizado para almacenas valores de las llaves.
     */
    var $keys = array();

    /**
     * @var Array     utilizado para almacenas valores de los atributos a agregar a un Item de la tabla..
     */
    var $attributes = array();

    /**
     * Constructor de la clase.
     */
    function __construct() {
        // Call the Model constructor
        parent::__construct();
    }

    /**
     * Esta funcion Carga todas las variables pro defecto en la aplicacion que esta registradas
     * @param string $idApp, id de la aplicacion.
     * @param boolean  $withDetails, si se requieren las variables con detalles o no
     * @return array conjunto de variables
     */
    function getVariables($idApp, $withDetails = false) {
        $arrResponse = array();
        $result = $this->db->get_where($this->tablename, array("id_app" => $idApp));
        $result = $result->result_array();
        if (count($result) > 0) {
            if ($withDetails) {
                foreach ($result as $row) {
                    $arrResponse[] = array("id_variable" => $row["id_variable"], "name" => $row["name"], "value" => $row["value"], "class" => $row["class"], "icon" => $row["icon"]);
                }
            } else {
                foreach ($result as $row) {
                    $arrResponse[$row["name"]] = $row["value"];
                }
            }
        }
        return $arrResponse;
    }

    /**
     * Funcion que permite obtener una variable por medio de  su nombre
     * @param string $idApp  identificador de la aplicacion.
     * @param string $name  nombre de la variable.
     * @return array conjunto de variables
     */
    function getVariable($idApp, $name) {
        $arrResponse = array();
        $result = $this->db->get_where($this->tablename, array("id_app" => $idApp, "name" => $name));

        $resulta = $result->result_array();
        //print_r($resulta);
        if (count($resulta) > 0) {
            foreach ($resulta as $row) {
                //$arrResponse[$row["name"]] = $row["value"];                                 
                $arrResponse = $row;
            }
        }
        return $arrResponse;
    }

    /**
     * Permite obtener una variable de una aplicacion
     * @param type $idApp identificador de la aplicacion
     * @param type $id identificador de la variable
     * @return Object objeto con los datos dicha variable.
     */
    function getVariableByID($idApp, $id) {
        $objResponse = null;
        $result = $this->db->get_where($this->tablename, array("id_app" => $idApp, "id_variable" => $id));
        // print_r($this->tablename." ".$idApp . " - ".$id);
        //print_r($result->);
        if ($result->num_rows() > 0) {
            $objResponse = $result->first_row();
        }
        return $objResponse;
    }

    /**
     * Permite obtener una variable de una aplicacion
     * @param type $id identificador de la variable
     * @return Object objeto con los datos dicha variable.
     */
    function obtenerVariable($id) {
        $objResponse = null;
        $result = $this->db->get_where($this->tablename, array("id_variable" => $id));
        //  print_r($result->first_row());
        if ($result->first_row()!=null) {
            $objResponse = $result->first_row();
        }
        return $objResponse;
    }

    /**
     * Permite modificar las condiciones y valores de una codicion variable AB 
     * @param String $idVar identificador de la variable
     * @param String $idABVar identififcador de una condicion de variable A/B 
     * @param JSON $cond nuevas condiciones para la variable
     * @param JSON  $values nuevos valores para la variable  que cumpla dichas condiciones
     * @return boolean confirmacion si se pudo actualizar o no los datos.
     */
    function modifyABVariable($idVar, $idABVar, $cond, $values) {
        if ($this->isJson($cond) && $this->isJson($values)) {
            $data = array("conditions" => $cond, "values" => $values);
            $result = $this->db->update($this->tablenameab, $data, array('id_abvariable' => $idABVar, "id_variable" => $idVar));
            return $result;
        } else {
            return false;
        }
    }

    private function isJson($string) {
        //if (is_string($string)) {
            json_decode($string);
            return (json_last_error() == JSON_ERROR_NONE);
        //}return false;
    }

    /**
     * Permite obtener una variable AB basado en su Id
     * @param type $idVar identificador de la variable AB
     * @return Object objeto que representa la variable AB
     */
    function getABVariable($idVar) {
        $objResponse = array();
        $result = $this->db->get_where($this->tablenameab, array("id_variable" => $idVar));
        if ($result->num_rows() > 0) {
            $objResponse = $result->result_array();
        }
        return $objResponse;
    }

    /**
     * Eliminar una condicion de la variable AB
     * @param type $idVar identificador de la variable
     * @param type $idABVar id de la condicion de la variable.
     * @return boolean  si se elimina o no dicha la condcion
     */
    public function deleteABCond($idVar, $idABVar) {
        $response = $this->db->delete($this->tablenameab, array("id_abvariable" => $idABVar,
            "id_variable" => $idVar));

        return $response;
    }

    /**
     * Esta funcion modifica una variable .
     * @param type $idApp identificador de la aplicacion
     * @param type $id identificador de la variable
     * @param type $value Valor de la nueva variable
     * @param type $icon icono de la nueva variable por defecto "none"
     * @return boolean respuesta si la variable fue o no actualizada
     */
    function modifyVariable($idApp, $id, $value, $icon = "none") {

        if ($this->isJson($value)) {
            $data = array(
                'value' => $value,
                'icon' => $icon
            );
            $this->db->where('id_variable', $id);
            return $this->db->update($this->tablename, $data);
        }
        return false;
    }

    /**
     *  Permite agregar una nueva condición a una variable de tipo AB
     * @param type $idVariable identificador de la variable
     * @param type $conds reglas de la condicion
     * @param type $values valores para dicha condicion.
     * @param type $condName nombre de la nueva condicion 
     * @return boolean respuesta, si pudo ser agregada o no.
     */
    public function registerCond($idVariable, $conds, $values, $condName) {
        $data = array(
            'values' => $values,
            'conditions' => $conds,
            'name' => $condName,
            'id_variable' => $idVariable
        );
        $this->db->insert($this->tablenameab, $data);
        if ($this->db->_error_number() == 1062) {//duplicado de indice
            return false;
        }
        return true;
    }

    /**
     * Esta funcion permite obtener el id inicial
     * de una variable de estado, pero no su estado final. 
     */
    function getStateVariable($idApp, $name) {

        $result = $this->db->get_where($this->tablename, array("id_app" => $idApp, "name" => $name, "class" => "STATE"));
        $result = $result->result_array();
        if (count($result) == 0)
            return false;
        else
            return $result[0]["id_variable"];
    }

    /**
     * Funcion que permite registrar una variable.
     * @param string $name  nombre de la variable.
     * @param string $value  clase o tipo de la variable.
     * @param string $icono  url dekl icono o bits de la imnagen de la variable.
     * @param string $idApp id de la aplicacion a la que pertenecen las variables.
     * @return string|bool , verdadero si se registra la var, false en caso deno registrar la variable.
     */
    function registerVariable($name, $value, $class, $icon, $idApp, $cond = null) {

        $this->keys[$this->hashname] = uniqid("", true);
        $this->keys[$this->rangename] = $idApp;
        $this->attributes["name"] = strtolower($name);
        $this->attributes["value"] = $value;
        $this->attributes["class"] = $class;
        $this->attributes["icon"] = $icon;
        if ($cond != null) {
            $this->attributes["cond"] = $cond;
        }

        $array = $this->getVariable($idApp, strtolower($name));
        if (count($array) > 0) {
            return false;
        }
        $this->keys = array_merge($this->keys, $this->attributes);
        $result = $this->db->insert($this->tablename, $this->keys);
        if ($result) {
            return $this->keys[$this->hashname];
        } else
            return false;
    }

    /**
     * Elimina una variable de una app.
     * @param string $idApp id de la aplicacion a la que pertenecen las variables.
     * @param string $id_variable id de la variable
     * @return si se elimina la aplicacion.
     */
    function delete($id_variable, $idApp) {
        return $this->db->delete($this->tablename, array('id_variable' => $id_variable, "id_app" => $idApp));
    }

    /*
     *
     * Migra datos desde un archivo generado
     * por Dynamo a la base de datos de MYSQL      
     */

    function migrateDatatoMYSQL() {
        //value timestamp id_variable_app id_download
        $myfile = fopen("C://xampp/htdocs/Web/DynamoDB/variables.out", "r") or die("Unable to open file!");
        $key = explode(" ", fgets($myfile));

        $var = array();
        foreach ($key as $keys => $value) {

            $key[$keys] = trim($value);
        }
        print_r($key);
        while (!feof($myfile)) {
            $data = (explode(" ", fgets($myfile)));
            for ($i = 0; $i < sizeof($key); $i++) {
                $key[$i] = trim($key[$i]);
                $var[$key[$i]] = trim($data[$i]);
            }
            print_r($this->db->insert("ethas_variables", $var));
        }
    }

}

?>