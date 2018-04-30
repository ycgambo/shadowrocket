
## 组件

组件是基本的功能单元。支持的组件名称和它们需要的配置如下：

### Server

Server接受客户端的请求然后返回响应。

#### 需要的配置

- port: 监听的端口
- password
- encryption: 加密方法
- process_num: 想要启用的进程数

### Local

本地代理接收本地请求，并传递给shadowsocks服务器，然后返回响应。

#### 需要的配置

- server: 服务器IP
- port: 服务器监听的端口
- password: 服务器密码
- encryption: 加密方法
- local_port: 本地代理监听的端口
- process_num: 想要启用的进程数

### Logger

Logger使用Monolog的Registry

#### 需要的配置

- logger_name: logger名, 默认值: shadowrocket_logger
- handlers: Monolog\Handler\HandlerInterface实例的数组

### Guarder

Guarder将决定是否对端口上的请求放行，或者鲁莽的关闭该端口

#### 需要的配置

- instance: 实现了ShadowRocket\Module\Base\ManageableInterface接口的类. 
默认值: ShadowRocket\Module\Base\Guarder

现在，默认的Guarder将会放行所有请求。
