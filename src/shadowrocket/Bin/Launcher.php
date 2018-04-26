<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       Launcher.php
 * @author     ycgambo
 * @update     4/26/18 10:09 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Bin;

use ShadowRocket\Module\Base\Configurable;
use ShadowRocket\Module\Base\ConfigRequired;
use ShadowRocket\Module\Base\LauncherModuleInterface;
use Workerman\Worker;

class Launcher
{
    private static $_config;

    /**
     * classes to be launched
     */
    private static $_echeloned_modules = array();
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
        if (!isset($config['enable'])) {
            $config['enable'] = true;
        }

        // remove tailing _ and numbers
        $base_module_name = preg_replace('/(.+?)[_\\d]*$/', '$1', $module_name);

        /**
         *
         * change   server, server1, server_1, server_test
         * into     Server, Server,  Server,   ServerTest
         *
         * then attach namespace in front of it
         */
        $class = str_replace('_', ' ', $base_module_name);
        $class = str_replace(' ', '', ucwords($class));
        $class = '\\ShadowRocket\\Module\\' . $class;

        try {
            $module = new $class();
            if ($module instanceof LauncherModuleInterface) {
                $module->init($config);
            }
            if (!($module instanceof Configurable)) {
                foreach ($config as $name => $value) {
                    $module->$name = $value;
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }

        $order = self::getLaunchOrder($base_module_name);
        if (!isset(self::$_echeloned_modules[$order])) {
            self::$_echeloned_modules[$order] = array();
        }
        self::$_echeloned_modules[$order][] = $module;
        self::$_modules[$config['name']] = $module;
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

        /* Check required configurations on enabled modules */
        array_walk_recursive(self::$_echeloned_modules, function ($module) {
            if ((($module instanceof Configurable) && ($module->getConfig('enable') === false)) ||
                (property_exists($module, 'enable') && ($module->enable == false))) {
                return;
            }

            if ($module instanceof ConfigRequired) {
                if ($module->declaredRequiredConfig() && ($missing_config = $module->getMissingConfig())) {
                    throw new \Exception("Missing config of {$module->getConfig('name')} :"
                        . implode(', ', $missing_config));
                }
            }
        });

        /* Prepare enabled modules by it's launch order */
        foreach (self::$_echeloned_modules as $order => $modules) {
            foreach (self::$_echeloned_modules[$order] as $module) {
                if ((($module instanceof Configurable) && ($module->getConfig('enable') === false)) ||
                    (property_exists($module, 'enable') && ($module->enable == false))) {
                    continue;
                }

                if ($module instanceof LauncherModuleInterface) {
                    try {
                        $module->getReady();
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }

                if ($module instanceof Configurable) {
                    $module->setConfigItems(array('__is_ready' => true));
                } else {
                    $module->__is_ready = true;
                }
            }
        }

        Worker::runAll();
    }

    public static function isModuleReady($module_name)
    {
        $module_name = strtolower($module_name);
        $module = self::$_modules[$module_name];
        if ($module instanceof Configurable) {
            return $module->getConfig('__is_ready');
        } else if (property_exists($module, '__is_ready')) {
            return $module->__is_ready;
        }
        return false;
    }

    public static function getModule($module_name)
    {
        $module_name = strtolower($module_name);
        return self::$_modules[$module_name];
    }
}
