
## Multi-server

### Example

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

- Each server is using different port
- Support fully configurations of each server
- Name them as Regular Expression format `server[_\d]*$`

For more detail about name format, see [Launcher](/doc/launcher.md).
