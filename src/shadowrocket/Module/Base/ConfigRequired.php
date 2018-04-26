<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       ConfigRequired.php
 * @author     ycgambo
 * @update     4/26/18 10:09 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Module\Base;

class ConfigRequired extends Configurable
{
    public function declareRequiredConfig($required)
    {
        $this->setConfigItems(array('__required_config' => $required));
    }

    public function getRequiredConfig()
    {
        return $this->getConfig('__required_config');
    }

    public function declaredRequiredConfig()
    {
        return $this->hasValidConfig('__required_config');
    }

    public function getMissingConfig()
    {
        $rtn = array();
        $defaults = array();
        foreach ($this->getRequiredConfig() as $key => $value) {
            if (is_numeric($key)) {
                // $id => $required
                if (!$this->hasConfig($value)) {
                    $rtn[] = $value;
                }
            } else {
                // $required => $default
                $defaults[$key] = $value;
            }
        }

        // check and set default config
        foreach ($defaults as $required => $default) {
            if (!$this->hasConfig($required)) {
                $this->setConfigItems(array($required => $default));
            }
        }

        return $rtn;
    }
}