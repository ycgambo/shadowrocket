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


use ShadowRocket\Module\Base\ConfigRequired;
use ShadowRocket\Module\Base\GuarderInterface;
use ShadowRocket\Module\Base\LauncherModuleInterface;

class Guarder extends ConfigRequired implements LauncherModuleInterface, GuarderInterface
{
    // todo: replace counter with database search
    /**
     * BE AWARE, each process has it's own counter, it's not share
     */
    static $counter = array();

    public function initByConfig(array $config = array())
    {
        $this->resetConfig($config);
        $this->declareRequiredConfig(array(
            'instance' => new self(),
        ));
    }

    public function getReady()
    {
        $instance = $this->getConfig('instance');

        if (!$instance instanceof GuarderInterface) {
            throw new \Exception('A Guarder should implements ShadowRocket\Module\Base\GuarderInterface');
        }
    }

    /**
     * This stops a service
     * @param $request
     * @param $port
     * @return boolean
     */
    public function _deny($request, $port)
    {
        return $this->getConfig('instance')->deny($request, $port);
    }

    /**
     * This blocks a request
     * @param $request
     * @param $port
     * @return boolean
     */
    public function _block($request, $port)
    {
        return $this->getConfig('instance')->block($request, $port);
    }

    public function deny($request, $port)
    {
        return false;

        if (isset(self::$counter[$port])) {
            self::$counter[$port] = self::$counter[$port] + 1;
        } else {
            self::$counter[$port] = 0;
        }

        if (self::$counter[$port] < 5) {
            return true;
        } else {
            // return false will close worker on this port, we have to reset counter for new process
            self::$counter[$port] = 0;
            return false;
        }
    }

    public function block($request, $prot)
    {
        return false;
    }
}