<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       GuarderInterface.php
 * @author     ycgambo
 * @update     4/30/18 8:20 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Module\Base;

interface GuarderInterface
{
    public function deny($request, $port);
    public function block($request, $port);
}