<?php
namespace Model;

use \Core\DB;
use \Model\Resource;


class Lecture {

    private $table = 'lectures';

    public $pagesize = 18;

    public function add($user_id, $data) {

        $total = DB::instance()->count($this->table, [
            'user_id'   => $user_id
        ]);
        if ($total >= WR_LECTURE_LIMIT) {
            set_sys_error('超出最大数量限制');
            return false;
        }

        $r = DB::instance()->insert($this->table, $data);
        if ($r->rowCount() <= 0) {
            return false;
        }
        return DB::instance()->id();
    }

    public function remove($user_id, $id) {
        $table = $this->table;
        $rs = new Resource;
        $status = false;
        DB::instance()->action(function($db) use ($table, $user_id, $rs, $id, &$status) {
            $cond = [
                'AND'   => [
                    'user_id'    => $user_id,
                    'lecture_id' => $id,
                    'is_lecture' => 1
                ]
            ];

            $r = $rs->set($cond, [
                'is_lecture'    => 0,
                'lecture_id'    => 0
            ]);
            if ($r->rowCount() <= 0) {
                $status = false;
                set_sys_error( $r->errorInfo()[2] );
                return false;
            }
            $r = $db->delete($table, [
                'AND'   => [
                    'user_id'   => $user_id,
                    'id'        => $id
                ]
            ]);
            if (!$r->rowCount() <= 0) {
                $status = false;
                set_sys_error( $r->errorInfo()[2]);
                return false;
            }
            $status = true;
        });

        if ($status === false) {
            return ApiRet::send($res, ErrInfo::DefErr( get_sys_error() ) );
        }
        return ApiRet::send($res, [
            'status'    => 0,
            'info'      => 'ok'
        ]);
    }

    public function update($cond, $data) {
        $r = DB::instance()->update($this->table, $data, $cond);
        if ($r->rowCount() <= 0) {
            return false;
        }

        return true;
    }

    public function lectureList($page = 1, $cond = [], $fields = []) {
        if (empty($fields)) {
            $fields = [
                'id',
                'lecture_name',
                'is_publish',
                'user_id',
                'add_time',
                'update_time',
                'chapter_list'
            ];
        }
        if (empty($cond)) {
            $cond = [
                'LIMIT' => [
                    ($page-1) * LEC_PAGESIZE, LEC_PAGESIZE
                ]
            ];
        }

        $lectures = DB::instance()->select($this->table, $fields, $cond);
        return $lectures;
    }

    public function pageInfo($cond = []) {
        if (empty($cond)) {
            $cond = [
                'id[>]' => 0
            ];
        }
        $total = DB::instance()->count($this->table, $cond);
        $page_count = (int)($total/LEC_PAGESIZE);
        $total_page = ($total % LEC_PAGESIZE == 0) ? $page_count : ($page_count+1);

        return [
            'total'         => $total,
            'total_page'    => $total_page
        ];
    }

    public function addChapter($user_id, $rsid) {
    
    }

    public function rmChapter($user_id, $rsid) {
    
    }

}

