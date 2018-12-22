<?php
namespace Access;

use \Model\Resource;
use \Error\ErrInfo;
use \Model\Users;
use \Core\SSL;
use \Task\Cli;
use \Core\ApiRet;

class Reader extends \First\First {

    //收藏资源
    public function store($req, $res) {
        
    }
    

    public function star() {
    
    }

    public function unstar() {
    
    }

    public function myInfo($req, $res) {
        $id = $this->user['id'];
        $cond = [
            'id'    => $id
        ];

        $uinfo = (new Users)->get($cond);

        if (empty($uinfo)) {
            return ApiRet::send($res, ErrInfo::DefErr('can not get userinfo'));
        }

        return ApiRet::send($res, [
            'status'    => 0,
            'userinfo' => $uinfo
        ]);
    }

    public function setNickname($req, $res) {
        $id = $this->user['id'];

        $cond = [
            'id'    => $id
        ];

        $nickname = post_data('nickname');
        $nickname = htmlentities($nickname);
        $nickname = substr($nickname, 0, 32);
        $r = (new \Model\Users)->update($cond, ['nickname' => $nickname]);
        if (!$r) {
            return ApiRet::send($res, ErrInfo::DefErr( get_sys_error() ));
        }
        return ApiRet::send($res, [
            'status'    => 0,
            'info'      => 'ok'
        ]);
    }

}

