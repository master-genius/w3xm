<?php
namespace Task;

use \Core\Email;
use \Core\DB;
use \Model\Users;

class Serv extends \Task\InitTask {

    private $temp = [

        'reg-email-verify'     => 
            "<h2>注册邮箱验证</h2>
            <p>请点击以下链接，如果无法点击可复制链接访问：</p>
            <a href=\"%s\">%s</a>
            
        ",

        //找回密码邮件格式
        'find-passwd'       => "
            <h2>找回密码操作，如非本人操作可忽略。</h2>
            <a href=\"%s\">%s</a>
        ",

        //重置未验证的邮箱
        'clear-email'       => "
            <h2>点击以下链接可重置已被注册但未被验证的邮箱：</h2>
            <a href=\"%s\">%s</a>
        ",
    
    ];

    public function taskDispatch($redis, $chan, $msg) {
        file_put_contents('/tmp/email.log', $msg);
        
        $msg = json_decode($msg, true);

        switch ($chan) {

            case 'reg-email-verify':
                $this->registerVerifyEmail($msg);
                break;

            case 'find-passwd':
                $this->findPasswdEmail($msg);
                break;

            case 'clear-email':
                $this->clearEmail($msg);
                break;

            default:   ;
        }

    }

    public function runServer() {

        try {
            $this->redis->pconnect($this->host, $this->port);
            $this->redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
            //$this->redis->subscribe($this->task_list, [$this, 'taskDispatch']);
            $this->redis->subscribe(
                array_keys($this->temp),
                [$this, 'taskDispatch']
            );
        } catch (\RedisException $e) {
            throw($e);
        }

    }

    public function registerVerifyEmail($msg) {
        //$cfg = include (CONFIG_PATH . '/config.php');
        $verify_url = $this->config['page_host']
                . '/v/email-verify?randstr='
                . $msg['randstr']
                . '&vstr=' . $msg['email_verify_str'];

        $content = sprintf($this->temp['reg-email-verify'], $verify_url, $verify_url);

        $r = (new Email)->send($msg['email'], '邮箱验证', $content);
        return $r;
    }

    public function findPasswdEmail($msg) {
        $verify_url = $this->config['page_host']
                    . '/v/findpasswd?randstr='
                    . $msg['randstr']
                    . '&vstr=' . $msg['email_verify_str'];

        $content = sprintf(
                        $this->temp['find-passwd'],
                        $verify_url,
                        $verify_url
                    );
        $r = (new Email)->send($msg['email'], '找回密码', $content);
        return $r;
    }

    public function clearEmail($msg) {
        $verify_url = $this->config['page_host']
                    . '/v/clearemail?randstr='
                    . $msg['clear_email_randstr']
                    . 'email=' . $msg['email'];

        $content = sprintf($this->temp['clear-email'],
                        $verify_url,
                        $verify_url
                    );
        $r = (new Email)->send($msg['email'], '重置邮箱', $content);
        return $r;
    }

}

