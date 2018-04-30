# shadowrocket

协助你建立自己的shadowsocks私有网络的socks5代理。基于PHP开发，支持Composer。

[贡献代码](/doc/contributing.md)

## 特性
1. 支持TCP/UDP
2. 支持IPV4/DOMAINNAME/IPV6
3. 平滑重启
4. Monolog日志系统
5. 端口守卫(黑名单)

### 即将实现的特性
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

这代表着我们想要发送数据包到本地代理127.0.0.1:1086，
它会请求代理服务器123.456.78.9:8388的响应。

### 启动脚本

假设你的脚本命名为start.php,其中包含了启动Launcher的代码。

作为守护进程启动:

    php start.php start -d

停止守护进程:

    php start.php stop
    
平滑重启:

    php start.php reload

查看状态:

    php start.php status

查看连接状态:

    php start.php connections


### 更多文档
- [在不同端口运行多个服务](/doc/multi-server-ch.md)
- [Launcher加载器](/doc/launcher-chn.md)
- [组件](/doc/modules-chn.md)

## 使用本地代理APP

- [Android版本](https://github.com/shadowsocks/shadowsocks-android/releases)
- [IOS版本](https://itunes.apple.com/cn/app/superwingy/id1290093815?mt=8)
- [Mac版本](https://github.com/shadowsocks/ShadowsocksX-NG/releases) 
- [Windows版本](https://github.com/shadowsocks/shadowsocks-windows/releases)
