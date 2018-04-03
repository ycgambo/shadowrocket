# shadowrocket

一个帮助你建立自己的socket隧道的composer组件。

## 特点
1. 支持TCP/UDP
2. 支持IPV4/DOMAINNAME/IPV6
3. monolog日志系统

### 即将实现的特点
- 黑名单
- 服务管理器
- 用户管理


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

use ShadowRocket\Bin\Launcher;

Launcher::initialize();
Launcher::addModule('server');
Launcher::launchAll();
```

以上代码将按默认配置启动一个服务器

### 自定义配置

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use ShadowRocket\Bin\Launcher;

$config = array(
    'server' => array(
        'port'        => '8388',
        'password'    => 'mypass',
        'encryption'  => 'aes-256-cfb',
        'process_num' => 12,
    )
);
Launcher::initialize($config);
Launcher::addModule('server');

// 更改某部分配置以启动另一个服务器端口
Launcher::addModule('server', $changing_config = array(
    'port'        => '8389',
    'password'    => 'another_pass'
));

// 启动这两个服务器
Launcher::launchAll();
```

### 启动本地代理

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use ShadowRocket\Bin\Launcher;

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
Launcher::initialize($config);
Launcher::addModule('local');
Launcher::launchAll();
```

现在我们可以发送数据包到127.0.0.1:1086，然后服务器123.456.78.9:8388将响应我们的请求。

## 使用本地代理APP

- [Android版本](https://github.com/shadowsocks/shadowsocks-android/releases)
- [IOS版本](https://itunes.apple.com/cn/app/superwingy/id1290093815?mt=8)
- [Mac版本](https://github.com/shadowsocks/ShadowsocksX-NG/releases) 
- [Windows版本](https://github.com/shadowsocks/shadowsocks-windows/releases)
