<?php

namespace ShadowRocket;

use Workerman\Worker;
use ShadowRocket\Helper\Configurable;
use ShadowRocket\Bin\Local;
use ShadowRocket\Bin\Server;

class Launcher extends Configurable
{
    private $_modules = array();

    function __construct(array $config = array())
    {
        $this->initConfig();
        $this->setConfig($config);
    }

    public function initConfig()
    {
        $this->_config = array(
            'server' => '127.0.0.1',
            'port' => '8388',
            'password' => 'mypass',
            'encryption' => 'aes-256-cfb',
            'local_port' => '1086',
            'process_num' => 12,
        );
    }

    public function addServer(array $config = array())
    {
        $config = empty($config) ? $this->_config : array_replace($this->_config, $config);
        $module = new Server($this->_config);
        $module->setConfig($config);
        $this->_modules[$config['port']] = $module;
    }

    public function addLocal(array $config = array())
    {
        $config = empty($config) ? $this->_config : array_replace($this->_config, $config);
        $module = new Local($this->_config);
        $module->setConfig($config);
        $this->_modules[$config['port']] = $module;
    }

    public function launchAll()
    {
        foreach ($this->_modules as $module) {
            $module->checkConfig();
        }

        foreach ($this->_modules as $module) {
            try {
                $module->getReady();
            } catch (\Exception $e) {
                throw $e;
            }
        }

        Worker::runAll();
    }
}
