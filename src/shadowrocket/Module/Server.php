<?php
/**
 * This file is part of shadowrocket.
 *
 * @file       Server.php
 * @author     ycgambo
 * @update     4/26/18 10:09 AM
 * @copyright  shadowrocket <https://github.com/ycgambo/shadowrocket>
 * @license    MIT License <http://www.opensource.org/licenses/mit-license.html>
 */

namespace ShadowRocket\Module;

use ShadowRocket\Bin\Launcher;
use ShadowRocket\Module\Base\ConfigRequired;
use ShadowRocket\Module\Base\LauncherModuleInterface;
use ShadowRocket\Module\Base\ManageableInterface;
use ShadowRocket\Net\Connection;
use ShadowRocket\Net\Encryptor;
use Workerman\Connection\UdpConnection;
use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;

class Server extends ConfigRequired implements LauncherModuleInterface, ManageableInterface
{
    public function initConfig(array $config = array())
    {
        $this->resetConfig($config);
        $this->declareRequiredConfig(array(
            'port',
            'password',
            'encryption',
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

    public function stop()
    {
        // TODO: Implement remove() method.
    }

    protected function createWorker($protocol)
    {
        $config = $this->getConfig();

        $worker = new Worker($protocol . '://0.0.0.0:' . $config['port']);
        $worker->count = $config['process_num'];
        $worker->name = 'shadowsocks-server';
        $worker->reusePort = true;

        $worker->onConnect = function ($client) use ($config) {
            $client->stage = Connection::STAGE_INIT;
            $client->cipher = new Encryptor($config['password'], $config['encryption']);
        };

        $worker->onMessage = function ($client, $buffer) use ($config, $protocol, $worker) {
            switch ($client->stage) {
                case Connection::STAGE_INIT:
                case Connection::STAGE_ADDR:
                    $buffer = $client->cipher->decrypt($buffer);

                    if ($request = Connection::parseSocket5Request($buffer)) {

                        if ($guarder = Launcher::getModuleIfReady('guarder')) {
                            if ($guarder->_deny($request, $config['port'])) {
                                $worker->stop();
                            }

                            if ($guarder->_block($request, $config['port'])) {
                                return;
                            }
                        }

                        // build tunnel to actual server
                        $address = "{$protocol}://{$request['dst_addr']}:{$request['dst_port']}";
                        $remote = ($protocol == 'udp')
                            ? new UdpConnection(socket_create( AF_INET, SOCK_DGRAM, SOL_UDP ), $address)
                            : new AsyncTcpConnection($address);

                        Connection::bind($client, $remote);

                        // 远程连接发来消息时，进行加密，转发给shadowsocks客户端，shadowsocks客户端会解密转发给浏览器
                        $remote->onMessage = function ($remote, $buffer) {
                            $remote->opposite->send($remote->opposite->cipher->encrypt($buffer));
                        };
                        // 当shadowsocks客户端发来数据时，解密数据，并发给远程服务端
                        $client->onMessage = function ($client, $data) {
                            $client->opposite->send($client->cipher->decrypt($data));
                        };

                        $remote->connect();

                        if ($request['data']) {
                            $remote->send($request['data']);
                        }

                        $client->state = Connection::STAGE_STREAM;
                    }
            }
        };

        return $worker;
    }
}
