<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       ManagerInterface.php
 * @author     ycgambo
 * @update     5/2/18 9:32 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Module\Base;

interface ManagerInterface
{

    public function preBootCommands();

    public function denyCommand($command, $full_command = '');
}