<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       Server.php
 * @author     ycgambo
 * @create     4/4/18 9:01 AM
 * @update     4/4/18 9:01 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

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
