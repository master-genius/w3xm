<?php
namespace Task;

/*
 *此类定义了基本的任务列表，Redis的服务进程
 *和客户端进程都会继承此任务
 *
 *
 **/

class InitTask {

    public $host      = '127.0.0.1';
    public $port      = 6379;

    protected $redis = false;

    protected $config = [];

    public function __construct($options = []) {
        try {
            $this->config = include (CONFIG_PATH . '/config.php');
            $this->redis = new \Redis();
            //$this->connect($this->config['host'], $this->config['port']);
        } catch (\Exception $e) {
            throw($e);
        }
    }


}

