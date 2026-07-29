<?php
// Por ahora estas son las únicas clases guardadas en la session
//require_once '/home/qjvixmoy/lasolucion/lib/ccargo/entities/Usuario.php';

//require_once '/home/qjvixmoy/lasolucion/lib/common/config.php';

/*
 * OJO esto debe redirigir al loggeo generico, por ahora es solo loan
 */
error_reporting(E_ALL);
session_start();
setlocale(LC_TIME, 'es_CO');

if (!isset($_SESSION['user'])) {
    // OJO Solucionar esto, sólo a loan por ahora
    //session_unset();
    header('Location: /ls/logout.php');
}

