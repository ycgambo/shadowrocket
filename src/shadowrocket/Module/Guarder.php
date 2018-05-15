<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       Guarder.php
 * @author     ycgambo
 * @update     4/29/18 9:14 PM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Module;


use ShadowRocket\Exception\ConfigException;
use ShadowRocket\Module\Base\ConfigRequired;
use ShadowRocket\Module\Base\GuarderInterface;
use ShadowRocket\Module\Base\LauncherModuleInterface;

class Guarder extends ConfigRequired implements LauncherModuleInterface, GuarderInterface
{
    public function init()
    {
        $this->declareRequiredConfig(array(
            'instance' => new self(),
        ));
    }

    public function getReady()
    {
        $instance = $this->getConfig('instance');

        if (!$instance instanceof GuarderInterface) {
            throw new ConfigException('A Guarder should implements ShadowRocket\Module\Base\GuarderInterface');
        }
    }

    public function _deny($request, $port)
    {
        return $this->getConfig('instance')->deny($request, $port);
    }

    public function _block($request, $port)
    {
        return $this->getConfig('instance')->block($request, $port);
    }

    public function _inspectFailed($data, $port)
    {
        return $this->getConfig('instance')->inspectFailed($data, $port);
    }

    public function deny($request, $port)
    {
        return false;
    }

    public function block($request, $port)
    {
        return false;
    }

    public function inspectFailed($data, $port)
    {
        return false;
    }
}