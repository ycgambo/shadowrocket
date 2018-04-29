<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       Local.php
 * @author     ycgambo
 * @update     4/26/18 10:09 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Module;

use ShadowRocket\Module\Base\ConfigRequired;
use ShadowRocket\Module\Base\LauncherModuleInterface;
use ShadowRocket\Net\Connection;
use ShadowRocket\Net\Encryptor;
use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\UdpConnection;

class Local extends ConfigRequired implements LauncherModuleInterface
{
    public $workers = array();

    public function initConfig(array $config = array())
    {
        $this->resetConfig($config);
        $this->declareRequiredConfig(array(
            'server',
            'port',
            'password',
            'encryption',
            'local_port',
            'process_num',
        ));
    }

    public function getReady()
    {
        $this->createWorker('tcp');
        $this->createWorker('udp');
    }

    public function superadd()
    {
        $this->createWorker('tcp')->listen();
        $this->createWorker('udp')->listen();
    }

    protected function createWorker($protocol)
    {
        $config = $this->getConfig();

        $worker = new Worker($protocol . '://0.0.0.0:' . $config['local_port']);
        $worker->count = $config['process_num'];
        $worker->name = 'shadowsocks-local';

        $worker->onConnect = function ($browser) use ($config) {
            $browser->stage = Connection::STAGE_INIT;
            $browser->cipher = new Encryptor($config['password'], $config['encryption']);
        };

        $worker->onMessage = function ($browser, $buffer) use ($config, $protocol) {
            switch ($browser->stage) {
                case Connection::STAGE_INIT:
                    // see: https://www.ietf.org/rfc/rfc1928 #6.Replies
                    $browser->send("\x05\x00");
                    $browser->stage = Connection::STAGE_ADDR;
                    break;

                case Connection::STAGE_ADDR:
                    // see: https://www.ietf.org/rfc/rfc1928 #4.Requests
                    if ($buffer) {
                        switch ($cmd = ord($buffer[1])) {
                            case Connection::CMD_CONNECT:
                            case Connection::CMD_UDP_ASSOCIATE:
                                $browser->stage = Connection::STAGE_CONNECTING;
                                $browser->send("\x05\x00\x00\x01\x00\x00\x00\x00" . pack('n', $config['local_port']));

                                // build tunnel to shadowsocks server
                                $address = "{$protocol}://{$config['server']}:{$config['port']}";
                                $server = ($protocol == 'udp')
                                    ? new UdpConnection(socket_create(AF_INET, SOCK_DGRAM, SOL_UDP), $address)
                                    : new AsyncTcpConnection($address);

                                Connection::bind($server, $browser);

                                // 远程连接发来消息时，进行解密，转发给shadowsocks客户端，shadowsocks客户端会解密转发给浏览器
                                $server->onMessage = function ($server, $buffer) {
                                    $server->opposite->send($server->opposite->cipher->decrypt($buffer));
                                };
                                // 当shadowsocks客户端发来数据时，加密数据，并发给远程服务端
                                $browser->onMessage = function ($browser, $data) {
                                    $browser->opposite->send($browser->cipher->encrypt($data));
                                };

                                $server->connect();

                                // forward the first package. delete VER, CMD and RSV in buffer
                                $server->send($browser->cipher->encrypt(substr($buffer, $dst_addr_needle = 3)));

                                // start package forwarding
                                $browser->state = Connection::STAGE_STREAM;
                                break;
                            // case Connection::CMD_BIND:
                            default:
                                $browser->send("\x05\x07\x00\x01");
                                $browser->close();
                        }
                    }
            }
        };

        return $worker;
    }
}
