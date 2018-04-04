<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       Launcher.php
 * @author     ycgambo
 * @create     4/4/18 9:01 AM
 * @update     4/4/18 9:01 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Bin;

use ShadowRocket\Helper\Configurable;
use ShadowRocket\Helper\ConfigRequired;
use ShadowRocket\Helper\LauncherModuleInterface;
use Workerman\Worker;

class Launcher extends Configurable
{
    /**
     * classes to be launched
     */
    private static $_modules = array();

    /**
     * defined launch sequence of $_modules
     */
    private static $_launch_echelon = array(
        /* 1st */
        array('logger'),
        /* 2nd */
        array(),
        /* 3rd */
        array('server', 'local'),
    );

    /**
     * @param string $module_name the name of module
     * @return int
     */
    protected static function getLaunchOrder($module_name)
    {
        foreach (self::$_launch_echelon as $order => $echelon) {
            if (in_array($module_name, $echelon)) {
                return intval($order);
            }
        }
        return count(self::$_launch_echelon);
    }

    /**
     * Create module and push it into an array in self::$modules[$order]
     * $order is the launch order calculated by self::getLaunchOrder($module_name)
     *
     * @param string $module_name
     * @param array $config
     * @throws \Exception
     */
    protected static function addModule($module_name, array $config = array())
    {
        $module_name = strtolower($module_name);
        if (!isset($config['name'])) {
            $config['name'] = $module_name;
        }
        if (!isset($config['enabled'])) {
            $config['enabled'] = true;
        }

        /**
         * change   server, server1, server_1, server_test
         * into     Server, Server,  Server,   ServerTest
         */
        $class_name = preg_replace('/(.+?)_?\\d+$/', '$1', $module_name);
        $class_name = str_replace('_', '', ucwords($class_name, '_'));

        $order = self::getLaunchOrder($module_name);
        if (!is_array(self::$_modules[$order])) {
            self::$_modules[$order] = array();
        }
        try {
            self::$_modules[$order][] = new $class_name($config);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function launch(array $configs)
    {
        foreach ($configs as $module_name => $config) {
            self::addModule($module_name, $config);
        }

        /* Check configurations of enabled config required modules */
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

        /* Prepare these enabled modules by it's launch order */
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
