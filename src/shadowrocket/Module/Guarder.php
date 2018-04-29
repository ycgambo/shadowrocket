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
use ShadowRocket\Module\Base\LauncherModuleInterface;

class Guarder extends ConfigRequired implements LauncherModuleInterface
{
    // todo: replace counter with database search
    /**
     * BE AWARE, each process has it's own counter, it's not share
     */
    static $counter = array();

    public function initConfig(array $config = array())
    {
        $this->resetConfig($config);
        $this->declareRequiredConfig(array());
    }

    public function getReady()
    {

    }

    public static function reject($request, $port)
    {
        if (isset(self::$counter[$port])) {
            self::$counter[$port] = self::$counter[$port] + 1;
        } else {
            self::$counter[$port] = 0;
        }

        return false;

        if (self::$counter[$port] < 5) {
            return true;
        } else {
            // return false will close worker on this port, we have to reset counter for new process
            self::$counter[$port] = 0;
            return false;
        }
    }
}