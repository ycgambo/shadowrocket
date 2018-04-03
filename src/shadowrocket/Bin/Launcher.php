<?php

namespace ShadowRocket\Bin;

use ShadowRocket\Helper\ConfigRequired;

use ShadowRocket\Helper\LauncherModuleInterface;
use Workerman\Worker;

class Launcher extends ConfigRequired
{
    private static $_modules = array();
    private static $_launch_echelon = array(
        /* 1st */
        array('logger'),
        /* 2nd */
        array(),
        /* 3rd */
        array('server', 'local'),
    );

    public static function initialize(array $config = array())
    {
        self::setConfig(array(
            'server' => array(
                'port' => '8388',
                'password' => 'mypass',
                'encryption' => 'aes-256-cfb',
                'process_num' => 12,
            ),
            'local' => array(
                'server' => '127.0.0.1',
                'port' => '8388',
                'password' => 'mypass',
                'encryption' => 'aes-256-cfb',
                'local_port' => '1086',
                'process_num' => 12,
            ),
        ));
        self::setConfigItems($config);
    }

    protected static function getLaunchOrder($key)
    {
        foreach (self::$_launch_echelon as $order => $echelon) {
            if (in_array($key, $echelon)) {
                return $order;
            }
        }
        return count(self::$_launch_echelon); // launch last
    }

    protected static function addModule($module_name, array $config = array())
    {
        $module_name = strtolower($module_name);
        $config = self::combineConfig($module_name, $config);

        if (!isset($config['name'])) {
            $config['name'] = $module_name;
        }
        if (!isset($config['enabled'])) {
            $config['enabled'] = true;
        }

        $order = self::getLaunchOrder($module_name);
        if (!is_array(self::$_modules[$order])) {
            self::$_modules[$order] = array();
        }


        $module_name = str_replace('_', '', ucwords($module_name, '_'));
        try {
            self::$_modules[$order][] = new $module_name($config);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function launchAll()
    {
        array_walk_recursive(self::$_modules, function ($module, $key) {
            if ($module['enabled'] == false) {
                return;
            }

            if ($module instanceof ConfigRequired) {
                if ($module::hasRequiredConfig() && ($missing_config = $module::getMissingConfig())) {
                    throw new \Exception("Missing config of {$module['name']} :"
                        . implode(', ', $missing_config));
                }
            }
        });

        foreach (self::$_modules as $order => $modules) {
            foreach (self::$_modules[$order] as $module) {
                if ($module['enabled'] == false) {
                    continue;
                }

                if ($module instanceof LauncherModuleInterface) {
                    try {
                        $module->getReady();
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
            }
        }

        Worker::runAll();
    }
}
