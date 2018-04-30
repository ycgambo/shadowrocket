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
use ShadowRocket\Net\Connection;
use Workerman\Worker;
use GetOpt\GetOpt;
use GetOpt\Command;
use GetOpt\Operand;
use GetOpt\Option;

class Manager extends ConfigRequired implements LauncherModuleInterface
{
    private static $_getopt;

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
            switch ($client->stage) {
                case Connection::STAGE_INIT:
                    if ($buffer == $config['token']) {
                        $client->stage = Connection::VERIFIED;
                        $client->send(Manager::getOpt()->getHelpText());
                    }
                    break;
                case Connection::VERIFIED:
                    switch (Manager::parseCommand($buffer)) {
                        case 'server:add':
                            foreach (Manager::getOpt()->getOperand('port') as $port) {
                                Launcher::superaddModule('server', array(
                                    'name' => (Manager::getOpt()->getOption('name') ?: 'server') . '_' . $port,
                                    'port' => $port,
                                    'password' => Manager::getOpt()->getOption('password'),
                                    'process_num' => Manager::getOpt()->getOption('process') ?: 4,
                                ));
                            }
                            break;
                        default:
                            $client->send(Manager::getOpt()->getHelpText());
                    }
                // todo: cmd parser
                // this is how to superadd module
//                    Launcher::superaddModule('server', array(
//                        'name' => 'server_test',
//                        'port' => 8381,
//                        'password' => 'mypass',
//                        'encryption' => 'aes-256-cfb',
//                        'process_num' => 4,
//                    ));
//                    Launcher::superaddModule('server', array(
//                        'name' => 'server_test2',
//                        'port' => 8382,
//                        'password' => 'mypass',
//                        'encryption' => 'aes-256-cfb',
//                        'process_num' => 4,
//                    ));
            }
        };
    }

    protected static function parseCommand($buffer)
    {
        self::getOpt()->process($buffer);

        if ($command = self::getOpt()->getCommand()) {
            return $command->getName();
        } else {
            return '';
        }
    }

    /**
     * @see http://getopt-php.github.io/getopt-php/
     * @return GetOpt
     */
    protected static function getOpt()
    {
        if (!(self::$_getopt instanceof GetOpt)) {
            $getopt = new GetOpt();
            $getopt->setHelp(new CustomGetOptHelp());
            $getopt->addCommands(array(
                Command::create('server:add', '')
                    ->setShortDescription('Add server on one or more port')
                    ->setDescription('Create server named as prefix_port on each port. 
                    The default prefix is `server`, you can change it with -n or --name option.')
                    ->addOperand(
                        Operand::create('port', Operand::MULTIPLE + Operand::REQUIRED)
                    )
                    ->addOptions(array(
                        Option::create('n', 'name', GetOpt::OPTIONAL_ARGUMENT)
                            ->setDescription('the name prefix of server. default: server'),
                        Option::create('p', 'password', GetOpt::REQUIRED_ARGUMENT)
                            ->setDescription('the password to use'),
                        Option::create(null, 'process', GetOpt::OPTIONAL_ARGUMENT)
                            ->setDescription('process number. default: 4'),
                    )),
                Command::create('server:del', ''),
            ));

            self::$_getopt = $getopt;
        }

        return self::$_getopt;
    }

}