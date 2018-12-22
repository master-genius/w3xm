<?php
require ('vendor/autoload.php');

use \Core\DB;
use \Core\VCode;
use \Core\ApiRet;
use \Middleware\AuthWare;
use \Middleware\RoleWare;


$co = new \Slim\Container;

$co['APIUser'] = function($co) {
    return (new \Access\User);
};

//404
$co['notFoundHandler'] = function() {
    return function($req, $res) use ($co) {
        return ApiRet::send($res, [
            'status'    => -1,
            'errinfo'   => '404 : Not found'
        ]);
    };
};

$app = new \Slim\App($co);

/*
    这部分接口只负责用户的登录，退出，重置密码等操作
*/

$app->group('/user', function() use ($app) {
    $app->post('/login', function($req, $res) {
        return $this->APIUser->login($req, $res);
    });

    $app->get('/email-verify', function($req, $res) {
        return (new \Access\User)->verifyEmail($req, $res);
    });

    $app->post('/reply-find-passwd', function($req, $res) {
        return (new \Access\User)->replyFindPasswd($req, $res);
    });

    $app->post('/reset/passwd', function($req, $res) {
        return (new \Access\User)->findPasswd($req, $res);
    });

    $app->post('/resend-verify-email', function($req, $res) {
    
    });

    $app->post('/register', function($req, $res) {
        return $this->APIUser->register($req, $res);
    });

});


$app->run();

