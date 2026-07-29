<?php

//require_once '/home/publibus/app/lib/config.php';
//require_once '/home/publibus/app/lib/smarty/libs/Smarty.class.php';

class JUtils {
    
    const LOGOUT_URL = "/apps/common/logout.php";


    public static function msgBox($text, $title, $ret = FALSE, $fn = NULL) {
        $msgbox = array("title" => $title, "text" => $text, "ret" => $ret);
        if (!is_null($fn)) {
            $msgbox["fn"] = $fn;
        }
        return array("msgbox" => $msgbox);
    }
    
    public static function fn($name, $params = NULL, $ret = FALSE, $wait = TRUE) {
        $fn = array("name" => $name, "ret" => $ret, "wait" => $wait);
        if (!is_null($params)) {
            $fn["params"] = $params;
        }
        return $fn;
        //return array("fn" => $fn);
    }
    
    public static function uiError($msg, $title = "Error") {
        return self::uiMsg($msg, $title, "danger", FALSE);
    }
    
    public static function uiMsg($msg, $title = "Atención", $class = "success", $ret = TRUE) {
        return array("uimsg" => array("ret" => $ret, "title" => $title, "class" => $class, "msg" => $msg));
    }
    
    public static function logout() {
        return self::fn("goUrl", array(LOGOUT_URL));
    }
            
}
