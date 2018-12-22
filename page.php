<?php
require ('vendor/autoload.php');

use \Core\DB;
use \Core\VCode;
use \Core\ApiRet;
use \Core\View;
use \Middleware\RoleWare;
use \Middleware\AuthWare;

$co = new \Slim\Container;

$co['View'] = function($co){
    return (new View);
};

$co['Config'] = function($co) {
    $cfg = include (CONFIG_PATH . '/config.php');
    return $cfg;
};

$app = new \Slim\App($co);

$app->group('/user', function() use ($app) {
    $app->get('/register', function($req, $res) {
        return (new \Access\User)->regPage($req, $res);
    });

    $app->get('/login', function($req, $res) {
        return ApiRet::raw($res, (new \Core\View)->page('user/login.html'));
    });

    $app->get('/forget/passwd', function($req, $res) {
        return (new \Access\User)->forgetPasswdPage($req, $res);
    });
})->add(function($req, $res, $next) {
    
    $res = $next($req, $res);
    return $res;
});


$app->group('/v', function() use ($app) {
    
    $app->get('/email-verify', function($req, $res) {
        return (new \Access\User)->verifyEmail($req, $res);
    });

    $app->post('/reply-find-passwd', function($req, $res) {
        return (new \Access\User)->replyFindPasswd($req, $res);
    });

    $app->get('/findpasswd', function($req, $res) {
        return (new \Access\User)->findPasswdPage($req, $res);
    });



});

/*
 *
 * 接口路由分组：/u  /w  /c /r
 *
 * 使用分组作为权限控制，/u是所有用户都允许的操作
 * /r 是Reader用户的权限
 * /w 是Writer用户的权限
 * /c 是Creator用户的权限
 * /c用户具备 /w  /r 用户的权限
 * /w 具备/r用户的权限
 * 
 * */

$app->group('', function() use ($app) {

    $app->get('[/]', function($req, $res) {
        $page = $this->View->page('first/index.html', $this->Config);
        return ApiRet::raw($res, $page);
    });


});


$app->group('/r', function() use ($app) {
    $app->get('/userinfo', function($req, $res) {
    
    });

    $app->get('', function($req, $res) {
    
    });

});

$app->group('/w', function() use ($app) {
    $app->get('/rs/add', function($req, $res) {
        return ApiRet::raw($res, $this->View->page('cwr/rsadd.html'));
    });

    $app->get('', function($req, $res) {
    
    });

});

    /*
    ->add(function($req, $res, $next) {
    $ro = new RoleWare([
        'roles' => [USER_CREATOR, USER_WRITER],
        'redirect'=> '/r/userinfo'
    ]);
    return $res;
    return $ro->roleRedirect($req, $res, $next);
})->add(function($req, $res, $next) {
    $a = new AuthWare([
        'redirect' => '/user/login'
    ]);
    
    return $a->authRedirect($req, $res, $next);
});
     */

$app->run();

