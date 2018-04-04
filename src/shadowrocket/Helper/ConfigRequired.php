<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       ConfigRequired.php
 * @author     ycgambo
 * @create     4/4/18 9:01 AM
 * @update     4/4/18 9:01 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Helper;

class ConfigRequired extends Configurable
{
    public static function setRequiredConfig($required)
    {
        parent::setConfigItems(array('__required_config' => $required));
    }

    public static function getRequiredConfig()
    {
        return parent::getConfig('__required_config');
    }

    public static function hasRequiredConfig()
    {
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