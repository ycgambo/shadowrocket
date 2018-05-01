<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       GuarderInterface.php
 * @author     ycgambo
 * @update     4/30/18 8:34 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Module\Base;

interface GuarderInterface
{
    /**
     * If false returned, service will be stopped, no more port listening.
     *
     * @param $request array
     * @param $port
     * @return mixed
     */
    public function deny($request, $port);

    /**
     * If false returned, connection request will be refused.
     *
     * @param $request array
     * @param $port
     * @return mixed
     */
    public function block($request, $port);

    /**
     * If false returned, connection between client and remote will be closed.
     * @param $data string  the data come in and go out this server
     * @param $port
     * @return mixed
     */
    public function inspectFailed($data, $port);
}