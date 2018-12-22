<?php
namespace Access;

use \Core\DB;
use \Error\ErrInfo;
use \Model\UAPI;
use \Model\Resource;
use \Core\ApiRet;

/*
    继承自Reader，具备Reader的所有功能，除此以外，
    还有Writer特有的：创建/发布文章，删除文章等功能。
*/

class Writer extends Reader {

    public $time_day    = 86400;


    public function filterData($text) {
        $text = preg_replace('/< *script *>/im', '', $text);
        $text = preg_replace('/< *script *src.*>/im', '', $text);
        $text = preg_replace('/< *\/script *>/im', '', $text);

        return $text;
    }

    public function preGetData($filter = []) {

        if (empty($filter)) {
            $filter = [
                'rs_title',
                'rs_content',
                'rs_keywords',
                'description',
                'rs_group'
            ];
        }

        $data = auto_post_data($filter, true);
        foreach($data as $k=>$v) {
            $data[$k] = $this->filterData($v);
        }

        if (isset($data['rs_keywords'])) {
            $data['rs_keywords'] = str_replace('，', ',', $data['rs_keywords']);
        }

        return $data;
    }

    public function wRsList($req, $res) {
        $page = get_data('page');
        if (!is_numeric($page) || $page <= 0) {
            $page = 1;
        }

        $cond = $this->preRsListCond($req, $res);

        $cond['AND']['user_id'] = $this->user['id'];
        unset($cond['AND']['is_publish']);

        $rsobj = new Resource;
        
        $pi = $rsobj->pageInfo($cond);

        $cond['LIMIT'] = [
            RS_PAGESIZE * ($page-1), RS_PAGESIZE
        ];



        return ApiRet::send($res, [
            'status'    => 0,
            'rs_list'    => $rsobj->rsList($page, $cond),

            'total_page' => $pi['total_page'],
            'total'      => $pi['total'],
            'cur_page'   => $page
        ]);

    }


    public function add($req, $res, $publish = false) {
        $uid = $this->user['id'];
        $content_type = post_data('content_type');

        $rs = new Resource;

        $tm = time();
        $count = $rs->stats([
            'AND'   => [
                'user_id'   => $uid,
                'add_time[>]'  => $tm - $this->time_day,
                'add_time[<]'  => $tm
            ]
        ]);
        
        if ($count >= RS_MAX_PUB) {
            return ApiRet::send($res, ErrInfo::DefErr('Out of limit: 50/day'));
        }

        $data = $this->preGetData();

        $data['is_publish'] = ($publish ? 1 : 0);
        $tm = time();
        $data['add_time'] = $tm;
        $data['update_time'] = $tm;
        $data['author_name'] = $this->user['nickname'];
        if (!empty($content_type)) {
            //markdown
            $data['content_type'] = 1; 
        }

        if (!isset($data['rs_title']) || empty($data['rs_title'])) {
            return ApiRet::send($res,
                        ErrInfo::DefErr('Bad-Data: 标题不能为空')
                    );
        }
        
        if (strlen($data['rs_content']) > RS_CONTENT_LIMIT) {
            return ApiRet::send($res, ErrInfo::DefErr('Bad-Data: 内容超出最大限制'));
        }

        $r = $rs->add($uid, $data);
        if (!$r) {
            return ApiRet::send($res, ErrInfo::DefErr( get_sys_error() ));
        }
        
        return ApiRet::send($res, [
            'status'    => 0,
            'id'        => $r
        ]);

    }

    public function publish($req, $res) {
        $uid = $this->user['id'];
        $id = post_data('resource_id');

        $r = (new Resource)->update($uid, $id, [
            'is_publish' => 1
        ]);

        if (!$r) {
            return ApiRet::send($res, ErrInfo::DefErr('data not update'));
        }

        return ApiRet::send($res, [
            'status'    => 0,
            'info'      => 'ok'
        ]);
    }

    public function update($req, $res) {
    
        $uid = $this->user['id'];
        $id = post_data('id');

        $rs = new Resource;

        $tm = time();
        
        $filter = [
            'rs_title',
            'rs_content',
            'rs_keywords',
            'is_publish',
            'description',
            'rs_group'
        ];
        $data = $this->preGetData($filter);

        if (!isset($data['rs_title']) && empty($data['rs_title'])) {
            return ApiRet::send($res, ErrInfo::DefErr('Bad-Data: title not be empty'));
        }
        
        
        if (isset($data['rs_content']) 
            && strlen($data['rs_content']) > RS_CONTENT_LIMIT)
        {
            return ApiRet::send($res, ErrInfo::DefErr('Bad-Data: content too large'));
        }

        $data['update_time'] = time();
        $data['version[+]'] = 1;
        $data['author_name'] = $this->user['nickname'];

        $r = $rs->update($uid, $id, $data);
        if (!$r) {
            return ApiRet::send($res, ErrInfo::DefErr( '没有更新数据：' . get_sys_error() ));
        }
        
        return ApiRet::send($res, [
            'status'    => 0,
            'info'      => 'ok'
        ]);
    }

    public function addPublish($req, $res) {
        return $this->add($req, $res, true);
    }

    public function remove($req, $res) {
        
        $uid = $this->user['id'];
        $id = post_data('resource_id');
        $real = post_data('real');
        if (empty($real)) {
            $real = false;
        }

        $rs = new Resource;

        $r = $rs->remove($uid, $id, true);
        if (!$r) {
            return ApiRet::send($res, ErrInfo::DefErr(get_sys_error()));
        }
        
        return ApiRet::send($res, [
            'status'    => 0,
            'info'      => 'ok'
        ]);

    }

    public function removeBatch($req, $res) {
        $uid = $this->user['id'];
        $real = post_data('real');
        $idtext = post_data('idlist');
        $idlist = json_decode($idtext, true);
        if (!idlist || empty($idlist)) {
            return ApiRet::send($res, ErrInfo::RetErr('ERR_BAD_DATA'));
        }

        $r = (new Resource)->removeBatch($uid, $idlist, true);
        if (!$r) {
            return ApiRet::send($res, ErrInfo::DefErr(sys_get_error()));
        }

        return ApiRet::send($res, [
            'status'    => 0,
            'info'      => 'ok'
        ]);

    }

    public function myPostList($req, $res) {
        $kwd = get_data('kwd');
        $page = get_data('page');
        $group = get_data('group');



    }


}

