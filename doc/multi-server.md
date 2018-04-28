
## Multi-server

### Lazy Example

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$config = array(
    'server' => array(
        'name'        => 'server_on_8388',
        'port'        => '8388',
        'password'    => 'mypass',
        'encryption'  => 'aes-256-cfb',
        'process_num' => 12,
    ),
    'server2' => array(
        'name'        => 'server_on_8389',
        'port'        => '8389',
        'password'    => 'another_pass',
        'encryption'  => 'aes-256-cfb',
        'process_num' => 12,
    ),
    'server_3' => array(
        'name'        => 'server_on_8390',
        'port'        => '8390',
        'password'    => 'some_other_pass',
        'encryption'  => 'aes-256-cfb',
        'process_num' => 12,
    ),
);

ShadowRocket\Bin\Launcher::launch($config);
```

- Each server are using different name
- Each server is using different port
- Support fully configurations of each server
- $config keys should fit Regular Expression format `server[_\d]*$`

For more detail about module name, see [Launcher](/doc/launcher.md).

