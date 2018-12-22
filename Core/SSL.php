<?php
namespace Core;

class SSL
{
    static private $iv = 'braveximingenius';

    static private $method = 'AES-256-CBC';

    static private $key_pre = 'xm_';

    static private $salt_arr = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n',
        'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',

        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N',
        'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
    ];
 
    static public function makeToken($u){
        $key = self::$key_pre . mt_rand(100,999) . time();
        $token = openssl_encrypt(serialize($u), self::$method, $key, 0, self::$iv);
        return [
            'token' => $token,
            'key'   => $key
        ];
    }

    static public function decryptToken($token, $key) {
        $u = openssl_decrypt($token, self::$method, $key, 0, self::$iv);
        return unserialize($u);
    }

    static public function hashPasswd($passwd, $salt='') {
        return hash('sha512', md5($passwd . $salt) );
    }

    static public function createSalt($length = 10) {
        $sa = array_rand(self::$salt_arr, $length);
        $rand_arr = [];
        foreach($sa as $i) {
            $rand_arr[] = self::$salt_arr[$i];
        }
        return implode($rand_arr);
    }

}

