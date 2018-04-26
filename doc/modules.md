
## Modules

Module is the basic functional unit. Supported modules and their required configurations are listed here:

### Server

Server receives request from Local clients and returns response to them.

#### Required configurations

- port: port to listen on
- password
- encryption: the encryption method
- process_num: how many process do you want

### Local

Local receives local requests and passes them to shadowsocks server, then returns responses back.

#### Required configurations

- server: the ip of server
- port: the port server listened on
- password: the server password
- encryption: the encryption method
- local_port: the port this local server listened on
- process_num: how many process do you want

### Logger

Logger uses Monolog Registry

#### Required configurations

- logger_name: the name of logger, default: shadowrocket_logger
- handlers: array of handler that are instance of Monolog\Handler\HandlerInterface