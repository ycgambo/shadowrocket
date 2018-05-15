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
                ->setShortDescription('Add server on a port.')
                ->setDescription('Create server named as prefix_port. ')
                ->addOperands(array(
                    Operand::create('password', Operand::REQUIRED),
                    Operand::create('port', Operand::REQUIRED),
                ))
                ->addOptions(array(
                    Option::create('n', 'name', GetOpt::OPTIONAL_ARGUMENT)
                        ->setDescription('the name prefix of server. The default prefix is `server`.')
                        ->setDefaultValue('server'),
                    Option::create(null, 'process', GetOpt::OPTIONAL_ARGUMENT)
                        ->setDescription('process number.')
                        ->setDefaultValue(4),
                )),
            Command::create('server:del', '')
                ->setShortDescription('Delete added server according to their name.')
                ->addOperands(array(
                    Operand::create('names', Operand::MULTIPLE + Operand::REQUIRED),
                )),
            Command::create('server:list', '')
                ->setShortDescription('List added server.'),
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

//    public function getOperand($index)
//    {
//        return $this->_getopt->getOperand($index);
//    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(array(&$this->_getopt,$name),$arguments);
    }
}