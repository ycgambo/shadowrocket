<?php

namespace ShadowRocket\Bin;

use ShadowRocket\Helper\Configurable;
use ShadowRocket\Net\Connection;

class Server extends Configurable
{
    public $workers = array();

    public function __construct(array $config = array())
    {
        $this->setConfig($config);
    }

    public function checkConfig()
    {
        $check_list = array(
            'password',
            'encryption',
            'port',
            'process_num',
        );

        foreach ($check_list as $check_item) {
            if (!isset($this->_config[$check_item])) {
                throw new \Exception('require config: ' . $check_item);
            }
        }
    }

    public function getReady()
    {
        array_push($this->workers, Connection::createServerWorker($this->_config, 'tcp'));
        array_push($this->workers, Connection::createServerWorker($this->_config, 'udp'));
    }
}
