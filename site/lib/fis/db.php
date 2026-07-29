<?php

require_once '/home/qjvixmoy/lasolucion/lib/config.php';
require_once '/home/qjvixmoy/lasolucion/lib/vendor/adodb/adodb-php/adodb.inc.php';

class Db {
    
    /**
     * Conexion ado a la base de datos
     * @return ADOConnection
     */
    public static function Connect() {
        global $CFG_DB_HOST;
        global $CFG_DB_TYPE;
        global $CFG_DB_LOGIN;
        global $CFG_DB_PASSWORD;
        global $CFG_DB;
        $db = NewADOConnection($CFG_DB_TYPE);
        $ret = $db->Connect($CFG_DB_HOST, $CFG_DB_LOGIN, $CFG_DB_PASSWORD, $CFG_DB);
        if ($ret) {
            $db->SetFetchMode(ADODB_FETCH_ASSOC); // Retornar campos como arreglo asociativo
        } else {
            throw new Exception(sprintf("Error [Db::Connect] [%s]", $db->errorMsg()));
            $db = NULL;
        }
        return $db;
    }
    
    /**
     * Retorna un recordset
     * 
     * @param string $sql SQL dela consulta a la base de datos
     * @param array $param Parámetros de la instrucción SQL, se asocian 1 a 1 a un ? 
     * @return adodbRs 
     */
    public static function Execute($sql, $params = NULL) {
        return self::Rs($sql, $params);
    }
    
    /**
     * Retorna un recordset
     * 
     * @param string $sql SQL de la consulta a la base de datos
     * @param array $param Parámetros de la instrucción SQL, se asocian 1 a 1 a un ? 
     * @return adodbRs 
     */
    public static function Rs($sql, $params = NULL) {
        //error_log("Rs: " . $sql, 0);
        $db = self::Connect();
        if (!is_null($params)) {
            $rs = $db->execute($sql, $params);
        } else {
            $rs = $db->execute($sql);
        }
        if (!$rs) {
            throw new Exception("Error [Db::Rs]: " . $db->errorMsg());
        }
        return $rs;
    }

    /**
     * Retorna un recordset
     * 
     * @param string $sql SQL de la consulta a la base de datos
     * @param array $param Parámetros de la instrucción SQL, se asocian 1 a 1 a un ? 
     * @return array 
     */
    public static function GetAssoc($sql, $params = NULL) {
        $db = self::Connect();
        if (!is_null($params)) {
            $arr = $db->getAssoc($sql, $params);
        } else {
            $arr = $db->getAssoc($sql);
        }
        if (!$arr) {
            throw new Exception("Error [Db::getAssoc]: " . $db->errorMsg());
        }
        return $arr;
    }
    
    public static function Delete($tableName, $where) {
        $db = self::Connect();
        $db->StartTrans();
        $sql = "DELETE FROM " . $tableName . " WHERE " . $where;
        $ret = $db->execute($sql);
        if ($ret === false) {
            $db->RollbackTrans();
            throw new Exception(sprintf("Error [Db::Delete] [%s]", $db->errorMsg()));
        }
        $db->CompleteTrans();
        return true;
    }

    public static function Save($manager, $entity) {
        $db = self::Connect();
        $id = $entity->getRecord()["id"];
        $rs = $manager->getRecordset($id);
        $db->StartTrans();
        if ($id === 0) {
            $sql = $db->GetInsertSQL($rs, $entity->getRecord(), ADODB_FORCE_NULL);
            $ret = $db->execute($sql);
        } else {
            $sql = $db->GetUpdateSQL($rs, $entity->getRecord(), ADODB_FORCE_NULL);
            $ret = $db->execute($sql, array($id));
        }
        //error_log($sql, 0);
        if (!$ret) {
            $db->RollbackTrans();
            throw new Exception(sprintf("Error [Db::Save] [%s]", $db->errorMsg()));
        } else if ($id === 0) { // Sólo mysql
            // Es un insert, buscamos el último id generado. para mysql/mariadb
            $ret = $db->execute("SELECT LAST_INSERT_ID() as lastId");
            $row = $ret->fetchRow();
            $id = $row["lastId"];
        }
        $db->CompleteTrans();
        return $id;
    }
    
    /**
     * Evaluacion de funciones escalares de mysql
     */
    public static function CallF($fn, $params = NULL) {
        $db = self::Connect();
        if (count($params) > 0) {
            $sql = "SELECT " . $fn . "(" . implode(",", array_fill(0, count($params), "?")) . ")";
        } else {
            $sql = "SELECT " . $fn . "()";
        }
        return $db->GetOne($sql, $params);
    }

    /**
     * Llamado de procedimientos de mysql
     */
    public static function CallP($fn, $params = NULL) {
        $db = self::Connect();
        if (count($params) > 0) {
            $sql = "CALL " . $fn . "(" . implode(",", array_fill(0, count($params), "?")) . ")";
        } else {
            $sql = "CALL " . $fn . "()";
        }
        $ret = $db->execute($sql, $params);
        if ($ret === false) {
           throw new Exception(sprintf("Error [Db::CallP] [%s]", $db->errorMsg())); 
        }
    }
    
    /**
     * Retorno de valor único
     */
    public static function GetOne($sql, $params = NULL) {
        error_log("SQL: " . print_r($sql, TRUE), 0);
        $db = self::Connect();
        if (!is_null($params)) {
            $ret = $db->GetOne($sql, $params);
        } else {
            $ret = $db->GetOne($sql);
        }
        if ($ret === false) {
            throw new Exception(sprintf("Error [Db::GetOne] [%s]", $db->errorMsg()), 0);
        }
        return $ret;
    }
    
    /**
     * Retorno de record único asociativo ?
     */
    public static function GetRow($sql, $params = NULL) {
        $db = self::Connect();
        if (!is_null($params)) {
            $ret = $db->GetOne($sql, $params);
        } else {
            $ret = $db->GetOne($sql);
        }
        if ($ret === false) {
            throw new Exception(sprintf("Error [Db::GetRow] [%s]", $db->errorMsg()), 0);
        }
        return $ret;
    }
    
    public static function getConfig($full = FALSE) {
        $db = self::Connect();
        if ($full) {
            return $db->GetAssoc("SELECT item, value, description FROM config;");
        } else {
            return $db->GetAssoc("SELECT item, value FROM config;");
        }
    }
    
}
