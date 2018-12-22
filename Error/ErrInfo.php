<?php
namespace Error;

class ErrInfo {

    static private $err_table = [];

    static private $default_err = [
        'status'  => 40001,
        'errinfo' => 'Request failed, something wrong'
    ];

    static protected function GetErrTable() {
        try {
            self::$err_table = include (CONFIG_PATH . '/errcode.php');
        } catch (\Exception $e) {
            self::$err_table = [];
        }
    }


    static public function RetErr($errname) {
        self::GetErrTable();
        if (isset(self::$err_table[$errname])) {
            return self::$err_table[$errname];
        }
        return self::$default_err;
    }

    static public function DefErr($info) {
        self::GetErrTable();
        if (isset(self::$err_table['ERR_USER_DEF'])) {
            $err = self::$err_table['ERR_USER_DEF'];
            $err['errinfo'] = $info;
            return $err;
        }
        return self::$default_err;
    }

}

