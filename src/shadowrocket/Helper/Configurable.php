<?php

namespace ShadowRocket\Helper;

class Configurable
{
    protected static $_config = array();

    public static function setConfig(array $config) {
        return self::$_config = $config;
    }

    public static function setConfigItems(array $config_items)
    {
        foreach ($config_items as $key => $value) {
            self::$_config[$key] = $value;
        }
        return self::$_config;
    }

    public static function getConfig($key = null)
    {
        if (empty($key)) {
            return self::$_config;
        }
        if (is_array($key)) {
            return array_intersect_key(self::$_config, $key);
        }
        return isset(self::$_config[$key]) ? self::$_config[$key] : null;
    }

    public static function hasConfig($key) {
        return array_key_exists($key, self::$_config);
    }

    public static function hasValidConfig($key) {
        return isset(self::$_config[$key]);
    }

    public static function combineConfig($key, array $config = array())
    {
        return empty($config)
            ? self::getConfig($key)
            : array_replace(self::getConfig($key), $config);
    }

    public static function delConfig($key)
    {
        if (array_key_exists($key, self::$_config)) {
            unset(self::$_config[$key]);
        }
        return self::$_config;
    }
}