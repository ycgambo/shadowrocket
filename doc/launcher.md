
## Launcher

Launcher is the entry point of shadowrocket.

### Config

We can launch all the [Modules](/doc/modules.md) by passing a array which contains the config of each module to it. In
this array, each item should be a key-value pair and the key is the module name and the value is the config. 

#### Name format

It should be declared that the module name has certain connections with Modules, we can't name them casually.

We also use this name to trace the corresponding module, so you can only use a name once.

Simply, it's case insensitive snake_case module name with optional tailing id number.

Let's explain it:

1. Start with module name and only contains module name.
2. If the module name contains multi word, separate them with underline(`_`).
3. Add id number at the end, separate it from module name with underline(`_`).
4. For tailing number, the separation from module name is not required.
5. It's case insensitive.

Example:

Server, server, server1, server_2, module_name, module_name_3

#### Config format

We pass an array to Launcher and each value in this array should also be an array which specifies the config of 
corresponding module.

For more details about module names and their configurations, see [Modules](/doc/modules.md) 