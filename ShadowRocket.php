<?php
require_once __DIR__ . '/vendor/autoload.php';

use ShadowRocket\Launcher;
use ShadowRocket\Server;

$sr = new Launcher();
$sr->launch(array(new Server()));
