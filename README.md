# shadowrocket

A PHP component that helps you to build your own socks tunnel.

### Features
1. Composer support
2. PHP 5.3+ support
3. Does new born take into account?

### Coming Next
- Fully socks support

## Install

    composer require ycgambo/shadowrocket


## Run a server

### Use this `Launcher`

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use ShadowRocket\Launcher;

$launcher = new Launcher();
$launcher->addServer();
$launcher->launch();

```

### Custom configuration

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use ShadowRocket\Launcher;

$config = array(
    'server'      => '127.0.0.1',
    'port'        => '8388',
    'password'    => 'mypass',
    'encryption'  => 'aes-256-cfb',
    'local_port'  => '6001',
    'process_num' => 12,
);
$launcher = new Launcher($config);

// multi-server
$launcher->addServer();

// change some configurations
$launcher->addServer(array(
    'port'        => '8389',
    'password'    => 'another_pass'
));

$launcher->launch();

```

## Get Clients

You can find many client apps in [shadowsocks repo](https://github.com/shadowsocks).

- [For Android](https://github.com/shadowsocks/shadowsocks-android/releases)
- [For IOS](https://github.com/shadowsocks/shadowsocks-iOS/releases)
- [For Mac](https://github.com/shadowsocks/ShadowsocksX-NG/releases) 
- [For Windows](https://github.com/shadowsocks/shadowsocks-windows/releases)
