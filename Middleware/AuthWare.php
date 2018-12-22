<?php
namespace Middleware;

use \Core\ApiRet;
use \Error\ErrInfo;
use \Auth\AuthRedis;
use \First\UserSession;

class AuthWare {

    public $callback = null;

    public $redirect_url = '';

    public function __construct($options = []) {
        if (isset($options['redirect'])) {
            $this->redirect_url = $options['redirect'];
        }
    }

    public function authRedirect($req, $res, $next) {
        $pass = true;
        $user = (new AuthRedis)->user();
        if ($user === false) {
            $pass = false;
        } else {
            UserSession::set($user);
        }

        if ($pass === false) {
            return $res->withRedirect($this->redirect_url, 301);
        }
        
        $res = $next($req, $res);
        return $res;
    }
    
    public function __invoke($req, $res, $next) {
        $user = (new AuthRedis)->user();
        if ($user === false) {
            return ApiRet::send(
                $res,
                ErrInfo::RetErr('ERR_NOT_LOGIN')
            );
        }

        UserSession::set($user);
        $res = $next($req, $res);
        return $res;
    }

}

