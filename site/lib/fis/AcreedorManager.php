<?php
require_once '/home/qjvixmoy/lasolucion/lib/db.php';
require_once '/home/qjvixmoy/lasolucion/lib/entities/Acreedor.php';
//require_once '/home/qjvixmoy/lasolucion/lib/entities/Prestamo.php';
require_once '/home/qjvixmoy/lasolucion/lib/manager/AbstractManager.php';


/**
 * Description of AcreedorManager
 *
 * @author cesar
 */
class AcreedorManager extends AbstractManager {
    
    function __construct() {
        $this->class_name = "Acreedor";
        $this->table_name = "acreedor";
        parent::__construct();
    }
    
    function exists($entity, &$error) {
        return FALSE;
    }
    
    public function okData($entity, &$error) {
        return TRUE;
    }
    
    /**
     * El acreedor que debe firmar el pagaré y todos los documentos.
     */
    public static function getAcreedorActual($fecha, $pais_id) {
        $where = "fecha_inicio <= ? AND (fecha_fin IS NULL OR fecha_fin >= ?) AND pais_id = ?";
        $params = array($fecha, $fecha, $pais_id);
        return $this->findWhere($where, $params);
	}
}
