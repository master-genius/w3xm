<?php
require ('vendor/autoload.php');

use \Core\DB;
use \Core\VCode;
use \Core\ApiRet;
use \Middleware\AuthWare;
use \Middleware\RoleWare;
use \Middleware\MediaWare;


$co = new \Slim\Container;

$co['APIWriter'] = function($co) {
    return (new \Access\Writer);
};

$co['APICreator'] = function($co) {
    return (new \Access\Creator);
};

$co['APIMedia'] = function($co) {
    return (new \Access\Media);
};

$app = new \Slim\App($co);



/*
 * 上传素材，编辑/发布内容
 * 删除自己的素材和内容
 * CREATOR和WRITER用户具备此权限
 *
 * */
$app->group('/mu', function() use ($app) {
    
    $app->post('/media/upload', function($req, $res) {
        return  $this->APIMedia->upload($req, $res);
    });

})->add(
    new MediaWare([
        'upload_name'   => 'image',
    ])
)->add(
    new RoleWare([
        'roles' => [USER_CREATOR, USER_WRITER]
    ])
)->add(
    new AuthWare
);


$app->group('/m', function() use ($app) {

    $app->post('/media/delete', function($req, $res) {
        return $this->APIMedia->remove($req, $res); 
    });

    $app->get('/media/list', function($req, $res) {
        return $this->APIMedia->mediaList($req, $res);
    });

    $app->post('/media/settag', function($req, $res) {
    
    });

})->add(
    new RoleWare([
        'roles' => [USER_CREATOR, USER_WRITER]
    ])
)->add(
    new AuthWare
);

$app->run();

