<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       Server.php
 * @author     ycgambo
 * @create     4/4/18 10:49 AM
 * @update     4/4/18 10:40 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Module;

use ShadowRocket\Module\ConfigRequired;
use ShadowRocket\Module\LauncherModuleInterface;
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