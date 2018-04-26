<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       LauncherModuleInterface.php
 * @author     ycgambo
 * @update     4/26/18 10:09 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Module\Base;

interface LauncherModuleInterface {
    public function init(array $config);
    public function getReady();
}