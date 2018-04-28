# shadowrocket

A socks5 proxy to build your own shadowsocks private network. PHP based & Composer supported.

[中文文档](/doc/README-chn.md)
[Contributing](/doc/contributing.md)

## Features
1. TCP/UDP support
2. IPV4/DOMAINNAME/IPV6 support
3. Graceful restart
4. Monolog Logger

### Coming Next
- black list
- server manager
- user management


## Install

    composer require ycgambo/shadowrocket

### Requirements
1. Composer
2. PHP 5.3+

## Usage

### Run a server

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$config = array(
    'server' => array(
        'port'        => '8388',
        'password'    => 'mypass',
        'encryption'  => 'aes-256-cfb',
        'process_num' => 12,
    ),
);

ShadowRocket\Bin\Launcher::launch($config);
```

### Run a local proxy

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$config = array(
    'local' => array(
        'server'      => '123.456.78.9',
        'port'        => '8388',
        'password'    => 'mypass',
        'encryption'  => 'aes-256-cfb',
        'local_port'  => '1086',
        'process_num' => 12,
    )
);

ShadowRocket\Bin\Launcher::launch($config);
```

This means we want to pass data to local proxy 127.0.0.1:1086 which 
will request proxy server 123.456.78.9:8388 for reply.

### Fire your script up

Assuming your script is named as start.php, in which contains code to launch the Launcher.

To run as daemon:

    php start.php start -d

To stop your script:

    php start.php stop

To graceful restart：

    php start.php reload

To check status:

    php start.php status

To check connections:

    php start.php connections

### More docs
- [Run multi server on different port](/doc/multi-server.md)
- [Launcher](/doc/launcher.md)
- [Modules](/doc/modules.md)

## Want a client APP?

- [For Android](https://github.com/shadowsocks/shadowsocks-android/releases)
- [For IOS](https://itunes.apple.com/cn/app/superwingy/id1290093815?mt=8)
- [For Mac](https://github.com/shadowsocks/ShadowsocksX-NG/releases) 
- [For Windows](https://github.com/shadowsocks/shadowsocks-windows/releases)
