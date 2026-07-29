<?php
/*
 * Configuración global
 */
// Habilitar debugging (true|false) debe ser false en el servidor
$CFG_DEBUG = true;
// Host donde se encuentra la base de datos
$CFG_DB_HOST = "localhost";
// Tipo de base de datos (para uso con adodb)
$CFG_DB_TYPE = "mysqli";
// Nombre de las base de datos del sistema
$CFG_DB = "qjvixmoy_lasolucion";
// Nombre de usuario con los privilegios sobre las tablas de la base de datos.
$CFG_DB_LOGIN = "qjvixmoy_dbUser";
// Contraseña del usuario anterior.
$CFG_DB_PASSWORD = "PwDbLoan!)";
// Include de clases.
$CFG_LIB_DIR = "/home/qjvixmoy/lasolucion/lib";
// Include de templates.
$CFG_TPL_DIR = "/home/qjvixmoy/lasolucion/templates";
// Librería de jquery
$JQUERY = "http://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js";
// URL de ccargo (js, css, images)
if (file_exists("/home/cesar")) { // Entorno desarrollo
	$CFG_URL = "http://localhost/ls";
} else {
//    $host = parse_url($url, PHP_URL_HOST);
    $CFG_URL = "http://www.lasolucion.co.uk";
}


