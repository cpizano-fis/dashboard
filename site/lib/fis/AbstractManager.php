<?php
require_once '/home/qjvixmoy/lasolucion/lib/utils.php';
require_once '/home/qjvixmoy/lasolucion/lib/db.php';
require_once '/home/qjvixmoy/lasolucion/lib/vendor/adodb/adodb-php/adodb.inc.php';
require_once '/home/qjvixmoy/lasolucion/lib/entities/AbstractEntity.php';

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

abstract class AbstractManager {
    
    protected $table_name = NULL;
    // protected $pk_field = NULL; Se reemplaza por id en todas las tablas
    protected $order = NULL;
    protected $class_name = NULL;
    protected $config = NULL;
    protected $autonumerico = TRUE;
            
    function __construct() {
        if (!Utils::noEmptyV($this->table_name, $this->class_name)) {
            throw new Exception("Error [" . get_class($this) . "]: No se ha definido el nombre de la tabla o el nombre de la clase.");
        }
    }
    
    function create($row, $status = AbstractEntity::ST_EDIT) {
        $entity = new $this->class_name($row);
        $entity->setEditStatus($status);
        return $entity;
    }
        
    public function getRecordset($param) {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE id = ?;";
        return Db::Rs($sql, array($param));
    }
    
    /**
     * 
     * @param int $param id pk
     * @return Objeto de la clase $class_name
     */
    public function find($param) {
        if (is_null($param)) {
            return NULL;
        }
        if ($param !== 0) {
            $rs = $this->getRecordset($param);
            if ($rs->RecordCount() != 0) {
                return $this->create($rs->fetchRow());
            } elseif ($this->autonumerico) { // Nuevo cuando el id no es autonumerico!
                return $this->create(array("id" => $param), AbstractEntity::ST_NEW);
            } else {
                throw new Exception("Error [" . get_class($this) . ".find]");
            }
        } else {
            // Todos los campos nulos, menos el id en 0
            return $this->create(array("id" => 0), AbstractEntity::ST_NEW);
        }
    }

    public function findWhere($where, $params) {
        $sql = "SELECT * FROM $this->table_name WHERE $where;";
        $rs = Db::Rs($sql, $params);
        $n = $rs->RecordCount();
        if ($n != 0) {
            if ($n == 1) {
                return $this->create($rs->fetchRow());
            } else {
                $ret = array();
                $arr = $rs->GetArray();
                foreach ($arr as $row) {
                    $ret[] = $this->create($row);
                }
                return $ret;
            }
        } else {
            return null;
        }
    }
    
    public function findByField($field, $value) {
        return $this->findWhere("$field = ?", array($value));
    }
    
    function getSQLSearch($sql) {
        $rs = Db::Rs($sql);
        return $rs->GetArray();
    }
    
    function getSearch($value, $text, $where = NULL) {
        $sql = "SELECT $text as text, $value as value FROM " . $this->table_name . (is_null($where) ? "" : " WHERE " . $where) . " ORDER BY text;";
        return $this->getSQLSearch($sql);
    }
        
    /**
     * retorna #pagina y el índice dentro de la página.
     * @param AbstractEntity $entity
     */
    function getPosicion($entity) {
        if (is_null($this->order)) {
            throw new Exception("Error [" . get_class($this) . ".getPosicion]: necesita establecer un orden.");
        }
        $value = $this->getOne("SELECT $this->order FROM $this->table_name WHERE id = ?", array($entity->getId()));
        $sql = "SELECT count(*) FROM $this->table_name where $this->order < ?";
        $pos = $this->getOne($sql, array($value));
        $pagina = intval($pos / Utils::getConfig()["pagina_max"]) + 1;
        $indice = $pos % Utils::getConfig()["pagina_max"];
        return array("id" => $entity->getId(), "value" => $value, "page" => $pagina, "ix" => $indice);
    }
    
