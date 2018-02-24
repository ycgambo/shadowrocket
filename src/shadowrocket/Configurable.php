<?php

namespace ShadowRocket;

abstract class Configurable {

    protected $_config = array();

    /**
     * Overwrite config with assigned `$config`
     *
     * @param array $config
     */
    public function setConfig(array $config = array()) {
        foreach ($config as $key => $value) {
            $this->_config[$key] = $value;
        }
    }

    /**
     * return specified config items, return all by default
     * @param array $config_names
     * @return array
     */
    public function getConfig(array $config_names = array()) {
        if ($config_names == array()) {
            return $this->_config;
        }

        $config_tmp = array();
        foreach ($config_names as $config_name) {
            if (isset($this->_config[$config_name])) {
                array_push($config_tmp, $this->_config[$config_name]);
            }
        }
        return $config_tmp;
    }
}