<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       GetOpt.php
 * @author     ycgambo
 * @update     4/30/18 7:02 PM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Module\Helper;

use GetOpt\GetOpt;
use GetOpt\Command;
use GetOpt\Operand;
use GetOpt\Option;

class ManagerCommandParser
{
    private $_getopt;

    public function __construct()
    {
        /**
         * @see http://getopt-php.github.io/getopt-php/
         */
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

        $this->_getopt = $getopt;
    }

    public function parseCommand($buffer)
    {
        $this->_getopt->process($buffer);
        if ($command = $this->_getopt->getCommand()) {
            return $command->getName();
        } else {
            return '';
        }
    }

    public function __call($name, $arguments)
    {
        return $this->_getopt->$name($arguments);
    }
}