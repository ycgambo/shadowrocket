<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/shadowrocket/Autoloader.php';

use ShadowRocket\Launcher;

new ShadowRocket\Autoloader();
$launcher = new Launcher();
$launcher->addServer(array('port' => 8389));
$launcher->launch();
