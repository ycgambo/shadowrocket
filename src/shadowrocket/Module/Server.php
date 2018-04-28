<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       Server.php
 * @author     ycgambo
 * @update     4/26/18 10:09 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Module;

use ShadowRocket\Module\Base\ConfigRequired;
use ShadowRocket\Module\Base\LauncherModuleInterface;
use ShadowRocket\Net\Connection;

class Server extends ConfigRequired implements LauncherModuleInterface
{
    public $workers = array();

    public function initConfig(array $config = array())
    {
        $this->resetConfig($config);
        $this->declareRequiredConfig(array(
            'port',
            'password',
            'encryption',
            'process_num',
        ));
    }

    public function getReady()
    {
        array_push($this->workers, Connection::createServerWorker($this->getConfig(), 'tcp'));
        array_push($this->workers, Connection::createServerWorker($this->getConfig(), 'udp'));
    }
}
