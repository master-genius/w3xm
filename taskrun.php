<?php
require ('vendor/autoload.php');

//$pname = explode('.', $argv[0])[0];
$pname = $argv[0];
$pid = posix_getpid();

$has = `ps -e -o user,pid,ppid,comm,args,tty | grep 'php.*$pname' | egrep -v 'grep|$pid' | awk -F' ' '{printf $2}'`;
if ($has) {

    if ($argc > 1 && ($argv[1] === 'restart' || $argv[1] === 'stop') ) {
        $r = `kill $has`;
        if ($r) {
            exit("Failed to stop server\n");
        }
        if ($argv[1] === 'stop') {
            exit(0);
        }
    } else {
        echo $argv[0] . " already running : $has\n";
        exit(0);
    }
}

$pid = pcntl_fork();
if ($pid < 0) {
    exit("Error: fork failed");
}

if ($pid > 0) {
    exit(0);
}

posix_setsid();

(new \Task\Serv)->runServer();

