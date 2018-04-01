# shadowrocket

一个帮助你建立自己的socket隧道的composer组件。

## 特点
1. 支持TCP/UDP
2. 支持IPV4/DOMAINNAME/IPV6

### 即将实现的特点
- monolog support
- server manager
- user management


## 安装

    composer require ycgambo/shadowrocket

### 安装需求
1. Composer
2. PHP 5.3+

## 用法

### 运行服务器

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use ShadowRocket\Launcher;

$launcher = new Launcher();
$launcher->addServer();
$launcher->launchAll();

```

以上代码将按默认配置启动一个服务器

### 自定义配置

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

// 更改某部分配置以应用到另一个服务器的启动上
$launcher->addServer(array(
    'port'        => '8389',
    'password'    => 'another_pass'
));

// 启动这两个服务器
$launcher->launchAll();

```

### 启动本地代理

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

现在我们可以发送数据包到127.0.0.1:1086，然后服务器123.456.78.9:8388将响应我们的请求。

## 使用本地代理APP

- [Android版本](https://github.com/shadowsocks/shadowsocks-android/releases)
- [IOS版本](https://itunes.apple.com/cn/app/superwingy/id1290093815?mt=8)
- [Mac版本](https://github.com/shadowsocks/ShadowsocksX-NG/releases) 
- [Windows版本](https://github.com/shadowsocks/shadowsocks-windows/releases)
