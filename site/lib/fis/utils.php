<?php

require_once '/home/qjvixmoy/lasolucion/lib/config.php';
require_once '/home/qjvixmoy/lasolucion/lib/vendor/adodb/adodb-php/adodb.inc.php';
require_once '/home/qjvixmoy/lasolucion/lib/vendor/smarty/smarty/libs/Smarty.class.php';

class Utils {
    /**
    * Crea y devuelve una instancia de Smarty especialmente configurada
    * para el sitio, utilizando las variables de config.php.
    *
    * getSmarty() crea el objeto del sistema de plantillas (Smarty) con las
    * siguientes variables ya asignadas:
    * - URL 
    * - LIB_DIR
    * - TPL_DIR
    *
    * @see config.php
    * @return Smarty instancia de la clase de plantillas Smarty configurada para el sistema
    */
    public static function getSmarty() {
		global $CFG_URL;
		global $CFG_LIB_DIR;
		global $CFG_TPL_DIR;
		global $JQUERY;
        
        $smarty = new Smarty();
        $smarty->debugging = false;
        $smarty->setTemplateDir($CFG_TPL_DIR);
        $smarty->setCompileDir($smarty->getTemplateDir(0) . '/_compiled.tpl/');
        $smarty->setConfigDir($smarty->getTemplateDir(0) . '/smarty/configs/');
        $smarty->setCacheDir($smarty->getTemplateDir(0) . '/smarty/cache/');
        $smarty->addPluginsDir($smarty->getTemplateDir(0) . '/smarty/plugins/');
        // Set global vars.
        $smarty->assign('URL', $CFG_URL);
        $smarty->assign('TPL', $CFG_TPL_DIR);
        $smarty->assign('JQUERY', $JQUERY);
        $smarty->setCaching(0);
        // Esto no es necesario ??
        //$_SESSION['smarty'] = $smarty;
        return $smarty;
    }
    
    public static function getConfig($full = FALSE) {
        if (!isset($GLOBALS["config"])) {
            $GLOBALS["config"] = Db::GetAssoc("SELECT item, valor " . ($full ? ", description, id" : "") . " FROM configuracion;");
        }
        return $GLOBALS["config"];
    }

    public static function getManager($name, $params = NULL) {
        if (!isset($GLOBALS["managers"])) {
            $GLOBALS["managers"] = array();
        }
        if (!isset($GLOBALS["managers"][$name])) {
            $GLOBALS["managers"][$name] = new $name($params);
        }
        //error_log(print_r($GLOBALS["managers"], TRUE), 0);
        return $GLOBALS["managers"][$name];
    }

    /**
     * Generar un nuevo password aleatorio
     * @param Persona $persona
     */
    public static function getNewPw($persona) {
        $ahora = date("D M d, Y G:i");
        //error_log($ahora . $persona->getDocumento() . $persona->getNombre(), 0);
        $md5sum = md5($ahora . $persona->getDocumento() . $persona->getNombre());
        // Evitamos ceros en la contraseña
        return strtoupper(substr(str_replace("0", "", $md5sum), 0, 6));
    }

    /**
    * Verifica que ninguna de las variables que recibe como argumento sea vacia,
    * utilizando el mismo metodo de {@link nempty}.
    * @return bool FALSE si alguno de los argumentos es vacio; TRUE en caso contrario.
    * @see nempty()
    */
    public static function noEmptyV() {
        $numargs = func_num_args();
        if ($numargs == 0) {
            return false;
        } else {
            foreach (func_get_args() as $arg) {
                if (!Utils::noEmpty($arg)) {
                    return false;
                }
            }
        return true;
        }
    }

    /**
    * Verifica que la variable <var>$var</var> no sea vacia.
    * @param mixed $var variable a probar
    * @return bool TRUE si la variable es no vacia; FALSE en caso contrario
    */
    public static function noEmpty($var) {
        if (isset($var)) {
            if (empty($var) && ($var !== 0) && ($var !== "0")) {
                return false;
            }
            if (is_string($var)) {
                $trimStr = trim($var);
                if ($trimStr === "") {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    public static function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') == $date;
    }
    
    public static function logout() {
        session_start();
        $_SESSION = array();
        session_unset();
        session_destroy();
    }
    
}
