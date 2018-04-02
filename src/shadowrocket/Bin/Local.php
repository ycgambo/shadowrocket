<?php

namespace ShadowRocket\Bin;

use ShadowRocket\Helper\Configurable;
use ShadowRocket\Net\Connection;

class Local extends Configurable
{
    public $workers = array();

    public function __construct(array $config = array())
    {
        self::setConfig($config);
        self::setRequiredConfig(array(
            'server',
            'port',
            'password',
            'encryption',
            'local_port',
            'process_num',
        ));
    }

    public function getReady()
    {
        array_push($this->workers, Connection::createLocalWorker(self::getConfig(), 'tcp'));
        array_push($this->workers, Connection::createLocalWorker(self::getConfig(),'udp'));
    }
}
