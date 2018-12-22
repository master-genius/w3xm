<?php
namespace Middleware;

use \Error\ErrInfo;
use \Core\ApiRet;

class MediaWare {

    public $ext_type = [
        'jpg',
        'png',
        'jpeg',
    ];

    public $mime_type = [
        'image/jpeg',
        'image/png',
        'image/gif'
    ];

    //size limit in bytes
    public $max_size = 2048000;

    //mode : ext OR mime
    public $filter_mode = 'mime';

    public $upload_name = '';


    public function __construct($options = []) { 
        if (isset($options['upload_name'])) {
            $this->upload_name = $options['upload_name'];
        }

        if (isset($options['max_size'])) {
            $this->max_size = $options['max_size'];
        }

        if (isset($options['filter_mode'])) {
            $this->filter_mode = $options['filter_mode'];
        }

        if (isset($options['mime_type'])) {
            $this->mime_type = $options['mime_type'];
        }
        
        if (isset($options['ext_type'])) {
            $this->ext_type = $options['ext_type'];
        }

    }

    public function check($file) {
        $filename = $file->getClientFilename();

        $type = $file->getClientMediaType();

        if (array_search($type, $this->mime_type) === false) {
            return [
                false,
                'Error: file type not allow -- ' . $filename . ' ' . $type
            ];
        }

        $size = $file->getSize();

        if ($size > $this->max_size) {
            return [
                false,
                'Error: ' . $filename . 
                ' out of limit -- ' . $this->max_size . ' bytes'
            ];
        }

        return [
            true,
            'ok'
        ];
    }

    public function checkArr($files) {
        foreach($files as $file) {
            $r = $this->check($file);
            if ($r[0] === false) {
                return $r;
            }
        }

        return [
            true,
            'ok'
        ];
    }

    public function filter($req) {

        $files = $req->getUploadedFiles();

        if (empty($this->upload_name) || !isset($files[$this->upload_name])) {
            if (count($files) == 0) {
                return [
                    false,
                    'Error: upload-file not found'
                ];
            }

            foreach($files as $k=>$f) {
                $r = (is_array($f) ? $this->checkArr($f) : $this->check($f) );
                if ($r[0] === false) {
                    return $r;
                }
            }
        } else {
            $file = $files[$this->upload_name];
            if (is_array($file) && count($file) == 0) {
                return [false, 'Error: upload-file not found'];
            }
            return ( is_array($file) ? $this->checkArr($file) : $this->check($file) );
        }

        return [ true, 'ok' ];
    }


    public function __invoke($req, $res, $next) {

        $r = $this->filter($req);

        if ($r[0] === false) {
            return ApiRet::send($res, ErrInfo::DefErr($r[1]));
        }

        $res = $next($req, $res);
        return $res;
    }


}

