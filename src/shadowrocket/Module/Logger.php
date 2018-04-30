<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       Logger.php
 * @author     ycgambo
 * @update     4/26/18 10:09 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Module;

use Monolog\Handler\HandlerInterface;
use Monolog\Registry;
use ShadowRocket\Module\Base\ConfigRequired;
use ShadowRocket\Module\Base\LauncherModuleInterface;

class Logger extends ConfigRequired implements LauncherModuleInterface
{
    public function init()
    {
        $this->declareRequiredConfig(array(
            'logger_name',
            'handlers',
        ));
    }

    public function getReady()
    {
        $logger = new \Monolog\Logger($this->getConfig('logger_name'));

        $handlers = $this->getConfig('handlers');
        foreach ($handlers as $handler) {
            if (!($handler instanceof HandlerInterface)) {
                throw new \Exception(
                    'Logger handlers should be an instance array of Monolog\Handler\HandlerInterface.'
                );
            }
        }
        $logger->setHandlers($handlers);

        Registry::addLogger($logger);
    }

    public function __call($name, $arguments)
    {
        return Registry::getInstance($this->getConfig('logger_name'))->$name($arguments);
    }
}
