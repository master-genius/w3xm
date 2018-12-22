<?php
namespace Middleware;


use \Core\ApiRet;
use \Error\ErrInfo;
use \First\UserSession;
use \Auth\AuthRedis;

class RoleWare {

    public $roles = [];

    public $redirect_url = '';

    //roles是允许的用户
    public function __construct($options = []) {
        if (isset($options['roles'])) {
            $this->roles = $options['roles'];
        }

        if (isset($options['redirect'])) {
            $this->redirect_url = $options['redirect'];
        }
    }

    public function rolePass() {
        
        $user = UserSession::get();
        if (false === $user) {
            return false;
        }

        $pass = false;

        if (is_array($this->roles)) {
            $role = $user['user_role'];
            if (array_search(
                $role,
                $this->roles) !== false)
            {
                $pass = true;
            }
        } else if(is_string($this->roles)
            || is_numeric($this->roles) )
        {
            if ($this->roles == $user['user_role']) {
                $pass = true;
            }
        }

        return $pass;
    }

    public function roleRedirect($req, $res, $next) {

        if ($this->rolePass() === false) {
            return $res->withRedirect($this->redirect_url, 301);
        }

        $res = $next($req, $res);
        return $res;
    }


    public function __invoke($req, $res, $next) {

        if ($this->rolePass() === false) {
            return ApiRet::send(
                $res,
                ErrInfo::RetErr('ERR_PERM_DENY')
            );
        }

        $res = $next($req, $res);
        return $res;
    }

}

