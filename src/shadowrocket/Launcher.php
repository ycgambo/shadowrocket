<?php

namespace ShadowRocket;

use Workerman\Worker;

class Launcher
{
    private $_config = [
        'server'        => '127.0.0.1',
        'port'          => '8388',
        'password'      => '536.shadowsocks',
        'encryption'    => 'aes-256-cfb',
        'local_port'    => '4001',
        'process_num'   => 12,
    ];

    function __construct(array $config = []) {
        if (!extension_loaded('pcntl')) {
            exit("Please install pcntl extension. See http://doc3.workerman.net/install/install.html\n");
        }
        if (!extension_loaded('posix')) {
            exit("Please install posix extension. See http://doc3.workerman.net/install/install.html\n");
        }

        foreach ($config as $key => $value) {
            $this->_config[$key] = $value;
        }
    }

    public function launch(array $modules = []) {
        foreach ($modules as $module) {
            $module->register($this->_config);
        }
        Worker::runAll();
    }
}
