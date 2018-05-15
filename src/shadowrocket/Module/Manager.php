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
use ShadowRocket\Exception\ConfigException;
use ShadowRocket\Module\Base\ConfigRequired;
use ShadowRocket\Module\Base\LauncherModuleInterface;
use ShadowRocket\Module\Base\ManagerInterface;
use ShadowRocket\Module\Helper\CustomGetOptHelp;
use ShadowRocket\Module\Helper\ManagerCommandParser;
use ShadowRocket\Net\Connection;
use Workerman\Worker;
use GetOpt\ArgumentException;

class Manager extends ConfigRequired implements LauncherModuleInterface, ManagerInterface
{
    protected static $servers = array();

    public function init()
    {
        $this->declareRequiredConfig(array(
            'port',
            'token',
            'instance' => new self(),
        ));
    }

    public function getReady()
    {
        $instance = $this->getConfig('instance');

        if (!$instance instanceof ManagerInterface) {
            throw new ConfigException('A Manager should implements ShadowRocket\Module\Base\ManagerInterface');
        }

        $this->preBoot();

        $this->listen();
    }

    protected function listen()
    {
        $config = $this->getConfig();

        $worker = new Worker('tcp://0.0.0.0:' . $config['port']);
        $worker->count = 1;
        $worker->name = 'shadowsocks-manager';

        $worker->onConnect = function ($client) use ($config) {
            $client->stage = Connection::STAGE_INIT;
        };

        $manager = $this;

        $worker->onMessage = function ($client, $buffer) use ($config, $manager) {
            $buffer = rtrim($buffer);
            switch ($client->stage) {
                case Connection::STAGE_INIT:
                    if ($buffer == $config['token']) {
                        $parser = new ManagerCommandParser();
                        $client->stage = Connection::VERIFIED;
                        $client->send($parser->getHelpText());
                    }
                    break;
                case Connection::VERIFIED:
                    $parser = new ManagerCommandParser();
                    try {
                        if ($command = $parser->parseCommand($buffer)) {
                            if ($manager->_denyCommand($command, $buffer)) {
                                $client->send(PHP_EOL . 'Failed: Illegal Command' . PHP_EOL);
                            } else {
                                $client->send(PHP_EOL . $manager->handle($command, $parser) . PHP_EOL);
                            }
                        } else {
                            $client->send($parser->getHelpText());
                        }
                    } catch (ArgumentException $exception) {
                        $client->send(PHP_EOL . $exception->getMessage() . PHP_EOL . $parser->getHelpText());
                    }
            }
        };
    }

    protected function handle($command, $parser)
    {
        switch ($command) {
            case 'server:add':
                $port = $parser->getOperand('port');
                Manager::serverAdd(array(
                    'name' => $parser->getOption('name') . '_' . $port,
                    'port' => $port,
                    'password' => $parser->getOperand('password'),
                    'process_num' => $parser->getOption('process'),
                ));
                return 'Done' . PHP_EOL;
                break;
            case 'server:del':
                foreach ($parser->getOperand('names') as $name) {
                    Manager::serverDel($name);
                }
                return 'Done' . PHP_EOL;
                break;
            case 'server:list':
                return Manager::serverList();
                break;
            case 'server:detail':
                return Manager::serverList(true);
                break;
        }
    }

    public static function serverAdd(array $config)
    {
        Launcher::superaddModule('server', $config);
        self::$servers[$config['name']] = $config;
    }

    public static function serverDel($server_name)
    {
        try {
            Launcher::removeModule($server_name);
            unset(self::$servers[$server_name]);
        } catch (\Exception $e) {
        }
    }

    public static function serverList($detail = false)
    {
        $rtn = 'Total: ' . count(self::$servers) . PHP_EOL;

        foreach (self::$servers as $name => $config) {
            if ($detail) {
                $rtn .= "{$config['port']}: $name with password {$config['password']}, {$config['process_num']} process" . PHP_EOL;
            } else {
                $rtn .= "{$config['port']}: $name" . PHP_EOL;
            }
        }

        return $rtn;
    }

    protected function preBoot()
    {
        $commands = $this->getConfig('instance')->preBootCommands();
        if ($commands && is_array($commands)) {
            $parser = new ManagerCommandParser();

            foreach ($commands as $buffer) {
                try {
                    if ($command = $parser->parseCommand($buffer)) {
                        $this->handle($command, $parser);
                    }
                } catch (\Exception $exception) {
                    throw new ConfigException('Manager preBoot Failed: ' . $exception->getMessage());
                }
            }
        }
    }

    public function _denyCommand($command, $buffer = '')
    {
        return $this->getConfig('instance')->denyCommand($command, $buffer);

    }

    public function preBootCommands()
    {
        return array();
    }

    public function denyCommand($command, $full_command = '')
    {
        return false;
    }

}
