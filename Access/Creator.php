<?php
namespace Access;

use \Model\Resource;
use \Model\Group;
use \Model\Users;
use \Error\ErrInfo;


class Creator extends Writer {


    public function authWriter($req, $res) {
        
        $uid = $this->user['id'];
        $user = new Users;

        $to = post_data('user_id');
        if (!is_numeric($to) || $to <= 0) {
            return ApiRet::send($res, ErrInfo::DefErr('Bad-data: illegal user id'));
        }

        $n = $user->stats(['auth_id' => $uid]);

        if ($n >= WR_AUTH_LIMIT) {
            return ApiRet::send($res, ErrInfo::DefErr('Out of limit: ' . WR_AUTH_LIMIT));
        }
        $cond = [
            'AND'   => [
                'is_delete' => 0,
                'user_role' => USER_READER,
                'email_status' => 1,
                'id'    => $to
            ]
        ];

        $data = [
            'auth_id'   => $uid,
            'user_role' => USER_WRITER,
            ''
        ];

        $r = $user->update($cond, $data);
        if (!$r) {
            return ApiRet::send($res, ErrInfo::DefErr( get_sys_error() ));
        }

        return ApiRet::send($res, [
            'status'    => 0,
            'info'      => 'ok'
        ]);
    }

    public function unAuthWriter($req, $res) {
        $to = post_data('user_id');
        if (!is_numeric($to) || $to <= 0) {
            return ApiRet::send($res, ErrInfo::DefErr('Bad-Data: illegal user id'));
        }

        $cond = [
            'AND'   => [
                'id'        => $to,
                'auth_id'   => $this->user['id'],
                'user_role' => USER_WRITER
            ]
        ];

        $r = (new Users)->update($cond, [
            'user_role' => USER_READER,
            'auth_id'   => 0
        ]);

        if (!$r) {
            return ApiRet::send($res, ErrInfo::DefErr( get_sys_error() ));
        }
        
        return ApiRet::send($res, [
            'status'    => 0,
            'info'      => 'ok'
        ]);

    }

    public function authList($req, $res) {
        return (new Users)->search([
            'email_status'  => 1,
            'is_delete' => 0,
            'auth_id' => $this->user['id']
        ]);
    }

}

