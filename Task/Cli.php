<?php
namespace Task;

class Cli extends \Task\InitTask {

    public function __construct() {
        parent::__construct();

        $this->redis->connect($this->host, $this->port);
    }

    /*
     *msg = [
     *  
     *  'email'     => 'your@email.com',
     *  'randstr'   => 'dlfweiofhowf',
     *  'email_verify_str' => 'dfe234sfweg'
     *]
     * */

    public function sendVerifyEmail($msg) {
        $this->redis->publish('reg-email-verify', json_encode($msg));
    }

    public function sendClearEmail($msg) {
        $this->redis->publish('clear-email', json_encode($msg));
    }

    public function sendFindPasswdEmail($msg) {
        $this->redis->publish('find-passwd', json_encode($msg));
    }

}

