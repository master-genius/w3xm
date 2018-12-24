<?php
require ('vendor/autoload.php');

use \Core\DB;
use \Core\VCode;
use \Core\ApiRet;
use \Middleware\AuthWare;
use \Middleware\RoleWare;
use \Middleware\MediaWare;


$co = new \Slim\Container;

$co['APIU'] = function($co) {
    return (new \First\First);
};

$co['APIUser'] = function($co) {
    return (new \Access\User);
};

$co['APIReader'] = function($co) {
    return (new \Access\Reader);
};

$co['APIWriter'] = function($co) {
    return (new \Access\Writer);
};

$co['APICreator'] = function($co) {
    return (new \Access\Creator);
};

$co['APIMedia'] = function($co) {
    return (new \Access\Media);
};

$co['APILecture'] = function($co) {
    return (new \Access\Lecture);
};

//404
$co['notFoundHandler'] = function() {
    return function($req, $res) use ($co) {
        return ApiRet::send($res, [
            'status'    => 404,
            'errinfo'   => '404 : Not found'
        ]);
    };
};

$app = new \Slim\App($co);

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

$app->group('/u', function() use ($app) {

    $app->get('/rs/group/list', function($req, $res) {
        return $this->APIU->groupList($req, $res);
    });

    $app->get('/rs/list', function($req, $res) {
        return (new \First\First)->rsList2($req, $res);
    });
    
    $app->get('/rs/page', function($req, $res) {
        return (new \First\First)->rsPageInfo($req, $res);
    });

    $app->get('/rs/get/{id}', function($req, $res, $args) {
        return $this->APIU->get($req, $res, $args['id']);
    });

    $app->get('/host', function($req, $res) {
        return ApiRet::send($res, $_SERVER['SERVER_NAME']);
    });

    $app->get('/lecture/list', function($req, $res) {
        return $this->APILecture->lectureList($req, $res);
    });

});



$app->group('/r', function() use ($app) {

    $app->get('/logout', function($req, $res) {
        return ApiRet::send($res, $this->APIUser->logout($req, $res));
    });

    $app->get('/userinfo', function($req, $res) {
        return $this->APIReader->myInfo($req, $res);
    });

    $app->post('/set/nickname', function($req, $res) {
        return $this->APIReader->setNickname($req, $res);
    });


})->add(new AuthWare);


/*
 * 上传素材，编辑/发布内容
 * 删除自己的素材和内容
 * CREATOR和WRITER用户具备此权限
 *
 * */
$app->group('/w', function() use ($app) {

    $app->get('/rs/get/{id}', function($req, $res, $args) {
        return $this->APIU->wget($req, $res, $args['id']);
    });

    $app->post('/rs/add', function($req, $res) {
        return $this->APIWriter->add($req, $res);    
    });
    
    $app->post('/rs/addpub', function($req, $res) {
        return $this->APIWriter->addPublish($req, $res);
    });

    $app->post('/rs/update', function($req, $res) {
        return $this->APIWriter->update($req, $res);
    });

    $app->post('/rs/publish', function($req, $res) {
    
    });

    $app->post('/rs/delete', function($req, $res) {
        return $this->APIWriter->remove($req, $res);
    });

    $app->post('/rs/deletelist', function($req, $res) {
        return $this->APIWriter->removeBatch($req, $res);
    });

    $app->get('/rs/list', function($req, $res) {
        return $this->APIWriter->wRsList($req, $res);
    });

    $app->get('/role/menu', function($req, $res) {
        return $this->APIU->getUserMenu($req, $res);
    });

    $app->post('/lecture/add', function($req, $res) {
        return $this->APILecture->add($req, $res);
    });

    $app->post('/lecture/delete', function($req, $res) {
        return $this->APILecture->remove($req, $res);
    });

    $app->post('/lecture/update', function($req, $res) {
        return $this->APILecture->update($req, $res);
    });

    $app->get('/lecture/list', function($req, $res){
        return $this->APILecture->lectureListw($req, $res);
    });

})->add(
    new RoleWare([
        'roles'   => [USER_CREATOR, USER_WRITER]
    ])
)->add(
    new AuthWare
);

/*
 * 
 * 设置Writer用户，CREATOR用户具备此权限
 *
 *
 * */
$app->group('/c', function() use ($app) {

    $app->post('/wr/set', function($req, $res) {
    
    });

    $app->post('/wr/unset', function($req, $res) {
    
    });

    $app->get('/wr/list', function($req, $res) {
    
    });


})->add(
    new RoleWare(USER_CREATOR)
)->add(
    new AuthWare
);


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

