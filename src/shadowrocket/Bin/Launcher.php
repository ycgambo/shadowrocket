<?php

namespace ShadowRocket\Bin;

use ShadowRocket\Helper\Configurable;

use Workerman\Worker;

class Launcher extends Configurable
{
    private $_modules = array();

    function __construct(array $config = array())
    {
        self::setConfig(array(
            'server' => array(
                'port' => '8388',
                'password' => 'mypass',
                'encryption' => 'aes-256-cfb',
                'process_num' => 12,
            ),
            'local' => array(
                'server' => '127.0.0.1',
                'port' => '8388',
                'password' => 'mypass',
                'encryption' => 'aes-256-cfb',
                'local_port' => '1086',
                'process_num' => 12,
            ),
        ));
        self::setConfigItems($config);
    }

    public function addServer(array $config = array())
    {
        $config = empty($config)
            ? self::getConfig('server')
            : array_replace(self::getConfig('server'), $config);
        $module = new Server($config);
        $this->_modules[spl_object_hash($module)] = $module;
    }

    public function addLocal(array $config = array())
    {
        $config = empty($config)
            ? self::getConfig('local')
            : array_replace(self::getConfig('local'), $config);
        $module = new Local($config);
        $this->_modules[spl_object_hash($module)] = $module;
    }

    public function launchAll()
    {
        foreach ($this->_modules as $hash => $module) {
            if ($module::hasRequiredConfig() && ($missing_config = $module::getMissingConfig())) {
               throw new \Exception("Missing config of $hash:" . implode(', ', $missing_config));
            }
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
