<?php
require_once __DIR__ . '/../vendor/autoload.php';

$config = array(
    'server' => array(
        'port' => '8388',
        'password' => 'mypass',
        'encryption' => 'aes-256-cfb',
        'process_num' => 12,
    ),
    'logger' => array(
        'name' => 'shadowrocket_logger',
        'handlers' => array(
            new \Monolog\Handler\StreamHandler(__DIR__.'/sr.log', \Monolog\Logger::DEBUG),
        ),
    ),
);

ShadowRocket\Bin\Launcher::launch($config);