    function getBusqueda() {
        /*Migrar a postgres*/
        $sql = "SELECT id, $this->order AS value, " .
            "(@row := @row + 1) div " . Utils::getConfig()["pagina_max"] . " + 1 AS page, @row % " . Utils::getConfig()["pagina_max"] . " AS ix FROM $this->table_name, (SELECT @row := -1) r ORDER BY value;";
        return $this->getArray($sql);
    }
    
    /*
    function getPaginasSql($sql, $params = NULL) {
        $total = doubleval($this->countSql($sql, $params));
        $pageSize = Utils::getConfig()["pagina_max"];
        $paginas = floor($total / $pageSize);
        if ($paginas * $pageSize < $total) {
            $paginas++;
        }
        return $paginas;
    }*/
    
    function getPaginas($where = NULL, $params = NULL) {
        $total = doubleval($this->count($where, $params));
        $pageSize = Utils::getConfig()["pagina_max"];
        $paginas = floor($total / $pageSize);
        if ($paginas * $pageSize < $total) {
            $paginas++;
        }
        return $paginas;
    }
    
    function getPagesData($page, $where, $params, $maxPage = NULL) {
        $maxPage = $this->getPaginas($where, $params);
        error_log("getPagesData maxPage: " . print_r($maxPage, TRUE), 0);
        //$nPages = Utils::getConfig()["n_paginas"];
        $nPages = 3;
        $middle = floor($nPages / 2);
        $pData = array("ini" => $page - $middle, "end" => $page + $middle, "prev" => TRUE, "next" => TRUE, "page" => $page);
        if ($pData["ini"] < 0) {
            $pData["end"] -= $pData["ini"]; // Es negativo
            $pData["ini"] = 0;
            $pData["prev"] = FALSE;
        }
        if ($maxPage < $nPages || $pData["end"] >= $maxPage) {
            $pData["end"] = $maxPage - 1;
            $pData["next"] = FALSE;
            if ($pData["end"] >= $nPages) {
                $pData["ini"] = $pData["end"] - $nPages + 1;
            }
                
        }
        /*else if ($page - $middle < 0) {
            $pData = array("ini" => 0, "end" => $page + $middle, "prev" => FALSE, "next" => TRUE);
            if ($page + $middle + 1 >= $maxPage) {
                $pData["end"] = $maxPage - 1;
                $pData["next"] = FALSE;
            }
        } else {
            $pData = array("ini" => $page - $middle, "end" => $page + $middle + 1, "prev" => TRUE, "next" => TRUE);
            if ($page + $middle + 1 >= $maxPage) {
                $pData["end"] = $maxPage - 1;
                $pData["next"] = FALSE;
            }
        }*/
        error_log("getPagesData: " . print_r($pData, TRUE), 0);
        return $pData;
    }
    
    function getArray($sql, $params = NULL) {
        //error_log("getArray: " . print_r($sql, TRUE), 0);
        $rs = Db::Rs($sql, $params);
        return $rs->GetArray();
    }
    
    function getSql($where = NULL, $limit = NULL, $offset = NULL) {
        $sql = "SELECT * FROM " . $this->table_name;
        $sql .= (is_null($where) || strlen($where) == 0) ? "" : " WHERE " . $where;
        $sql .= is_null($this->order) ? "" : " ORDER BY " . $this->order;
        $sql .= is_null($limit) ? "" : " LIMIT " . $limit;
        $sql .= is_null($offset) ? "" : " OFFSET " . $offset;
        return $sql;
    }
    
    function getAllSql($sql, $params = NULL) {
        //error_log("getAllSql: " . print_r($sql, TRUE), 0);
        $arr = self::getArray($sql, $params);
        $ret = array();
        foreach ($arr as $row) {
            $ret[] = $this->create($row);
        }
        return $ret;
    }
    
    function getAll($where = NULL, $params = NULL, $limit = NULL, $offset = NULL) {
        $sql = self::getSql($where, $limit, $offset);
        //error_log("getAll: " . $sql, 0);
        return $this->getAllSql($sql, $params);
    }
    
