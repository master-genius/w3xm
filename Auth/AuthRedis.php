<?php
namespace Auth;

use \Core\SSL;
use \First\UserSession;


class AuthRedis implements \Interfaces\AuthInterface {

    private $config = [
        'host'      => '127.0.0.1',
        'port'      => 6379,

        'pre'       => 'cwr_user_',

        'timeout'   => 5400
    ];


    private $redis = null;

    public function __construct($options = []) {
        if (!empty($options)) {
            if (isset($options['host'])) {
                $this->config['host'] = $options['host'];
            }
            if (isset($options['port'])) {
                $this->config['port'] = $options['port'];
            }

            if (isset($options['pre'])) {
                $this->config['pre'] = $options['pre'];
            }
        }

        try {
            $this->redis = new \Redis();
            $this->redis->connect(
                    $this->config['host'],
                    $this->config['port']
                );
        } catch (\RedisException $e) {
            throw($e);
        }

    }

    private function makeKey($token) {
        $key = md5($token);
        return $this->config['pre'] . $key;
    }

    public function login($u) {
        $t = SSL::makeToken($u);

        $name = $this->makeKey($t['token']);
        
        $this->redis->set($name, $t['key']);
        $this->redis->expire($name, $this->config['timeout']);
        
        return $t['token'];
    }

    public function logout($token = '') {
        $name = $this->makeKey($token);
        $user_key = $this->redis->get($name);

        if (false === $user_key) {
            return true;
        }
        if ($this->redis->delete($name) > 0) {
            return true;
        }
        
        return false;
    }

    public function get($token = '') {
        $name = $this->makeKey($token);
        file_put_contents('/tmp/buffer.log', $token);
        $user_key = $this->redis->get($name);
        if (false === $user_key) {
            return false;
        }

        $u = SSL::decryptToken($token, $user_key);
        return $u;
    }

    public function user() {
        return $this->get(get_data('api_token'));
        //return false;
    }

}

