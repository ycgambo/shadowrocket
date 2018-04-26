<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       Configurable.php
 * @author     ycgambo
 * @update     4/26/18 10:09 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Module\Base;

class Configurable
{
    protected $_config = array();

    public function resetConfig(array $config)
    {
        return $this->_config = $config;
    }

    public function setConfigItem($key, $value)
    {
        return $this->_config[$key] = $value;
    }

    public function setConfigItems(array $config_items)
    {
        foreach ($config_items as $key => $value) {
            $this->_config[$key] = $value;
        }
        return $this->_config;
    }

    public function getConfig($key = null)
    {
        if (empty($key)) {
            return $this->_config;
        }
        if (is_array($key)) {
            return array_intersect_key($this->_config, $key);
        }
        return isset($this->_config[$key]) ? $this->_config[$key] : null;
    }

    public function hasConfig($key)
    {
        return array_key_exists($key, $this->_config);
    }

    public function hasValidConfig($key)
    {
        return isset($this->_config[$key]);
    }

    public function combineConfig($key, array $config = array())
    {
        return empty($config)
            ? self::getConfig($key)
            : array_replace(self::getConfig($key), $config);
    }

    public function delConfig($key)
    {
        if (array_key_exists($key, $this->_config)) {
            unset($this->_config[$key]);
        }
        return $this->_config;
    }
}