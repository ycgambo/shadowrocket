<?php

namespace ShadowRocket\Helper;

abstract class Configurable extends Config
{
    public static function setRequiredConfig($required)
    {
        parent::setConfigItems(array('__required_config' => $required));
    }

    public static function getRequiredConfig()
    {
        return parent::getConfig('__required_config');
    }

    public static function hasRequiredConfig() {
        return parent::hasValidConfig('__required_config');
    }

    public static function getMissingConfig()
    {
        $rtn = array();
        foreach (self::getRequiredConfig() as $required) {
            if (!parent::hasConfig($required)) {
                $rtn[] = $required;
            }
        }
        return $rtn;
    }
}