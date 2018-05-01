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
                ->setShortDescription('Add server on one or more port.')
                ->setDescription('Create server named as prefix_port on each port. ' )
                ->addOperands(array(
                    Operand::create('password', Operand::REQUIRED),
                    Operand::create('ports', Operand::MULTIPLE + Operand::REQUIRED),
                ))
                ->addOptions(array(
                    Option::create('n', 'name', GetOpt::OPTIONAL_ARGUMENT)
                        ->setDescription('the name prefix of server. The default prefix is `server`.')
                        ->setDefaultValue('server'),
                    Option::create(null, 'process', GetOpt::OPTIONAL_ARGUMENT)
                        ->setDescription('process number.')
                        ->setDefaultValue(4),
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