<?php
namespace Access;

use \Error\ErrInfo;
use \Model\Users;
use \Task\Cli;
use \Core\ApiRet;
use \Core\View;
use \Auth\AuthRedis;
use \Core\SSL;


class User {

    private $failed_login_limit = 5;

    private $failed_timeout    = 1800;


    public function logout($req, $res) {
        $api_token = post_data('api_token');

        (new AuthSession)->logout();

        return ApiRet::send($res, [
            'status'    => 0,
            'info'      => 'ok'
        ]);
    }

    public function regPage($req, $res) {
        return ApiRet::raw($res, (new View)->page('user/register.html'));
    }

    public function login($req, $res) {

        $username = post_data('username');
        $passwd   = post_data('passwd');

        if (empty($username) || empty($passwd)) {
            return ApiRet::send($res, ErrInfo::RetErr('ERR_BAD_DATA'));
        }

        $user = new Users;

        $field_name = (strpos($username, '@')===false) ? 'username' : 'email';

        $cond = [
            'AND'   => [
                $field_name => $username,
                'is_delete' => 0,
                'is_forbid' => 0
            ]
        ];

        $u = $user->get($cond, [
            'id', 'username', 'passwd', 'salt', 'email', 'user_role',
            'failed_login', 'failed_login_time', 'nickname'
        ]);
        if (empty($u)) {
            return ApiRet::send($res, ErrInfo::DefErr('用户名或密码错误'));
        }

        if ($u['failed_login'] >= $this->failed_login_limit 
            && $u['failed_login_time'] > (time() - $this->failed_timeout) )
        {
            return ApiRet::send($res, ErrInfo::DefErr('此用户登录失败次数过多，请30分钟后再试。'));
        }

        $c = [
            'id'    => $u['id']
        ];

        if (SSL::hashPasswd($passwd, $u['salt']) !== $u['passwd']) {
            $user->update($c, [
                'failed_login[+]'   => 1,
                'failed_login_time' => time()
            ]);

            return ApiRet::send($res, ErrInfo::DefErr('用户名或密码错误'));
        }

        $user->update($u, [
            'failed_login'      => 0,
            'failed_login_time' => 0
        ]);

        unset($u['failed_login']);
        unset($u['failed_login_time']);
        unset($u['passwd']);

        $token = (new AuthRedis)->login($u); 
        
        return ApiRet::send($res, [
            'status'    => 0,
            'token'     => $token
        ]);

    }

    public function register($req, $res) {

        $filter = [
            'email',
            'username',
            'passwd'
        ];

        $data = auto_post_data($filter, true);

        if (!isset($data['email']) 
            || !isset($data['username'])
            || !isset($data['passwd']) 
        )
        {
            return api_ret($res, ErrInfo::DefErr('Illegal data'));
        }

        $user = new Users;

        $r = $user->add($data);
        
        if (!$r) { 
            $uchk = $user->get([
                'email'     => $data['email'],
                'email_status' => 0
            ], ['id', 'email', 'salt', 'email_verify_str']);

            if ( $uchk !== false) {
                $r = [
                    'randstr'   => $user->genRandStr(),
                    'email_verify_str'  => $uchk['email_verify_str']
                ];

                $user->update([
                    'id' => $uchk['id']
                ], [
                    'randstr'   => $r['randstr']
                ]);

                goto just_send_email;
            }

            return api_ret($res, ErrInfo::RetErr('ERR_BAD_DATA'));
        }

        just_send_email:;
        //注册成功，发送验证邮件
        //验证邮件的发送仅仅是传递到消息队列，由负责此任务的进程处理
        (new Cli)->sendVerifyEmail([
            'email'     => $data['email'],
            'randstr'   => $r['randstr'],
            'email_verify_str' => $r['email_verify_str']
        ]);

        return api_ret($res, [
            'status'    => 0,
            'info'   => '验证邮件已发送，5分钟内未收到可重新申请'
        ]);
        
    }

    public function verifyEmail($req, $res) {
        $randstr = get_data('randstr');
        $vstr = get_data('vstr');

        if ((new Users)->verifyEmail($randstr, $vstr) === false) {
            return ApiRet::send($res, ErrInfo::DefErr( '邮件验证失败，超时未验证或已验证' ) );
        }

        return ApiRet::send($res, [
            'status'    => 0,
            'info'      => 'ok'
        ]);

    }

    public function clearEmail($req, $res) {
    
    }

    public function findPasswd($req, $res) {

        $randstr = post_data('randstr');
        $vstr = post_data('vstr');
        $passwd = post_data('passwd');
        $username = post_data('username');

        $r = (new Users)->findPasswd($username, $randstr, $vstr, $passwd);
        if (!$r) {
            return ApiRet::send($res, ErrInfo::DefErr( get_sys_error() ));
        }

        return ApiRet::send($res, [
            'status' => 0,
            'info'   => 'ok'
        ]);

    }

    public function findPasswdPage($req, $res) {
        $randstr = get_data('randstr');
        $vstr = get_data('vstr');

        if (false === (new Users)->checkFindPasswd($randstr, $vstr)) {
            return ApiRet::raw($res, '此链接已失效');
        }

        return ApiRet::raw($res, 
                    (new View)->page('user/findpasswd.html', [
                        'randstr'   => $randstr,
                        'vstr'      => $vstr
                    ])
                );
    }

    public function forgetPasswdPage($req, $res) {
        return ApiRet::raw($res, (new View)->page('user/forgetpasswd.html'));
    }


    public function replyFindPasswd($req, $res) {

        $email = post_data('email');

        $user = new Users;

        $u = $user->get([
            'email'     => $email,
            'email_status' => 1
        ], ['id', 'email', 'salt']);

        if (empty($u)) {
            return ApiRet::send($res, ErrInfo::RetErr('ERR_BAD_DATA'));
        }

        $data = [
            'randstr'       => $user->genRandStr($u['email']),
            'email_verify_str'  => $user->genVerifyStr($u['email'], $u['salt']),
            'email_verify_time' => time()
        ];

        $r = $user->update($u['id'], $data);

        if (!$r) {
            return ApiRet::send($res, ErrInfo::DefErr(get_sys_error()));
        }

        (new Cli)->sendFindPasswdEmail([
            'email'     => $u['email'],
            'randstr'   => $data['randstr'],
            'email_verify_str' => $data['email_verify_str']
        ]);

        return ApiRet::send($res, [
            'status'    => 0,
            'info'      => '请查收验证邮件，如未收到可重新申请'
        ]);

    }

    public function replyClearEmail($req, $res) {

        $email = post_data('email');

        $user = new Users;

        $u = $user->get([
            'email'     => $email,
            'email_status' => 1
        ], ['id', 'email', 'salt']);

        if (empty($u)) {
            return ApiRet::send($res, ErrInfo::RetErr('ERR_BAD_DATA'));
        }
        
        $data = [
            'clear_email_randstr'  => $user->genVerifyStr($u['email'], $u['salt']),
            'buff_time' => time()
        ];

        $r = $user->update($u['id'], $data);

        if (!$r) {
            return ApiRet::send($res, ErrInfo::DefErr(get_sys_error()));
        }

        (new Cli)->sendClearEmail([
            'email'     => $u['email'],
            'clear_email_randstr' => $data['clear_email_randstr']
        ]);

        return ApiRet::send($res, [
            'status'    => 0,
            'info'      => '请查收验证邮件，如未收到可重新申请'
        ]);

    }


}

