<?php
namespace Core;

class ApiRet {
    
    static public $CORS = '.w3xm.top';

    static public function send($res, $data = '') {
        return $res->withHeader(
            'Access-Control-Allow-Origin',
            self::$CORS
        )->withStatus(200)->write(
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );
    }

    static public function raw($res, $data = '') {
    
        return $res->withHeader(
            'Access-Control-Allow-Origin',
            self::$CORS
        )->withHeader(
            'Access-Control-Allow-Credentials',
            "true"
        )->withStatus(200)->write($data);
    }

}

