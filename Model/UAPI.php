<?php
namespace Model;

use \Core\DB;

class UAPI {

    public $table = 'user_api_log';


    public function add($api_name, $user_id, $flag = false) {
        $tm = time();
        $data = [
            'user_id'   => $user_id,
            'api_name'  => $api_name,
            'start_time'=> $tm,
            'last_time' => $tm,
            'call_count'=> 1
        ];

        $r = DB::instance()->insert($this->table, $data);

        if ($r->rowCount() <= 0) {
            set_sys_error($r->errorInfo()[2]);
            return false;
        }
        if ($flag) {
            $data['id'] = DB::instance()->id();
            return $data;   
        }

        return DB::instance()->id();
    }


    public function clearCall($api_name, $user_id) {
        $tm = time();
        $r = DB::instance()->update($this->table, [
            'start_time'    => $tm,
            'last_time'     => $tm,
            'call_count'    => 1
        ], [
            'user_id'   => $user_id,
            'api_name'  => $api_name
        ]);

        if (!$r->rowCount() <= 0) {
            set_sys_error($r->errorInfo()[2]);
            return false;
        }

        return true;
    }

    public function addCall($api_name, $user_id) {
        $r = DB::instance()->update($this->table, [
            'call_count[+]' => 1,
            'last_time'     => time()
        ], [
            'user_id'   => $user_id,
            'api_name'  => $api_name
        ]);

        if ($r->rowCount() <= 0) {
            set_sys_error($r->errorInfo()[2]);
            return false;
        }

        return true;
    }


    public function get($api_name, $user_id) {
    
        $r = DB::instance()->get($this->table, [
                'id',
                'api_name', 
                'user_id', 
                'start_time',
                'last_time', 
                'call_count'
            ], [
                'AND'   => [
                    'user_id'   => $user_id,
                    'api_name'  => $api_name
                ]
            ]);

        if (empty($r)) {
            return $this->add($api_name, $user_id, true);
        }
        return $r;
    }

}

