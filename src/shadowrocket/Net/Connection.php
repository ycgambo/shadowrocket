<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       Connection.php
 * @author     ycgambo
 * @update     4/26/18 10:09 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Net;

use ShadowRocket\Bin\Launcher;
use Monolog\Registry;
use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;

class Connection
{
    const STAGE_INIT = 0;
    const STAGE_ADDR = 1;
    const STAGE_UDP_ASSOC = 2;
    const STAGE_DNS = 3;
    const STAGE_CONNECTING = 4;
    const STAGE_STREAM = 5;
    const STAGE_DESTROYED = -1;

    const CMD_CONNECT = 1;
    const CMD_BIND = 2;
    const CMD_UDP_ASSOCIATE = 3;

    const ADDRTYPE_IPV4 = 1;
    const ADDRTYPE_IPV6 = 4;
    const ADDRTYPE_HOST = 3;

    const VERIFIED = 99;

    public static function bind($proxy, $remote)
    {
        $remote->opposite = $proxy;
        $proxy->opposite = $remote;

        // 流量控制，远程连接的发送缓冲区满，则停止读取shadowsocks客户端发来的数据
        $remote->onBufferFull = function ($remote) {
            $remote->opposite->pauseRecv();
        };
        // 流量控制，远程连接的发送缓冲区发送完毕后，则恢复读取shadowsocks客户端发来的数据
        $remote->onBufferDrain = function ($remote) {
            $remote->opposite->resumeRecv();
        };
        // 远程连接断开时，则断开shadowsocks客户端的连接
        $remote->onClose = function ($remote) {
            $remote->opposite->close();
            $remote->opposite = null;
        };
        // 远程连接发生错误时（一般是建立连接失败错误），关闭shadowsocks客户端的连接
        $remote->onError = function ($remote, $code, $msg) {
            echo "remote_connection error code:$code msg:$msg\n";
            $remote->close();
            if (isset($remote->opposite)) {
                $remote->opposite->close();
            }
        };

        // 流量控制，shadowsocks客户端的连接发送缓冲区满时，则停止读取远程服务端的数据
        $proxy->onBufferFull = function ($proxy) {
            $proxy->opposite->pauseRecv();
        };
        // 流量控制，当shadowsocks客户端的连接发送缓冲区发送完毕后，继续读取远程服务端的数据
        $proxy->onBufferDrain = function ($proxy) {
            $proxy->opposite->resumeRecv();
        };
        // 当shadowsocks客户端关闭连接时，关闭远程服务端的连接
        $proxy->onClose = function ($proxy) {
            $proxy->opposite->close();
            $proxy->opposite = null;
        };
        // 当shadowsocks客户端连接上有错误时，关闭远程服务端连接
        $proxy->onError = function ($proxy, $code, $msg) {
            echo "connection err code:$code msg:$msg\n";
            $proxy->close();
            if (isset($proxy->opposite)) {
                $proxy->opposite->close();
            }
        };
    }


    /**
     *
     * @param $buffer
     * @return array
     *
     * @see https://tools.ietf.org/html/rfc1928  #5. Addressing
     */
    public static function parseSocket5Request($buffer)
    {
        $addr_type = ord($buffer[0]);
        switch ($addr_type) {
            case Connection::ADDRTYPE_IPV4:
                $dst_addr = implode('.', array_map(function ($chr) {
                    return ord($chr);
                }, str_split(substr($buffer, 1, 4), 1)));
                $port_data = unpack('n', substr($buffer, 5, 2));
                $dst_port = $port_data[1];
                $header_len = 7;
                break;
            case Connection::ADDRTYPE_HOST:
                $addr_len = ord($buffer[1]);
                $dst_addr = substr($buffer, 2, $addr_len);
                $port_data = unpack('n', substr($buffer, 2 + $addr_len, 2));
                $dst_port = $port_data[1];
                $header_len = $addr_len + 4;
                break;
            case Connection::ADDRTYPE_IPV6:
                $dst_addr = implode('.', array_map(function ($chr) {
                    return ord($chr);
                }, str_split(substr($buffer, 1, 16), 1)));
                $port_data = unpack('n', substr($buffer, 17, 2));
                $dst_port = $port_data[1];
                $header_len = 19;
                break;
            default:
                return array();
        }

        return array(
            'addr_type' => $addr_type,
            'dst_addr' => $dst_addr,
            'dst_port' => $dst_port,
            'data' => substr($buffer, $header_len),
        );
    }
}