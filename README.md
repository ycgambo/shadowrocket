# shadowrocket

A composer component that helps you to build your own socks tunnel.

## Features
1. TCP/UDP support
2. IPV4/DOMAINNAME/IPV6 support

### Coming Next
- monolog support
- server manager
- user management


## Install

    composer require ycgambo/shadowrocket

### Requirements
1. Composer support
2. PHP 5.3+ support

## Usage

### Run a server

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use ShadowRocket\Launcher;

$launcher = new Launcher();
$launcher->addServer();
$launcher->launchAll();

```

These code start a server by using default configurations.

### Custom configuration

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use ShadowRocket\Launcher;

$config = array(
    'port'        => '8388',
    'password'    => 'mypass',
    'encryption'  => 'aes-256-cfb',
    'process_num' => 12,
);
$launcher = new Launcher($config);

$launcher->addServer();

// change some configurations to config another port
$launcher->addServer(array(
    'port'        => '8389',
    'password'    => 'another_pass'
));

// launch these two servers
$launcher->launchAll();

```

### Run a local proxy

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use ShadowRocket\Launcher;

$config = array(
    'server'      => '123.456.78.9',
    'port'        => '8388',
    'password'    => 'mypass',
    'encryption'  => 'aes-256-cfb',
    'local_port'  => '1086',
);
$launcher = new Launcher($config);
$launcher->addLocal();
$launcher->launchAll();

```

Now we can pass data to 127.0.0.1:1086 and server 123.456.78.9:8388 will reply.

You can find many client apps in [shadowsocks repo](https://github.com/shadowsocks) if you prefer app.

- [For Android](https://github.com/shadowsocks/shadowsocks-android/releases)
- [For IOS](https://github.com/shadowsocks/shadowsocks-iOS/releases)
- [For Mac](https://github.com/shadowsocks/ShadowsocksX-NG/releases) 
- [For Windows](https://github.com/shadowsocks/shadowsocks-windows/releases)
