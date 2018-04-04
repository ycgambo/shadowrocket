<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       Logger.php
 * @author     ycgambo
 * @create     4/4/18 9:01 AM
 * @update     4/4/18 9:01 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Module;

use MonoLog\Registry;
use Monolog\Handler\HandlerInterface;

class Logger extends ConfigRequired implements LauncherModuleInterface
{
    public function __construct($config)
    {
        parent::setConfig($config);
        parent::setRequiredConfig(array(
            'name',
            'handlers',
        ));
    }

    public function getReady()
    {
        $logger = new \Monolog\Logger(self::getConfig('name'));

        $handlers = self::getConfig('handlers');
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
}