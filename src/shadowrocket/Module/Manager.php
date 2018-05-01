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
use ShadowRocket\Module\Helper\CustomGetOptHelp;
use ShadowRocket\Module\Helper\ManagerCommandParser;
use ShadowRocket\Net\Connection;
use Workerman\Worker;
use GetOpt\ArgumentException;

class Manager extends ConfigRequired implements LauncherModuleInterface
{
    public function init()
    {
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
            $parser = new ManagerCommandParser();

            switch ($client->stage) {
                case Connection::STAGE_INIT:
                    if ($buffer == $config['token']) {
                        $client->stage = Connection::VERIFIED;
                        $client->send($parser->getHelpText());
                    }
                    break;
                case Connection::VERIFIED:
                    try {
                        if ($command = $parser->parseCommand($buffer)) {
                            Manager::handle($command, $parser);
                            $client->send(PHP_EOL . 'success' . PHP_EOL);
                        } else {
                            $client->send($parser->getHelpText());
                        }
                    } catch (ArgumentException $exception) {
                        $client->send(PHP_EOL . $exception->getMessage() . PHP_EOL . $parser->getHelpText());
                    }
            }
        };
    }

    /**
     * @param $command
     * @param $parser
     * @throws \Exception
     */
    protected static function handle($command, $parser)
    {
        switch ($command) {
            case 'server:add':
                foreach ($parser->getOperand('ports') as $port) {
                    Launcher::superaddModule('server', array(
                        'name' => $parser->getOption('name') . '_' . $port,
                        'port' => $port,
                        'password' => $parser->getOperand('password'),
                        'process_num' => $parser->getOption('process'),
                    ));
                }
                break;
        }
    }
}
