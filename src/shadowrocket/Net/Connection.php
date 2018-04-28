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

    public static function createLocalWorker(array $config, $protocol)
    {
        $worker = new Worker($protocol . '://0.0.0.0:' . $config['local_port']);
        $worker->count = $config['process_num'];
        $worker->name = 'shadowsocks-local';

        // when applications connect this local server
        $worker->onConnect = function ($connection) use ($config) {
            $connection->stage = Connection::STAGE_INIT;
            $connection->cipher = new Encryptor($config['password'], $config['encryption']);
        };

        // message from applications to this local server
        $worker->onMessage = function ($connection, $buffer) use ($config, $protocol) {
            switch ($connection->stage) {
                case Connection::STAGE_INIT:
                    // see: https://www.ietf.org/rfc/rfc1928 #6.Replies
                    $connection->send("\x05\x00");
                    $connection->stage = Connection::STAGE_ADDR;
                    break;

                case Connection::STAGE_ADDR:
                    if (empty($buffer)) {
                        $connection->close();
                        return;
                    }
                    // see: https://www.ietf.org/rfc/rfc1928 #4.Requests
                    switch ($cmd = ord($buffer[1])) {
                        case Connection::CMD_CONNECT:
                        case Connection::CMD_UDP_ASSOCIATE:
                            $connection->stage = Connection::STAGE_CONNECTING;
                            $connection->send(
                                "\x05\x00\x00\x01\x00\x00\x00\x00" . pack('n', $config['local_port'])
                            );

                            // build tunnel to shadowsocks server
                            $address = "{$protocol}://{$config['server']}:{$config['port']}";
                            $remote_connection = new AsyncTcpConnection($address);

                            $remote_connection->opposite = $connection;
                            $connection->opposite = $remote_connection;

                            // 流量控制，远程连接的发送缓冲区满，则停止读取shadowsocks客户端发来的数据
                            // 避免由于读取速度大于发送速导致发送缓冲区爆掉
                            $remote_connection->onBufferFull = function ($remote_connection) {
                                $remote_connection->opposite->pauseRecv();
                            };
                            // 流量控制，shadowsocks客户端的连接发送缓冲区满时，则停止读取远程服务端的数据
                            // 避免由于读取速度大于发送速导致发送缓冲区爆掉
                            $connection->onBufferFull = function ($connection) {
                                $connection->opposite->pauseRecv();
                            };

                            // 流量控制，远程连接的发送缓冲区发送完毕后，则恢复读取shadowsocks客户端发来的数据
                            $remote_connection->onBufferDrain = function ($remote_connection) {
                                $remote_connection->opposite->resumeRecv();
                            };
                            // 流量控制，当shadowsocks客户端的连接发送缓冲区发送完毕后，继续读取远程服务端的数据
                            $connection->onBufferDrain = function ($connection) {
                                $connection->opposite->resumeRecv();
                            };

                            // 远程连接断开时，则断开shadowsocks客户端的连接
                            $remote_connection->onClose = function ($remote_connection) {
                                $remote_connection->opposite->close();
                                $remote_connection->opposite = null;
                            };
                            // 当shadowsocks客户端关闭连接时，关闭远程服务端的连接
                            $connection->onClose = function ($connection) {
                                $connection->opposite->close();
                                $connection->opposite = null;
                            };

                            // 远程连接发生错误时（一般是建立连接失败错误），关闭shadowsocks客户端的连接
                            $remote_connection->onError = function ($remote_connection, $code, $msg) use ($address) {
                                echo "remote_connection $address error code:$code msg:$msg\n";
                                $remote_connection->close();
                                if (!empty($remote_connection->opposite)) {
                                    $remote_connection->opposite->close();
                                }
                            };
                            // 当shadowsocks客户端连接上有错误时，关闭远程服务端连接
                            $connection->onError = function ($connection, $code, $msg) {
                                echo "connection err code:$code msg:$msg\n";
                                $connection->close();
                                if (isset($connection->opposite)) {
                                    $connection->opposite->close();
                                }
                            };

                            // 远程连接发来消息时，进行解密，转发给shadowsocks客户端，shadowsocks客户端会解密转发给浏览器
                            $remote_connection->onMessage = function ($remote_connection, $buffer) {
                                $remote_connection->opposite->send($remote_connection->opposite->cipher->decrypt($buffer));
                            };
                            // 当shadowsocks客户端发来数据时，加密数据，并发给远程服务端
                            $connection->onMessage = function ($connection, $data) {
                                $connection->opposite->send($connection->cipher->encrypt($data));
                            };

                            // 执行远程连接
                            $remote_connection->connect();

                            // forward the first package. delete VER, CMD and RSV in buffer
                            $buffer = substr($buffer, $dst_addr_needle = 3);
                            $remote_connection->send($connection->cipher->encrypt($buffer));

                            // start package forwarding
                            $connection->state = Connection::STAGE_STREAM;
                            break;
                        // case Connection::CMD_BIND:
                        default:
                            $connection->send("\x05\x07\x00\x01");
                            $connection->close();
                    }
            }
        };
        return $worker;
    }

    public static function createServerWorker(array $config, $protocol)
    {
        $worker = new Worker($protocol . '://0.0.0.0:' . $config['port']);
        $worker->count = $config['process_num'];
        $worker->name = 'shadowsocks-server';

        // shadowsocks client on connect
        $worker->onConnect = function ($connection) use ($config) {
            $connection->stage = Connection::STAGE_INIT;
            $connection->cipher = new Encryptor($config['password'], $config['encryption']);
        };

        // message from shadowsocks client
        $worker->onMessage = function ($connection, $buffer) use ($config, $protocol) {
            switch ($connection->stage) {
                case Connection::STAGE_INIT:
                case Connection::STAGE_ADDR:
                    $buffer = $connection->cipher->decrypt($buffer);

                    $header_data = Connection::parseSocket5Header($buffer);
                    if (empty($header_data)) {
                        $connection->close();
                        return;
                    }

                    // build tunnel to actual server
                    $address = "{$protocol}://{$header_data['dst_addr']}:{$header_data['dst_port']}";
                    $remote_connection = new AsyncTcpConnection($address);

                    $remote_connection->opposite = $connection;
                    $connection->opposite = $remote_connection;

                    // 流量控制，远程连接的发送缓冲区满，则停止读取shadowsocks客户端发来的数据
                    // 避免由于读取速度大于发送速导致发送缓冲区爆掉
                    $remote_connection->onBufferFull = function ($remote_connection) {
                        $remote_connection->opposite->pauseRecv();
                    };
                    // 流量控制，shadowsocks客户端的连接发送缓冲区满时，则停止读取远程服务端的数据
                    // 避免由于读取速度大于发送速导致发送缓冲区爆掉
                    $connection->onBufferFull = function ($connection) {
                        $connection->opposite->pauseRecv();
                    };

                    // 流量控制，远程连接的发送缓冲区发送完毕后，则恢复读取shadowsocks客户端发来的数据
                    $remote_connection->onBufferDrain = function ($remote_connection) {
                        $remote_connection->opposite->resumeRecv();
                    };
                    // 流量控制，当shadowsocks客户端的连接发送缓冲区发送完毕后，继续读取远程服务端的数据
                    $connection->onBufferDrain = function ($connection) {
                        $connection->opposite->resumeRecv();
                    };

                    // 远程连接断开时，则断开shadowsocks客户端的连接
                    $remote_connection->onClose = function ($remote_connection) {
                        $remote_connection->opposite->close();
                        $remote_connection->opposite = null;
                    };
                    // 当shadowsocks客户端关闭连接时，关闭远程服务端的连接
                    $connection->onClose = function ($connection) {
                        $connection->opposite->close();
                        $connection->opposite = null;
                    };

                    // 远程连接发生错误时（一般是建立连接失败错误），关闭shadowsocks客户端的连接
                    $remote_connection->onError = function ($remote_connection, $code, $msg) use ($address) {
                        echo "remote_connection $address error code:$code msg:$msg\n";
                        $remote_connection->close();
                        if (!empty($remote_connection->opposite)) {
                            $remote_connection->opposite->close();
                        }
                    };
                    // 当shadowsocks客户端连接上有错误时，关闭远程服务端连接
                    $connection->onError = function ($connection, $code, $msg) {
                        echo "connection err code:$code msg:$msg\n";
                        $connection->close();
                        if (isset($connection->opposite)) {
                            $connection->opposite->close();
                        }
                    };

                    // 远程连接发来消息时，进行加密，转发给shadowsocks客户端，shadowsocks客户端会解密转发给浏览器
                    $remote_connection->onMessage = function ($remote_connection, $buffer) {
                        $remote_connection->opposite->send($remote_connection->opposite->cipher->encrypt($buffer));
                    };
                    // 当shadowsocks客户端发来数据时，解密数据，并发给远程服务端
                    $connection->onMessage = function ($connection, $data) {
                        $connection->opposite->send($connection->cipher->decrypt($data));
                    };

                    // 执行远程连接
                    $remote_connection->connect();

                    // send extra data to actual server if data is longer than header
                    if (strlen($buffer) > $header_data['header_len']) {
                        $remote_connection->send(substr($buffer, $header_data['header_len']));
                    }

                    // start package forwarding
                    $connection->state = Connection::STAGE_STREAM;
            }
        };
        return $worker;
    }

    /**
     *
     * @param $buffer
     * @return array
     *
     * @see https://tools.ietf.org/html/rfc1928  #5. Addressing
     *
     */
    public static function parseSocket5Header($buffer)
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

                /* not sure, log this */
                if ($logger = Launcher::getModuleIfReady('logger__')) {
                    $logger->debug('incoming ipv6', array(
                            'dst_addr' => $dst_addr,
                            'port_data' => $port_data,
                            'dst_port' => $dst_port,
                        )
                    );
                }
                break;
            default:
                return array();
        }

        return array(
            'addr_type' => $addr_type,
            'dst_addr' => $dst_addr,
            'dst_port' => $dst_port,
            'header_len' => $header_len,
        );
    }
}