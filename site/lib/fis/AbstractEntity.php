<?php
require_once '/home/qjvixmoy/lasolucion/lib/db.php';

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
 
 class Fk {
 
    private $class_name;
    private $id;
    private $value = NULL;
    private $on_demand = FALSE;
    
    public function __construct($class_name, $id, $on_demand = FALSE) {
        $this->class_name = $class_name;
        $this->id = $id;
        $this->on_demand = $on_demand;
    }
    
    public function setClassName($class_name) {
        $this->class_name = $class_name;
    }
    
    public function getClassName() {
        return $this->class_name;
    }
    
    public function setId($id) {
        $this->id = $id;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function setValue($value) {
        $this->value = $value;
    }
        
    public function getValue() {
        return $this->value;
    }
            
    public function onDemand() {
        return $this->on_demand;
    }
 }
 
 class One2Many {
    
    private $id;
    private $class_name;
    private $values = NULL;
    private $on_demand = FALSE;
    
    /**
     * 
     * @param mixed $class_name La clase a la que pertenecen los objectos relacionados 'Many'
     * @param mixed $id El nombre del id en la clase relacionada 'Many'
     * @param mixed $on_demand Se carga en el constructor del la clase 'One'?
     * @return  
     */
    public function __construct($class_name, $id, $on_demand = FALSE) {
        $this->id = $id;
        $this->class_name = $class_name;
        $this->on_demand = $on_demand;
    }
    
    public function setId($id) {
        $this->id = $id;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function setClassName($class_name) {
        $this->class_name = $class_name;
    }
    
    public function getClassName() {
        return $this->class_name;
    }
    
    public function setValues($values) {
        $this->values = $values;
    }
    
    public function addValue($value) {
        $this->values[] = $value;
    }
        
    public function getValues() {
        return $this->values;
    }
            
    public function onDemand() {
        return $this->on_demand;
    }
    
 }

/**
 * $fn Es el nombre de una función que retorna un valor exclusivamente con el Id de la clase
 */
 
 class FnExe {
 
    private $fn;
    private $on_demand = TRUE;
    private $value = NULL;
    
    public function __construct($fn, $on_demand = TRUE) {
        $this->fn = $fn;
        $this->on_demand = $on_demand;
    }
    
    public function setFn($fn) {
        $this->fn = $fn;
    }
    
    public function getFn() {
        return $this->fn;
    }
    
    public function setValue($value) {
        $this->value = $value;
    }
        
    public function getValue() {
        return $this->value;
    }
    
    public function onDemand() {
        return $this->on_demand;
    }
}

/**
 * AbstractEntity es la clase abstracta sobre la que se basan todas 
 * las clase que contienen un registro de una tabla de la base de datos.
 *
 * @author cesar
 */
abstract class AbstractEntity {

    protected $record = array();
    //protected $id = NULL; Se reemplaza por id constante en todas las tablas
    protected $nulls = array();
    protected $fk = array();
    protected $o2m = array();
    protected $fn = array();
    protected $fields = NULL;
    protected $edit_status = NULL;

    const ST_NEW = 0;
    const ST_EDIT = 1;
    const ST_DELETED = 2;

    const ENABLE = 0;
    const DISABLE = 1;
    
    /**
     * 
     * @param mixed $row Valores devueltos por la base de datos
     * @param mixed $nulls Campos que se deben poner en NULL si no se asignó valor
     */
    public function __construct($row, $nulls = array()) {
        if (!is_null($this->fields)) {
            // Inicializar el arreglo con NULL en cada posición
            $keys = explode(",", $this->fields);
            $values = array_fill(0, count($keys), NULL);
            $init_record = array_combine($keys, $values);
        } else {
            // No se definieron campos, se inicializa con arreglo vacío. $row asignará campos
            $init_record = array();
        }
        $this->record = array_merge($init_record, $row);
        $this->nulls = $nulls;
        $this->refresh();
    }
    
    /**
     * refresh: Actualiza todos los valores que se deben consultar en la base de datos.
     * @param mixed $force Se debe poner en NULL los que son OnDemand y volverlos a consultar en caso de ser necesario
     * @return  
     */
    public function refresh($force = FALSE) {
        $this->setFk($force);
        $this->setO2M($force);
        $this->setFn($force);
    }

    public function getEditStatus() {
        return $this->edit_status;
    }
    
    public function setEditStatus($edit_status) {
        $this->edit_status = $edit_status;
    }
    
    public function getRecord() {
        return $this->record;
    }
    
    public function setId($id) {
        $this->record["id"] = $id;
    }
    
    public function getId() {
        return $this->record["id"];
    }
    
    public function nuevo() {
        return $this->edit_status === self::ST_NEW;
    }
    
    public function getNulls() {
        return $this->nulls;
    }
    
    public function setField($field, $value) {
        $this->record[$field] = $value;
    }
    
    /**
     * Agregar una clave foránea
     * @param mixed $class_name 
     * @param mixed $id 
     * @param mixed $on_demand : Se carga sólo cuando se vaya a usar, requerido si se usa otro nombre para el índice
     * @param mixed $ix : Índice en el arreglo de claves foráneas 'fk', si no se entrega, se usa el nombre de la clase
     * @return  
     */
    public function addFk($class_name, $id, $on_demand = FALSE, $ix = NULL) {
        if (is_null($ix)) {
            $ix = $class_name;
        }
        // Verificar que el índice sea único
        if (array_key_exists($ix, $this->fk)) {
            throw new Exception("Error [" . get_class($this) . ".addFk]: Ya existe el índice $ix.");
        }
        $this->fk[$ix] = new Fk($class_name, $id, $on_demand);
    }
    
    public function getFk($ix) {
        if (!array_key_exists($ix, $this->fk)) {
            throw new Exception("Error [" . get_class($this) . ".getFk]: No existe FK[$ix].");
        }
        // Verificamos si el objeto se crea bajo demanda, cuando es null.
        if (is_null($this->fk[$ix]->getValue()) && $this->fk[$ix]->onDemand()) {
            $this->createFk($ix);
        }
        return $this->fk[$ix]->getValue();
    }
    
    public function setFk($force = FALSE) {
        //error_log("******************** setFk", 0);
        if (!$this->nuevo() && count($this->fk) > 0) {
            // $ix Índice, Nombre de clase
            foreach($this->fk as $ix => $fk) {
                //error_log("******************** setFk[$ix]", 0);
                if ($force) {
                    // Se restablecen todos los valores, los OnDemand se espera que sean solicitados
                    $this->fk[$ix]->setValue(NULL);
                }
                // Si este objeto no es por demanda o ya se ha cargado la FK
                if (!$fk->onDemand() || ($fk->onDemand() && !is_null($fk->getValue()))) {
                    //error_log("******************** createFk[$ix]", 0);
                    $this->createFk($ix);
                }                
            }
        }
    }
    
    private function createFk($ix) {
        try {
            // Si la FK es NULL, no lo buscamos y establecermos el valor en NULL
            $mng_name = $this->fk[$ix]->getClassName() . "Manager"; // Se debe incluir en la respectiva clase
            $mng = new $mng_name(); // El manager de la clase
            $value = $mng->find($this->record[$this->fk[$ix]->getId()]);
            /*error_log("******************** Manager $ix:" . $mng_name, 0);
            error_log("******************** Id $ix:" . $this->fk[$ix]->getId(), 0);
            error_log("******************** Id Value $ix:" . $this->record[$this->fk[$ix]->getId()], 0);*/
            $this->fk[$ix]->setValue($value);
        } catch (Exception $e) {
            error_log("Error [" . get_class($this) . ": No se pudo crear FK[$ix]");
        }
    }
    
    public function addO2m($class_name, $id, $on_demand = FALSE, $ix = NULL) {
        if (is_null($ix)) {
            $ix = $class_name;
        }
        // Verificar que el índice sea único
        if (array_key_exists($ix, $this->o2m)) {
            throw new Exception("Error [" . get_class($this) . ".addO2m]: Ya existe el índice $ix.");
        }
        $this->o2m[$ix] = new One2Many($class_name, $id, $on_demand);
    }
    
    public function getO2M($ix) {
        if (!array_key_exists($ix, $this->o2m)) {
            throw new Exception("Error [" . get_class($this) . ".getO2m]: No existe O2M[$ix].");
        }
        // Verificamos si el objeto se crea bajo demanda
        if (is_null($this->o2m[$ix]->getValues()) && $this->o2m[$ix]->onDemand()) {
            $this->createO2m($ix);
        }
        return $this->o2m[$ix]->getValues();
    }
    
    public function setO2m($force = FALSE) {
        if (!$this->nuevo() && count($this->o2m) > 0) {
            // $ix Índice
            foreach($this->o2m as $ix => $o2m) {
                if ($force) {
                    // Se restablecen todos los valores, los OnDemand se espera que sean solicitados
                    $this->o2m[$ix]->setValues(NULL);
                }
                // Si este objeto no es por demanda o ya se ha cargado la o2m
                if (!$o2m->onDemand() || ($o2m->onDemand() && !is_null($o2m->getValues()))) {
                    $this->createO2m($ix);
                }
            }
        }
    }
    
    private function createO2m($ix) {
        try {
            $mng_name = $this->o2m[$ix]->getClassName() . "Manager"; // Se debe incluir en la respectiva clase
            $mng = new $mng_name();
            $values = $mng->findByField($this->o2m[$ix]->getId(), $this->getId());
            $this->o2m[$ix]->setValues($values);
        } catch (Exception $e) {
            error_log("Error [" . get_class($this) . ": No se pudo crear O2M[$ix], " . $e->getMessage());
        }
    }
    
    public function addFn($fn, $on_demand = TRUE) {
        $this->fn[$fn] = new FnExe($fn, $on_demand);
    }
    
    public function getFn($fn) {
        if (!array_key_exists($fn, $this->fn)) {
            throw new Exception("Error [" . get_class($this) . ".getFn]: No existe Fn[$fn].");
        }
        // Verificamos si el objeto se crea bajo demanda
        if (is_null($this->fn[$fn]->getValue()) && $this->fn[$fn]->onDemand()) {
            $this->createFn($fn);
        }
        return $this->fn[$fn]->getValue();
    }
    
    public function setFn($force = FALSE) {
        if (!$this->nuevo() && count($this->fn) > 0) {
            // $ix Índice
            foreach($this->fn as $ix => $fn) {
                if ($force) {
                    // Se restablecen todos los valores, los OnDemand se espera que sean solicitados
                    $this->fn[$ix]->setValue(NULL);
                }
                // Si este objeto no es por demanda o ya se ha cargado la fn
                if (!$fn->onDemand() || ($fn->onDemand() && !is_null($fn->getValue()))) {
                    $this->createFn($ix);
                }
            }
        }
    }
    
    /**
     * La función no depende de un manager. 
     * Se usa el manager de esta clase.
     * Depende exclusivamente del Id de la clase
     * @param mixed $fn
     * @return  
     */
    private function createFn($fn) {
        try {
            /*$mng_name = $this->class_name . "Manager";
            $mng = new $mng_name();*/
            // Depende exclusivamente de Id de esta clase
            $value = Db::CallF($this->fn[$fn]->getFn(), array($this->getId()));
            $this->fn[$fn]->setValue($value);
        } catch (Exception $e) {
            error_log("Error [" . get_class($this) . ": No se pudo crear FnExe[$fn]");
        }
    }
    
}
