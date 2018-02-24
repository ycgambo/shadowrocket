<?php

namespace ShadowRocket;

use Workerman\Worker;

class Launcher extends Configurable {
    private $_modules = array();

    function __construct(array $config = array()) {
        if (!extension_loaded('pcntl')) {
            exit("Please install pcntl extension. See http://doc3.workerman.net/install/install.html\n");
        }
        if (!extension_loaded('posix')) {
            exit("Please install posix extension. See http://doc3.workerman.net/install/install.html\n");
        }

        $this->initConfig();
        $this->setConfig($config);
    }

    public function initConfig() {
        $this->_config = array(
            'server'      => '127.0.0.1',
            'port'        => '8388',
            'password'    => 'mypass',
            'encryption'  => 'aes-256-cfb',
            'local_port'  => '6001',
            'process_num' => 12,
        );
    }

    // TODO: attach id for each server
    public function addServer(array $config = array()) {
        $module = new Server($this->_config);
        $module->setConfig($config);
        array_push($this->_modules, $module);
    }

    public function addLocal(array $config = array()) {
        $module = new Local($this->_config);
        $module->setConfig($config);
        array_push($this->_modules, $module);
    }

    public function launch() {
        foreach ($this->_modules as $module) {
            try {
                $module->checkConfig();
                $module->getReady();
            } catch (\Exception $exception) {
                throw $exception;
            }
        }
        Worker::runAll();
    }
}
