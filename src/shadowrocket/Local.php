<?php

namespace ShadowRocket;

use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;

class Local extends Configurable {

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

    public function __construct(array $config = array()) {
        $this->setConfig($config);
    }

    public function checkConfig() {
        $check_list = array(
            'password',
            'encryption',
            'local_port',
        );

        foreach ($check_list as $check_item) {
            if (!isset($this->_config[$check_item])) {
                throw new \ArgumentCountError();
            }
        }
    }

    public function onConnect($connection) {
        // 设置当前连接的状态为STAGE_INIT，初始状态
        $connection->stage = self::STAGE_INIT;
        // 初始化加密类
        $connection->encryptor = new Encryptor($this->_config['password'], $this->_config['encryption']);
    }

    public function onMessage($connection, $buffer) {
        // 判断当前连接的状态
        switch ($connection->stage) {
            case self::STAGE_INIT:
                //与客户端建立socks5连接
                //参见: https://www.ietf.org/rfc/rfc1928.txt 6.Replies
                $connection->send("\x05\x00");
                $connection->stage = self::STAGE_ADDR;
                break;
            case self::STAGE_ADDR:
                $cmd = ord($buffer[1]);
                //仅处理客户端的tcp连接请求
                if ($cmd != self::CMD_CONNECT) {
                    echo "unsupport cmd\n";
                    $connection->send("\x05\x07\x00\x01");
                    return $connection->close();
                }
                $connection->stage = self::STAGE_CONNECTING;
                $buf_replies = "\x05\x00\x00\x01\x00\x00\x00\x00" . pack('n', $this->_config['local_port']);
                $connection->send($buf_replies);
                $address = "tcp://{$this->_config['server']}:{$this->_config['port']}";

                $tunnel = $this->buildTunnel($connection, $address);

                // 改变当前连接的状态为stage_stream，即开始转发数据流
                $connection->state = self::STAGE_STREAM;

                //转发首个数据包，包含由客户端封装的目标地址，端口号等信息
                $buffer = substr($buffer, 3);
                $buffer = $connection->encryptor->encrypt($buffer);
                $tunnel->send($buffer);
        }
    }

    protected function buildTunnel($connection, $address) {
        $remote_connection = new asynctcpconnection($address);
        $connection->opposite = $remote_connection;
        $remote_connection->opposite = $connection;
        // 流量控制
        $remote_connection->onbufferfull = function ($remote_connection) {
            $remote_connection->opposite->pauserecv();
        };
        $remote_connection->onbufferdrain = function ($remote_connection) {
            $remote_connection->opposite->resumerecv();
        };
        // 远程连接发来消息时，进行解密，转发给客户端
        $remote_connection->onmessage = function ($remote_connection, $buffer) {
            $remote_connection->opposite->send($remote_connection->opposite->encryptor->decrypt($buffer));
        };
        // 远程连接断开时，则断开客户端的连接
        $remote_connection->onclose = function ($remote_connection) {
            // 关闭对端
            $remote_connection->opposite->close();
            $remote_connection->opposite = NULL;
        };
        // 远程连接发生错误时（一般是建立连接失败错误），关闭客户端的连接
        $remote_connection->onerror = function ($remote_connection, $code, $msg) use ($address) {
            echo "remote_connection $address error code:$code msg:$msg\n";
            $remote_connection->close();
            if ($remote_connection->opposite) {
                $remote_connection->opposite->close();
            }
        };
        // 流量控制
        $connection->onbufferfull = function ($connection) {
            $connection->opposite->pauserecv();
        };
        $connection->onbufferdrain = function ($connection) {
            $connection->opposite->resumerecv();
        };
        // 当客户端发来数据时，加密数据，并发给远程服务端
        $connection->onmessage = function ($connection, $data) {
            $connection->opposite->send($connection->encryptor->encrypt($data));
        };
        // 当客户端关闭连接时，关闭远程服务端的连接
        $connection->onclose = function ($connection) {
            $connection->opposite->close();
            $connection->opposite = NULL;
        };
        // 当客户端连接上有错误时，关闭远程服务端连接
        $connection->onerror = function ($connection, $code, $msg) {
            echo "connection err code:$code msg:$msg\n";
            $connection->close();
            if (isset($connection->opposite)) {
                $connection->opposite->close();
            }
        };
        // 执行远程连接
        $remote_connection->connect();

        return $remote_connection;
    }

    public function getReady() {
        $worker = new Worker('tcp://0.0.0.0:' . $this->_config['local_port']);
        $worker->count = $this->_config['process_num'];
        $worker->name = 'shadowsocks-local';

        // 如果加密算法为table，初始化table
        if ($this->_config['encryption'] == 'table') {
            Encryptor::initTable($this->_config['password']);
        }

        // 当shadowsocks客户端连上来时
        $worker->onConnect = array($this, 'onConnect');

        // 当shadowsocks客户端发来消息时
        $worker->onMessage = array($this, 'onMessage');
    }
}
