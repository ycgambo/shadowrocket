<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       Manager.php
 * @author     ycgambo
 * @update     4/29/18 6:41 PM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Module;

use ShadowRocket\Bin\Launcher;
use ShadowRocket\Module\Base\ConfigRequired;
use ShadowRocket\Module\Base\LauncherModuleInterface;
use ShadowRocket\Net\Connection;
use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;

class Manager extends ConfigRequired implements LauncherModuleInterface
{
    public function initConfig(array $config = array())
    {
        $this->resetConfig($config);
        $this->declareRequiredConfig(array(
            'port',
            'token',
            'process_num' => 1,
        ));
    }

    public function getReady()
    {
        $config = $this->getConfig();

        $worker = new Worker('tcp://0.0.0.0:' . $config['port']);
        $worker->count = $config['process_num'];
        $worker->name = 'shadowsocks-manager';

        $worker->onConnect = function ($client) use ($config) {
            $client->stage = Connection::STAGE_INIT;
        };

        $worker->onMessage = function ($client, $buffer) use ($config) {
            switch ($client->stage) {
                case Connection::STAGE_INIT:
                    if ($buffer == $config['token']) {
                        $client->stage = Connection::VARIFIED;
                        $client->send(Manager::guideMsg());
                    }
                    break;
                case Connection::VARIFIED:
                    return 'cmd';
            }
        };
    }

    public static function guideMsg()
    {
        return 'hello';
    }

}