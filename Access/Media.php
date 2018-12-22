<?php
namespace Access;

use \Core\ApiRet;
use \Error\ErrInfo;


class Media extends \First\First {

    private $map_dir = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 
        'h', 'i', 'j', 'k', 'l', 'm', 'n',
        'o', 'p', 'q', 'r', 's', 't', 'u',
        'v', 'w', 'x', 'y', 'z'
    ];

    public function get($req, $res) {
    
    }

    public function upload($req, $res) {

        $m = new \Model\Media;
        if ($m->mediaStats(['user_id' => $this->user['id']]) >= USER_IMAGE_LIMIT) {
            return ApiRet::send($res, ErrInfo::DefErr('超出最大数量限制：' . USER_IMAGE_LIMIT));
        }

        $files = $req->getUploadedFiles();

        if (!isset($files['image'])) {
            return ApiRet::send($res, ErrInfo::RetErr('ERR_BAD_DATA'));
        }
        $file = $files['image'];

        $sub_dir = $this->map_dir[array_rand($this->map_dir)];
        $target_path = UPLOAD_IMAGE_PATH . '/' . $sub_dir . '/';
        
        $ext = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);

        $upload_name = $sub_dir . hash('sha1', 'u_' . time() . mt_rand(100, 1000))
                    . '.' . $ext;

        try{
            $r = $file->moveTo($target_path . $upload_name);
        } catch (\RuntimeException $e) {
            return ApiRet::send($res, ErrInfo::DefErr($e->getMessage()));
        } catch (\InvalidArgumentException $e) {
            return ApiRet::send($res, ErrInfo::DefErr($e->getMessage()));
        }

        $r = $m->add([
            'media_name'    => $upload_name,
            'media_size'    => $file->getSize(),
            'media_path'    => $target_path,
            'media_type'    => $file->getClientMediaType(),
            'user_id'       => $this->user['id'],
            'upload_time'   => time()
        ]);

        return ApiRet::send($res, [
            'status'   => 0,
            'sub_dir'  => $sub_dir,
            'filename' => $upload_name
        ]);
    }


    public function remove($req, $res) {
        //$media_name = post_data('media_name');
        //
        $id = post_data('id');

        $cond = [
            'AND'   => [
                'id'      => $id,
                //'media_name' => $media_name,
                'user_id'    => $this->user['id']
            ]
        ];

        $m = new \Model\Media;

        $mi = $m->get($cond, []);
        if (empty($mi)) {
            return ApiRet::send($res, ErrInfo::RetErr('ERR_BAD_DATA'));
        }

        $filename = UPLOAD_IMAGE_PATH . '/' . $mi['media_name'][0] . '/' . $mi['media_name'];

        $r = $m->remove($filename, ['id' => $mi['id']]);
        if (!$r) {
            return ApiRet::send($res, ErrInfo::DefErr( get_sys_error() . $filename ));
        }

        return ApiRet::send($res, [
            'status'    => 0,
            'info'      => 'ok'
        ]);

    }


    public function mediaList($req, $res) {
        $page = get_data('page');
        $tag = get_data('tag');

        if (!is_numeric($page) || $page <= 0) {
            $page = 1;
        }

        $cond = [
            'AND'   => [
                'is_delete' => 0,
                'user_id'   => $this->user['id'],
            ],

            'LIMIT' => [
                ($page-1)*IMG_PAGESIZE, IMG_PAGESIZE
            ],
            'ORDER' => [
                'upload_time'   => 'DESC'
            ]

        ];

        if (!empty($tag)) {
            $cond['AND']['media_tag[~]'] = $tag;
        }

        $m = new \Model\Media;

        $pi = $m->pageInfo(['AND'   => $cond['AND'] ]);
        
        return ApiRet::send($res, [
            'status'        => 0,
            'media_list'    => $m->mediaList($cond),
            'cur_page'      => $page,
            'total'         => $pi['total'],
            'total_page'    => $pi['total_page']
        ]);

    }

    public function settag($req, $res) {
    
    }


}


