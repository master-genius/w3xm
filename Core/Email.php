<?php
namespace Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email {

    private $mail = false;
    private $config = [];

    public function __construct() {
        $this->mail = new PHPMailer;
        try {
            $this->config = include (CONFIG_PATH . '/email_server.php');
        } catch (\Exception $e) {
            throw($e);
        }
    }

    public function send($to, $subject, $content) {
        try {
            //$this->mail->SMTPDebug = 2;
            $this->mail->isSMTP();
            $this->mail->Host = $this->config['host'];
            $this->mail->Username = $this->config['email'];
            $this->mail->Password = $this->config['password'];
            $this->mail->SMTPAuth = true;
            $this->mail->SMTPSecure = $this->config['secure'];
            $this->mail->Port = $this->config['port'];
            $this->mail->setFrom($this->config['email'], $this->config['from_name']);
            $this->mail->CharSet = 'UTF-8';
            $this->mail->setLanguage('zh_cn');
            $this->mail->SMTPOptions = [
                'ssl'   => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true
                ]
            ];

            if (is_string($to)) {
                $this->mail->addAddress($to);
            } elseif (is_array($to)) {
                foreach ($to as $e) {
                    $this->mail->addAddress($e);
                }
            } else {
                return false;
            }

            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $content;

            $this->mail->send();

        } catch (Exception $e) {
            set_sys_error($e->getMessage());
            return false;
        }
        return true;
    }

}

