
## Modules

Module is the basic functional unit. Supported modules and their required configurations are listed here:

### Server

Server receives request from Local clients and returns response to them.

#### Required configurations

- port: port to listen on
- password
- encryption: the encryption method. default: aes-256-cfb
- process_num: how many process do you want. default: 4

### Local

Local receives local requests and passes them to shadowsocks server, then returns responses back.

#### Required configurations

- server: the ip of server
- port: the port server listened on
- password: the server password
- encryption: the encryption method. default: aes-256-cfb
- local_port: the port this local server listened on
- process_num: how many process do you want. default: 4

### Guarder

Guarder will determine whether to allow a request pass the port it guarded or recklessly close the port.

#### Required configurations

- instance: A class instance implemented ShadowRocket\Module\Base\ManageableInterface. 
default: ShadowRocket\Module\Base\Guarder

The default guarder will pass all the requests.

### Manager

It can dynamically manage your modules, even after the launch. Use `telnet` to talk with it.

#### Required configurations

- port: the port server listened on
- token: a connection is trusted only after this token received
- process_num: how many process do you want. default: 1

Only server:add are supported now.
