<?php

namespace ShadowRocket\Helper;

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
