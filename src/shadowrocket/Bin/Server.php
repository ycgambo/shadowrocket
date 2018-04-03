<?php

namespace ShadowRocket\Bin;

use ShadowRocket\Helper\ConfigRequired;
use ShadowRocket\Helper\LauncherModuleInterface;
use ShadowRocket\Net\Connection;

class Server extends ConfigRequired implements LauncherModuleInterface
{
    public $workers = array();

    public function __construct(array $config = array())
    {
        self::setConfig($config);
        self::setRequiredConfig(array(
            'port',
            'password',
            'encryption',
            'process_num',
        ));
    }

    public function getReady()
    {
        array_push($this->workers, Connection::createServerWorker(self::getConfig(), 'tcp'));
        array_push($this->workers, Connection::createServerWorker(self::getConfig(), 'udp'));
    }
}
