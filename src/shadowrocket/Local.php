<?php

namespace ShadowRocket;

use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;

class Local {
    const constants = array(
        'STAGE_INIT'        => 0,
        'STAGE_ADDR'        => 1,
        'STAGE_UDP_ASSOC'   => 2,
        'STAGE_DNS'         => 3,
        'STAGE_CONNECTING'  => 4,
        'STAGE_STREAM'      => 5,
        'STAGE_DESTROYED'   => -1,
        'CMD_CONNECT'       => 1,
        'CMD_BIND'          => 2,
        'CMD_UDP_ASSOCIATE' => 3,
    );

    public function register(array $config) {
        $worker = new Worker('tcp://0.0.0.0:' . $config['local_port']);
        $worker->count = $config['process_num'];
        $worker->name = 'shadowsocks-local';
        // 如果加密算法为table，初始化table
        if ($config['encryption']== 'table') {
            Encryptor::initTable($config['password']);
        }
        // 当客户端连上来时
        $worker->onConnect = function ($connection) use ($config) {
            // 设置当前连接的状态为STAGE_INIT，初始状态
            $connection->stage = self::constants['STAGE_INIT'];
            // 初始化加密类
            $connection->encryptor = new Encryptor($config['password'], $config['encryption']);
        };
        // 当客户端发来消息时
        $worker->onMessage = function ($connection, $buffer) use ($config) {
            // 判断当前连接的状态
            switch ($connection->stage) {
                case self::constants['STAGE_INIT']:
                    //与客户端建立SOCKS5连接
                    //参见: https://www.ietf.org/rfc/rfc1928.txt
                    $connection->send("\x05\x00");
                    $connection->stage = self::constants['STAGE_ADDR'];
                    return;
                case self::constants['STAGE_ADDR']:
                    $cmd = ord($buffer[1]);
                    //仅处理客户端的TCP连接请求
                    if ($cmd != self::constants['CMD_CONNECT']) {
                        echo "unsupport cmd\n";
                        $connection->send("\x05\x07\x00\x01");
                        return $connection->close();
                    }
                    $connection->stage = self::constants['STAGE_CONNECTING'];
                    $buf_replies = "\x05\x00\x00\x01\x00\x00\x00\x00" . pack('n', $config['local_port']);
                    $connection->send($buf_replies);
                    $address = "tcp://{$config['server']}:{$config['port']}";
                    $remote_connection = new AsyncTcpConnection($address);
                    $connection->opposite = $remote_connection;
                    $remote_connection->opposite = $connection;
                    // 流量控制
                    $remote_connection->onBufferFull = function ($remote_connection) {
                        $remote_connection->opposite->pauseRecv();
                    };
                    $remote_connection->onBufferDrain = function ($remote_connection) {
                        $remote_connection->opposite->resumeRecv();
                    };
                    // 远程连接发来消息时，进行解密，转发给客户端
                    $remote_connection->onMessage = function ($remote_connection, $buffer) {
                        $remote_connection->opposite->send($remote_connection->opposite->encryptor->decrypt($buffer));
                    };
                    // 远程连接断开时，则断开客户端的连接
                    $remote_connection->onClose = function ($remote_connection) {
                        // 关闭对端
                        $remote_connection->opposite->close();
                        $remote_connection->opposite = NULL;
                    };
                    // 远程连接发生错误时（一般是建立连接失败错误），关闭客户端的连接
                    $remote_connection->onError = function ($remote_connection, $code, $msg) use ($address) {
                        echo "remote_connection $address error code:$code msg:$msg\n";
                        $remote_connection->close();
                        if ($remote_connection->opposite) {
                            $remote_connection->opposite->close();
                        }
                    };
                    // 流量控制
                    $connection->onBufferFull = function ($connection) {
                        $connection->opposite->pauseRecv();
                    };
                    $connection->onBufferDrain = function ($connection) {
                        $connection->opposite->resumeRecv();
                    };
                    // 当客户端发来数据时，加密数据，并发给远程服务端
                    $connection->onMessage = function ($connection, $data) {
                        $connection->opposite->send($connection->encryptor->encrypt($data));
                    };
                    // 当客户端关闭连接时，关闭远程服务端的连接
                    $connection->onClose = function ($connection) {
                        $connection->opposite->close();
                        $connection->opposite = NULL;
                    };
                    // 当客户端连接上有错误时，关闭远程服务端连接
                    $connection->onError = function ($connection, $code, $msg) {
                        echo "connection err code:$code msg:$msg\n";
                        $connection->close();
                        if (isset($connection->opposite)) {
                            $connection->opposite->close();
                        }
                    };
                    // 执行远程连接
                    $remote_connection->connect();
                    // 改变当前连接的状态为STAGE_STREAM，即开始转发数据流
                    $connection->state = self::constants['STAGE_STREAM'];
                    //转发首个数据包，包含由客户端封装的目标地址，端口号等信息
                    $buffer = substr($buffer, 3);
                    $buffer = $connection->encryptor->encrypt($buffer);
                    $remote_connection->send($buffer);
            }
        };
    }
}
