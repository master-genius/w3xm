<?php
namespace Model;

use \Core\DB;
use \Core\SSL;

class Users {

    private $table = 'users';

    private $timeout = 3600;

    private $reset_timeout  = 1800;

    private $fields = [
        'id',
        'username',
        'email',
        'mobile',
        'nickname',
        'user_role',
    ];


    //username or email
    public function has($field = 'username', $val) {
        if ($field !== 'username' && $field !=='email') {
            $field = 'username';
        }

        $n = DB::instance()->count($this->table, [
            'AND'   => [
                $field  => $val,
                'is_delete' => 0
            ]
        ]);

        return $n;
    }

    public function stats($cond) {
        return DB::instance()->count($this->table, $cond);
    }

    public function add($u) {
        
        $salt = SSL::createSalt();
        $hash_passwd = SSL::hashPasswd($u['passwd'], $salt);
        
        //$hash_passwd = password_hash($u['passwd'], PASSWORD_DEFAULT);
        
        $u['passwd'] = $hash_passwd;
        $u['salt'] = $salt;
        $u['reg_time'] = time();
        $u['email_status'] = 0;

        $u['email_verify_str'] = $this->genVerifyStr($u['email'], $salt);
        $u['randstr'] = $this->genRandStr($u['email']);
        

        $r = DB::instance()->insert($this->table, $u);
        if ($r->rowCount() <= 0) {
            set_sys_error($r->errorInfo()[2]);
            return false;
        }
        return [
            'id'      => DB::instance()->id(),
            'randstr' => $u['randstr'],

            'email_verify_str'  => $u['email_verify_str']
        ];
    }


    public function get($cond, $fields = []) {
        if (empty($fields)) {
            $fields = $this->fields;
        }

        $u = DB::instance()->get($this->table, $fields, $cond);
        if (empty($u)) {

            return false;
        }
        return $u;
    }

    public function getAll($cond, $fields = []) {
        if (empty($fields)) {
            $fields = $this->fields;
        }
        return DB::instance()->select($this->table, $fields, $cond);
    }

    public function update($cond, $data) {
        $r = DB::instance()->update($this->table, $data, $cond);

        if ($r->rowCount() <= 0) {
            set_sys_error($r->errorInfo()[2]);
            return false;
        }
        return $r->rowCount();
    }

    public function search($cond, $page = 1) {
        $total = DB::instance()->count($this->table, $cond);
        $div = $total/USER_PAGESIZE;
        $total_page = ($total%USER_PAGESIZE) ? ((int)($div) + 1) : (int)$div;
        
        $cond['LIMIT'] = [($page - 1)*USER_PAGESIZE, USER_PAGESIZE];

        $fields = [
            'id',
            'username',
            'email',
            'user_role',
            'nickname',
            'auth_id'
        ];

        $ul = DB::instance()->select($this->table, $fields, $cond);

        return [
            'total'     => $total,
            'total_page'=> $total_page,
            'user_list' => $ul,
            'cur_page'  => $page
        ];
    }

    public function genVerifyStr($key = '', $salt = '') {
        return hash('tiger192,3', $key . $salt);
    }

    public function genRandStr($str = '') {
        return md5(time() . mt_rand(1000, 9999) . $str);
    }


    public function verifyEmail($randstr, $vstr) {
        $cond = [
            'AND'   => [
                'email_status'      => 0,
                'randstr'           => $randstr,
                'email_verify_str'  => $vstr,
                'reg_time[>]'       => time() - $this->timeout
            ]
        ];

        $r = DB::instance()->update($this->table, [
            'email_status'      => 1,
            'email_verify_time' => time()
        ], $cond);

        if ($r->rowCount() <= 0) {
            set_sys_error($r->errorInfo()[2]);
            return false;
        }

        return true;

    }


    /*
     * 此函数主要用于以下情形：如果一个用户因为某种原因注册了邮箱但没有验证，
     * 而且可能是用其他人的邮箱注册，此时邮箱真正的用户则无法注册。
     * 这时候邮箱用户可以申请重置未验证邮箱，这个操作会把没有验证的邮箱
     * 字段清空。
     * */
    public function clearNotVerifyEmail($email, $randstr) {

        $cond = [
            'AND'   => [
                'email'     => $email,
                'email_status'  => 0,
                'reg_time[>]'   => time() - $this->reset_timeout,
                'clear_email_randstr'   => $randstr
            ]
        ];

        $r = DB::instance()->update($this->table, ['email'  => null], $cond);
        if ($r->rowCount() <= 0) {
            set_sys_error($r->errorInfo()[2]);
            return false;
        }

        return true;

    }

    public function resetPasswd($id, $old_passwd, $passwd) {
    
    }

    /*
     * 找回密码操作会使用randstr和email_verify_str字段作为授权检测，
     * 这需要一个页面链接配合，用户通过邮箱打开链接，此时页面会请求
     * 是否合法，并展示重置密码页面，在重置密码时还会进行验证，这保证了
     * 如果用户直接通过接口绕过页面请求不会导致验证漏洞。
     * */
    public function findPasswd($username, $randstr, $vstr, $passwd) {

        $salt = SSL::createSalt();

        $cond = [
            'AND'    => [
                'username'  => $username,
                'randstr'   => $randstr,
                'email_verify_str' => $vstr,
                'email_verify_time[>]'      => time() - $this->reset_timeout
            ]
        ];

        $data = [
            'salt'      => $salt,
            'passwd'    => SSL::hashPasswd($passwd, $salt),
            'randstr'   => '',
        ];

        $r = DB::instance()->update($this->table, $data, $cond);
        if ($r->rowCount() <= 0) {
            set_sys_error($r->errorInfo()[2]);
            return false;
        }

        return true;
    }

    public function checkFindPasswd($randstr, $vstr) {
        $cond = [
            'AND'   => [
                'randstr'           => $randstr,
                'email_verify_str'  => $vstr,
                'email_verify_time[>]'      => time() - $this->reset_timeout
            ]
        ];

        $r = DB::instance()->get($this->table, ['id','email','salt'], $cond);
        if (empty($r)) {
            return false;
        }

        return $r;
    }

}

