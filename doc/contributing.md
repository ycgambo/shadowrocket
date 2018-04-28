
# Contributing

## Working on

### Server Manager

add superadd method for needed Modules. then we can create a Manager module that
superadd these modules after launch.

Manager should also be a Worker listend on a port. It will manage modules when it receives message.

to dynamically start a worker, use $worker->listen();

to dynamically stop a worker, use Worker::stopAll() in sub worker process; // not sure

## Recent done