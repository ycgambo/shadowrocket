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

use ShadowRocket\Module\Configurable;
use ShadowRocket\Module\ConfigRequired;
use ShadowRocket\Module\LauncherModuleInterface;
use Workerman\Worker;

class Launcher
{
    private static $_config;

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
         * remove tailing _ and numbers
         *
         * change   server, server1, server_1, server_test
         * into     Server, Server,  Server,   ServerTest
         *
         * then attach namespace in front of it
         */
        $class = preg_replace('/(.+?)[_\\d]*$/', '$1', $module_name);
        $class = str_replace('_', ' ', $class);
        $class = str_replace(' ', '', ucwords($class));
        $class = '\\ShadowRocket\\Module\\' . $class;

        $order = self::getLaunchOrder($module_name);
        if (!isset(self::$_modules[$order])) {
            self::$_modules[$order] = array();
        }
        try {
            $module = new $class();
            $module->init();
            self::$_modules[$order][] = $module;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function launch(array $configs)
    {
        if (!extension_loaded('pcntl')) {
            throw new \Exception(
                'Require pcntl extension. See http://doc3.workerman.net/install/install.html' . PHP_EOL
            );
        }
        if (!extension_loaded('posix')) {
            throw new \Exception(
                'Require posix extension. See http://doc3.workerman.net/install/install.html' . PHP_EOL
            );
        }

        /* save config and create modules */
        self::$_config = $configs;
        foreach ($configs as $module_name => $config) {
            self::addModule($module_name, $config);
        }

        /* Check configurations of enabled config required modules */
        array_walk_recursive(self::$_modules, function ($module) {
            if (($module instanceof Configurable) &&
                ($module->getConfig('enabled') == false)) {
                return;
            }

            if ($module instanceof ConfigRequired) {
                if ($module->hasRequiredConfig() && ($missing_config = $module->getMissingConfig())) {
                    throw new \Exception("Missing config of {$module['name']} :"
                        . implode(', ', $missing_config));
                }
            }
        });

        /* Prepare these enabled modules by it's launch order */
        foreach (self::$_modules as $order => $modules) {
            foreach (self::$_modules[$order] as $module) {
                if (($module instanceof Configurable) &&
                    ($module->getConfig('enabled') == false)) {
                    continue;
                }

                if ($module instanceof LauncherModuleInterface) {
                    try {
                        $module->getReady();
                    } catch (\Exception $e) {
                        throw $e;
                    }

                    $module->setConfigItems(array('__is_ready' => true));
                }
            }
        }

        Worker::runAll();
    }

    public static function isModuleReady($module_name)
    {
        $module_name = strtolower($module_name);
        foreach (self::$_modules as $order => $modules) {
            foreach (self::$_modules[$order] as $module) {
                if (($module instanceof Configurable) &&
                    ($module->getConfig('name') == $module_name)) {
                    return $module->getConfig('__is_ready');
                }
            }
        }
        return false;
    }
}
