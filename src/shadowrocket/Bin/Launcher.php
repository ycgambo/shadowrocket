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
use ShadowRocket\Module\Base\ManageableInterface;
use Workerman\Worker;

class Launcher
{
    private static $_config;

    /**
     * classes to be launched
     */
    private static $_modules = array();
    private static $_echeloned_modules = array();

    /**
     * defined launch sequence of $_modules
     */
    private static $_launch_echelon = array(
        /* 1st */
        array('logger'),
        /* 2nd */
        array('guarder'),
        /* 3rd */
        array('server', 'local', 'manager'),
    );

    /**
     * format: module_name => order
     */
    private static $_custom_order = array();
    /**
     * call setLaunchOrder() will change this to true.
     */
    private static $_is_custom_order = false;

    /**
     * We can launch all the Modules by passing a array which contains the config of each module to it.
     * In this array, each item should be a key-value pair and the key is the module name and the value is the config.
     *
     * @param array $configs
     * @throws \Exception
     */
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

        $configs = array_merge($configs, self::builtinModules());

        /* save config and create modules */
        foreach ($configs as $module_name => $config) {
            self::addModule($module_name, $config);
            self::$_config[$config['name']] = $config;
        }

        /* Check required configurations on enabled modules */
        array_walk(self::$_modules, function ($module) {
            if ((($module instanceof Configurable) && ($module->getConfig('enable') == false)) ||
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
                if ((($module instanceof Configurable) && ($module->getConfig('enable') == false)) ||
                    (property_exists($module, 'enable') && ($module->enable == false))) {
                    continue;
                }
                self::getModuleReady($module);
            }
        }

        Worker::runAll();
    }

    /**
     * If you want to add module after launch, Use this.
     *
     * @param $module_name
     * @param array $config
     * @throws \Exception
     */
    public static function superaddModule($module_name, array $config)
    {
        /* append config and create modules */
        $module = self::addModule($module_name, $config);
        self::$_config[$config['name']] = $config;

        /* Check required configurations */
        if ((($module instanceof Configurable) && ($module->getConfig('enable') == false)) ||
            (property_exists($module, 'enable') && ($module->enable == false))) {
            return;
        }

        if ($module instanceof ConfigRequired) {
            if ($module->declaredRequiredConfig() && ($missing_config = $module->getMissingConfig())) {
                throw new \Exception("Missing config of {$module->getConfig('name')} :"
                    . implode(', ', $missing_config));
            }
        }

        /* Get module ready */
        if (!(
            (($module instanceof Configurable) && ($module->getConfig('enable') == false)) ||
            (property_exists($module, 'enable') && ($module->enable == false))
        )) {
            self::getModuleReady($module, true);
        }
    }

    /**
     * @param $module_name
     * @throws \Exception
     */
    public static function removeModule($module_name)
    {
        $module_name = strtolower($module_name);
       if (!self::isModuleReady($module_name)) {
           throw new \Exception($module_name . ' not ready yet');
       }

       $module = self::getModule($module_name);
       if ($module instanceof ManageableInterface) {

           $module->stop();

           unset($module);

           /**
            * This will also set self::$_echeloned_modules[$module_name] to null
            *
            * @see Launcher::addModule()
            */
           self::$_modules[$module_name] = null;

       } else {
           throw new \Exception($module_name . ' is not manageable');
       }
    }

    /**
     * these are builtin module configurations
     *
     * @return array
     * @throws \Exception
     */
    private static function builtinModules()
    {
        return array(
            'logger__' => array(
                'enable' => true,
                'logger_name' => 'shadowrocket_builtin_logger',
                'handlers' => array(
                    new \Monolog\Handler\StreamHandler(__DIR__ . '/shadowrocket.log', \Monolog\Logger::DEBUG),
                ),
            )
        );
    }

