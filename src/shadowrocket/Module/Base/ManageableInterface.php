<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       ManageableInterface.php
 * @author     ycgambo
 * @update     4/30/18 8:22 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Module\Base;


interface ManageableInterface
{
    public function superadd();

    /**
     * Make sure all resources are released here
     * After this, the implemented class will be destroyed in Launcher, untraceable.
     */
    public function stop();
}