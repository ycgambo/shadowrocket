
## 多服务器

### 示例

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
    'server2' => array(
        'port'        => '8389',
        'password'    => 'another_pass',
        'encryption'  => 'aes-256-cfb',
        'process_num' => 12,
    ),
    'server_3' => array(
        'port'        => '8390',
        'password'    => 'some_other_pass',
        'encryption'  => 'aes-256-cfb',
        'process_num' => 12,
    ),
);

ShadowRocket\Bin\Launcher::launch($config);
```

- 每个服务器应该配置在不同的端口上
- 每个服务器都应该提供全部配置项
- 每个服务器的命名应符合正则表达式`server[_\d]*$`

更多命名细节，请参考[Launcher](/doc/launcher-chn.md)