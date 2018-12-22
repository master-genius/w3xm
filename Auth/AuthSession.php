<?php
namespace Auth;

use \Core\SSL;

class AuthSession implements \Interfaces\AuthInterface {

    public function login($u) {
        $t = SSL::makeToken($u);

        $_SESSION['user'] = [
            'info'        => $u,
            'login_time'  => time(),
            'token'       => $t['token'],
            'key'         => $t['key']
        ];

        return $t['token'];
    }

    public function logout($id = '') {
        unset($_SESSION['user']);
        session_destroy();
        return true;
    }

    public function get($id = '') {
        if(isset($_SESSION['user'])) {
            return $_SESSION['user'];
        }

        return false;
    }

    static public function user() {
        if (isset($_SESSION['user'])) {
            return $_SESSION['user'];
        }
        return false;
    }

}

