<?php
namespace Model;

use \Core\DB;

class Media {

    private $table = 'media';


    public function add($media) {
        $r = DB::instance()->insert($this->table, $media);

        if ($r->rowCount() <= 0) {
            set_sys_error($r->errorInfo()[2]);
            return false;
        }
        return DB::instance()->id();
    }

    public function mediaList($cond, $fields = []) {
        if (empty($fields)) {
            $fields = [
                'id',
                'media_name',
                'media_size',
                'upload_time'
            ];
        }

        return DB::instance()->select($this->table, $fields, $cond);
    }

    public function pageInfo($cond, $pagesize = 0) {
        if ($pagesize <= 0) {
            $pagesize = IMG_PAGESIZE;
        }

        $total = DB::instance()->count($this->table, $cond);
        $total_page = ($total%$pagesize == 0) ? $total/$pagesize : (int)($total/$pagesize + 1);
        
        return [
            'total'     => $total,
            'total_page'=> $total_page
        ];
    }

    public function remove($filename, $cond) {
        if (file_exists($filename)) {
            $r = unlink($filename);
            if (!$r) {
                set_sys_error('删除文件失败');
                return false;
            }
        }

        $mi = $this->get($cond);
        if (empty($mi)) {
            goto remove_end;
        }

        if ($mi['wx_status'] == 1) {
            $r = DB::instance()->update($this->table, ['is_delete'  => 1], $cond);
        } else {
            $r = DB::instance()->delete($this->table, $cond);
        }

        if ($r->rowCount() <= 0) {
            set_sys_error($r->errorInfo()[2]);
            //return false;
        }

        remove_end:;
        return true;
    }

    public function get($cond, $fields = []) {
        if (empty($fields)) {
            $fields = [
                'id',
                'media_name',
                'wx_status'
            ];
        }
        
        $r = DB::instance()->get($this->table, $fields, $cond);
        if (empty($r)) {
            return false;
        }

        return $r;
    }

    public function mediaStats($cond) {
        return DB::instance()->count($this->table, $cond);
    }

}

