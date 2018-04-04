# shadowrocket

一个帮助你建立自己的socket隧道的composer组件。

## 特点
1. 支持TCP/UDP
2. 支持IPV4/DOMAINNAME/IPV6
3. Monolog日志系统

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

### 运行本地代理

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

现在我们可以发送数据包到127.0.0.1:1086，然后服务器123.456.78.9:8388将响应我们的请求。

### 更多文档
- [在不同端口运行多个服务]
- [Launcher加载器]
- [组件]

## 使用本地代理APP

- [Android版本](https://github.com/shadowsocks/shadowsocks-android/releases)
- [IOS版本](https://itunes.apple.com/cn/app/superwingy/id1290093815?mt=8)
- [Mac版本](https://github.com/shadowsocks/ShadowsocksX-NG/releases) 
- [Windows版本](https://github.com/shadowsocks/shadowsocks-windows/releases)