    /**
     * Ejecuta la función propia de la clase $fn en cada objeto, no retorna nada.
     * @param mixed $fn Nombre de la función
     * @param mixed $where 
     * @param mixed $params 
     * @param mixed $limit 
     * @param mixed $offset 
     * @return  
     */
    function runAll($fn, $where = NULL, $params = NULL, $limit = NULL, $offset = NULL) {
        $sql = self::getSql($where, $limit, $offset);
        $arr = self::getArray($sql, $params);
        foreach ($arr as $row) {
            $ret = $this->create($row);
            $ret->$fn(); // Se ejecuta la función
        }
    }
    
    /**
     * Ejecuta la función externa $fn que se le entrega cada objeto, no retorna nada.
     * @param mixed $fn Nombre de la función
     * @param mixed $where 
     * @param mixed $params 
     * @param mixed $limit 
     * @param mixed $offset 
     * @return  
     */
    function runAllEx($fn, $where = NULL, $params = NULL, $limit = NULL, $offset = NULL) {
        $sql = self::getSql($where, $limit, $offset);
        $arr = self::getArray($sql, $params);
        foreach ($arr as $row) {
            $ret = $this->create($row);
            $fn($ret); // Se ejecuta la función
        }
    }
    
    /*
    public function getPaginaSql($pagina, $sql, $params = NULL) {
        $pageSize = Utils::getConfig()["pagina_max"];
        return $this->getAllSql($sql, $params, $pageSize, $pagina * $pageSize);
    }*/
    
    public function getPagina($pagina, $where = NULL, $params = NULL) {
        $pageSize = Utils::getConfig()["pagina_max"];
        return $this->getAll($where, $params, $pageSize, $pagina * $pageSize);
    }
    
    public function count($where = NULL, $params = NULL) {
        $sql = "SELECT count(*) FROM " . $this->table_name . (empty($where) ? "" : " WHERE " . $where);
        return Db::GetOne($sql, $params);
    }
    
    public function has($where, $params = NULL) {
        if (is_null($where)) {
            throw new Exception("Error [" . get_class($this) . "._has] sin where");
        }
        $sql = "SELECT 1 FROM " . $this->table_name . " WHERE " . $where . " LIMIT 1";
        return !is_null(Db::GetOne($sql, $params));
    }
    
    /**
     * 
     * @param AbstractEntity $entity
     * @return Integer
     */
    public function save(&$entity) {
        // Garantizar nulos
        if (count($entity->getNulls()) > 0) {
            foreach ($id = $entity->getRecord() as $key => $value) {
                if (array_search($key, $entity->getNulls()) !== FALSE && !Utils::noEmpty($value)) {
                    $entity->setField($key, NULL);
                }
            }
        }
        // Esto no funciona si no es por referencia $entity
        $entity->setId(Db::Save($this, $entity));
        $entity->refresh(TRUE); // Forzar recarga de FK
        return $entity->getId();
    }
    
    /**
     * 
     * @param AbstractEntity $entity
     * @return Boolean
     */
    public function delete(&$entity) {
        $ret = Db::Delete($this->table_name, "id = " . $entity->getId());
        if ($ret) {
            $entity->setEditStatus(AbstractEntity::ST_DELETED);
        }
        return $ret;
    }
    
    public function Execute($sql, $params) {
        return Db::Execute($sql, $params);
    }
    
    public function getOne($sql, $params) {
        return Db::GetOne($sql, $params);
    }
    
    public function getRow($sql, $params) {
        return Db::GetRow($sql, $params);
    }
    
    public function CallF($fn, $params) {
        return Db::CallF($fn, $params);
    }
    
    public function getTableName() {
        return $this->table_name;
    }
    
    protected function getSelect() {
        return "SELECT * FROM " . $this->table_name;
    }
    
    // Para PK no autonumérico
    public function getNextPk() {
        return Db::GetOne("SELECT max(id) + 1 FROM $this->table_name");
    }
    
    public function setOrder($order) {
        $this->order = $order;
    }
    
    abstract function exists($entity, &$error);
    
}
