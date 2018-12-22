<?php
namespace Access;

use \Core\ApiRet;
use \Error\ErrInfo;

class Lecture extends \First\First {

    private $model = null;

    public function __construct( ) {
        $this->model = new \Model\Lecture;
    }

    public function add($req, $res) {
        $uid = $this->user['id'];

        $lecture_name = post_data('lecture_name');
        $lecture_name = htmlentities(trim($lecture_name));
        if (empty($lecture_name)) {
            return ApiRet::send($res, ErrInfo::RetErr('ERR_BAD_DATA'));
        }

        $is_publish = post_data('is_publish');

        $tm = time();
        $data = [
            'user_id'       => $uid,
            'lecture_name'  => $lecture_name,
            'is_publish'    => (empty($is_publish) ? 0 : 1),
            'add_time'      => $tm,
            'update_time'   => $tm
        ];

        $r = $this->model->add($uid, $data);
        if (!$r) {
            return ApiRet::send($res, ErrInfo::DefErr( get_sys_error() ));
        }
        return ApiRet::send($res, [
            'status'    => 0,
            'id'        => $r
        ]);
    }

    public function remove($req, $res) {
        $id = post_data('lecutre_id');
        $r = (new \Model\Lecture)->remove($this->user['id'], $id);
        if (!$r) {
            return ApiRet::send($res, ErrInfo::DefErr( get_sys_error() ));
        }

        return ApiRet::send($res, [
            'status'    => 0,
            'info'      => 'ok'
        ]);

    }

    public function lectureListw($req, $res) {
        $cond = [
            'user_id'   => $this->user['id']
        ];

        $lectures = (new \Model\Lecture)->lectureList(1, $cond);
        return ApiRet::send($res, [
            'status'    => 0,
            'lectures'  => $lectures
        ]);
    }

    public function lectureList($req, $res) {
        $page = get_data('page');
        if (!is_numeric($page) || $page <= 0) {
            $page = 1;
        }

        $kwd = get_data('kwd');
        $user = get_data('user');
        $cond = [
            'AND'  => [
                'is_publish'    => 1,
                'id[>]'         => 0
            ]
        
        ];

        if (!empty($user)) {
            $users = (new \Model\Users)->getAll([
                'AND'   => [
                    'email_status'  => 1,
                    'user_role'     => [USER_CREATOR, USER_WRITER]
                ]
            ], ['id']);
            if (!empty($users)) {
                $idlist = [];
                foreach($users as $id) {
                    $idlist[] = $id;
                }
                $cond['AND']['user_id'] = $idlist;
            }

        }

        if (!empty($kwd)) {
            $cond['AND']['lecture_name[~]'] = $kwd;
        }

        $cond['LIMIT'] = [($page-1)*LEC_PAGESIZE, LEC_PAGESIZE];

        $m = new \Model\Lecture;

        $pgi = $m->pageInfo(['AND'  => $cond['AND']]);

        return ApiRet::send($res, [
            'status'    => 0,
            'total'     => $pgi['total'],
            'total_pagw'=> $pgi['total_page'],
            'lectures'  => $m->lectureList($page, $cond)
        ]);

    }


}

