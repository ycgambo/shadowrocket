<?php
require_once __DIR__ . '/../vendor/autoload.php';

$config = array(
    'server' => array(
        'port' => '8388',
        'password' => 'mypass',
        'encryption' => 'aes-256-cfb',
        'process_num' => 2,
    ),
    'manager' => array(
        'port' => 6001,
        'token' => 123456,
    ),
    'guarder' => array(),
);

ShadowRocket\Bin\Launcher::launch($config);
