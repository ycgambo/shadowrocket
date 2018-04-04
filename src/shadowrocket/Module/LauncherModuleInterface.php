<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       LauncherModuleInterface.php
 * @author     ycgambo
 * @create     4/4/18 9:01 AM
 * @update     4/4/18 9:01 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Module;

interface LauncherModuleInterface {
    public function init(array $config);
    public function getReady();
}