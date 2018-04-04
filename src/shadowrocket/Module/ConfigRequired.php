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

namespace ShadowRocket\Module;

class ConfigRequired extends Configurable
{
    public function setRequiredConfig($required)
    {
        $this->setConfigItems(array('__required_config' => $required));
    }

    public function getRequiredConfig()
    {
        return $this->getConfig('__required_config');
    }

    public function hasRequiredConfig()
    {
        return $this->hasValidConfig('__required_config');
    }

    public function getMissingConfig()
    {
        $rtn = array();
        foreach ($this->getRequiredConfig() as $required) {
            if (!$this->hasConfig($required)) {
                $rtn[] = $required;
            }
        }
        return $rtn;
    }
}