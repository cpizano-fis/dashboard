<?php
require_once '/home/qjvixmoy/lasolucion/lib/entities/AbstractEntity.php';
require_once '/home/qjvixmoy/lasolucion/lib/entities/Usuario.php';
require_once '/home/qjvixmoy/lasolucion/lib/entities/Pais.php';
//require_once '/home/qjvixmoy/lasolucion/lib/entities/Divpol2.php';

class Acreedor extends AbstractEntity {
       
    public function __construct($param) {
        $this->fields = "id,usuario_id,pais_id,fecha_inicio,fecha_fin,direccion,usuarioc_id,fecha,divpol2_id";
        $this->addFk("Usuario", "usuario_id");
        // Relación doble a Usuario, requiere índice separado 'UsuarioC'
        $this->addFk("Usuario", "usuarioc_id", FALSE, "UsuarioC");
        $this->addFk("Pais", "pais_id");
        // Definir totalmente Divpol2
        //$this->addFk("Divpol2", "divpol2_id");
        parent::__construct($param);
    }
    
    public function getDireccion() {
        return $this->record["direccion"];
    }

    public function setDireccion($direccion) {
        $this->record["direccion"] = $direccion;
    }

    public function getFecha() {
        return $this->record["fecha"];
    }

    public function setFecha($fecha) {
        $this->record["fecha"] = $fecha;
    }

    public function getFechaInicio() {
        return $this->record["fecha_inicio"];
    }

    public function setFechaInicio($fecha_inicio) {
        $this->record["fecha_inicio"] = $fecha_inicio;
    }

    public function getFechaFin() {
        return $this->record["fecha_fin"];
    }

    public function setFechaFin($fecha_fin) {
        $this->record["fecha_fin"] = $fecha_fin;
    }

    public function getPaisId() {
        return $this->record["pais_id"];
    }

    public function setPaisId($pais_id) {
        $this->record["pais_id"] = $pais_id;
    }
    
    public function getUsuarioId() {
        return $this->record["usuario_id"];
    }

    public function setUsuarioId($usuario_id) {
        $this->record["usuario_id"] = $usuario_id;
    }
    
    public function getUsuarioCreaId() {
        return $this->record["usuarioc_id"];
    }

    public function setUsuarioCreaId($usuarioc_id) {
        $this->record["usuarioc_id"] = $usuarioc_id;
    }
    
    public function getDivpol2Id() {
        return $this->record["divpol2_id"];
    }

    public function setDivPol2Id($divpol2_id) {
        $this->record["divpol2_id"] = $divpol2_id;
    }
    
    /* Foreign key*/
    
    private function getFK() {
        $this->Usuario = new User($this->record["usuario_id"]);
        $this->UsuarioCrea = new User($this->record["usuarioc_id"]);
        $this->Pais = new Base($this->record["pais_id"]);
        $this->Divpol2 = new Divpol2($this->record["divpol2_id"]);
    }
    
    public function getUsuario() {
        return $this->getFk("Usuario");
    }
    
    public function getUsuarioCrea() {
        return $this->getFk("UsuarioC");
    }
    
    public function getPais() {
        return $this->getFk("Pais");
    }
    /*
    public function getDivpol2() {
        return $this->getFk("Divpol2");
    }*/
 
}
