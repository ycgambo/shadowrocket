<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/shadowrocket/Autoloader.php';

use ShadowRocket\Launcher;

new ShadowRocket\Autoloader();
$launcher = new Launcher(array(
    'server' => '127.0.0.1',
    'port' => '8388',
    'password' => 'mypass',
    'encryption' => 'aes-256-cfb',
    'local_port' => '1086',
    'process_num' => 12,
));
$launcher->addServer(array('port' => 8389));
$launcher->launchAll();
