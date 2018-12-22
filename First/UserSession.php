<?php
namespace First;

class UserSession {

    static public function set($u) {
        $GLOBALS['USER'] = $u;
    }

    static public function get() {
        if (!isset($GLOBALS['USER'])) {
            return false;
        }
        return $GLOBALS['USER'];
    }

    static public function isLogin() {
        if (!isset($GLOBALS['USER'])
            || $GLOBALS['USER'] === false )
        {
            return false;
        }

        return true;
    }

}

