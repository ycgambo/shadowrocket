<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/shadowrocket/Autoloader.php';

use ShadowRocket\Launcher;
use ShadowRocket\Server;

new \ShadowRocket\Autoloader();
$sr = new Launcher();
$sr->launch(array(new Server()));