    /**
     * Create module and push it into an array in self::$modules[$order]
     * $order is the launch order calculated by self::getLaunchOrder($module_name)
     *
     * @param string $module_name
     * @param array $config
     * @throws \Exception
     * @return LauncherModuleInterface
     */
    protected static function addModule($module_name, array $config = array())
    {
        $module_name = strtolower($module_name);

        self::setCommonConfig($module_name, $config);

        if (self::getModule($config['name'])) {
            throw new \Exception('module name ' . $config['name']. ' already in use');
        }

        // use module_name to create a module
        try {
            $module = self::createModule($module_name, $config);
        } catch (\Exception $e) {
            throw $e;
        }

        // use config['name'] to trace this module
        $order = self::getLaunchOrder($config['name']);
        if (!isset(self::$_echeloned_modules[$order])) {
            self::$_echeloned_modules[$order] = array();
        }
        self::$_echeloned_modules[$order][] = &$module;
        self::$_modules[$config['name']] = &$module;

        return $module;
    }

    /**
     * Modules may have common config, set the default value here.
     *
     * @param $module_name
     * @param $config
     */
    protected static function setCommonConfig($module_name, & $config)
    {
        if (!isset($config['name'])) {
            $config['name'] = $module_name;
        }
        if (!isset($config['enable'])) {
            $config['enable'] = true;
        }
    }

    /**
     * Create the module class and call initConfig on it.
     *
     * @param $module_name
     * @param $config
     * @return mixed the module class
     * @throws \Exception
     */
    protected static function createModule($module_name, $config)
    {
        // remove tailing underline and numbers
        $base_module_name = preg_replace('/(.+?)[_\\d]*$/', '$1', $module_name);

        /**
         * change   server, server_test
         * into     Server, ServerTest
         *
         * then attach namespace in front of it
         */
        $class = str_replace('_', ' ', $base_module_name);
        $class = str_replace(' ', '', ucwords($class));
        $class = '\\ShadowRocket\\Module\\' . $class;

        try {
            $module = new $class();

            if ($module instanceof Configurable) {
                $module->resetConfig($config);
            } else {
                foreach ($config as $name => $value) {
                    $module->$name = $value;
                }
            }

            if ($module instanceof LauncherModuleInterface) {
                $module->init();
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $module;
    }

    /**
     * Use custom launch order instead of the default order echelon.
     *
     * @param array $order_map
     */
    public static function setLaunchOrder(array $order_map)
    {
        foreach ($order_map as $module_name => $order) {
            self::$_custom_order[strtolower($module_name)] = $order;
        }
        self::$_is_custom_order = true;
    }

    /**
     * Fake clear.
     */
    public static function clearLaunchOrder()
    {
        self::$_is_custom_order = false;
    }

    /**
     * Return the last order if no proper order found
     *
     * @param string $module_name the name of module
     * @return int
     */
    protected static function getLaunchOrder($module_name)
    {
        $module_name = strtolower($module_name);

        if (self::$_is_custom_order) {
            return isset(self::$_custom_order[$module_name])
                ? self::$_custom_order[$module_name]
                : count(self::$_custom_order);
        }

        // remove tailing underline and numbers
        $base_module_name = preg_replace('/(.+?)[_\\d]*$/', '$1', $module_name);
        foreach (self::$_launch_echelon as $order => $echelon) {
            if (in_array($base_module_name, $echelon)) {
                return intval($order);
            }
        }
        return count(self::$_launch_echelon);
    }

    /**
     * After config initialization, we can get module ready properly
     *
     * @param $module
     * @param bool $superadd
     * @throws \Exception
     */
    protected static function getModuleReady($module, $superadd = false)
    {
        try {
            if ($superadd && ($module instanceof ManageableInterface)) {
                $module->superadd();
            } else if ($module instanceof LauncherModuleInterface) {
                $module->getReady();
            }
        } catch (\Exception $e) {
            throw $e;
        }

        if ($module instanceof Configurable) {
            $module->setConfigItem('__is_ready', true);
        } else {
            $module->__is_ready = true;
        }
    }

    public static function isModuleReady($module_name)
    {
        if ($module = self::getModule($module_name)) {
            if ($module instanceof Configurable) {
                return $module->getConfig('__is_ready');
            } else if (property_exists($module, '__is_ready')) {
                return $module->__is_ready;
            }
        }
        return false;
    }

    public static function getModule($module_name)
    {
        $module_name = strtolower($module_name);
        return isset(self::$_modules[$module_name]) ? self::$_modules[$module_name] : null;
    }

    public static function getModuleIfReady($module_name)
    {
        return self::isModuleReady($module_name) ? self::getModule($module_name) : null;
    }
}
