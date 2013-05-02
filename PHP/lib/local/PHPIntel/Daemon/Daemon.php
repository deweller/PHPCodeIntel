<?php

namespace PHPIntel\Daemon;

use PHPIntel\Logger\Logger;

use \Exception;

/*
* Daemon
* runs a daemon that serves command requests
*/
class Daemon
{

    protected $loop;
    protected $port = 20001;

    public function __construct($port=null)
    {
        $this->loop = \React\EventLoop\Factory::create();
        if ($port !== null) { $this->port = $port; }
    }

    public function run()
    {
        // add periodic tasks
        $this->loop->addPeriodicTimer(2, array($this, 'runPeriodicTasks'));

        // listen for new socket connections
        $socket = new \React\Socket\Server($this->loop);
        $socket->on('connection', array($this, 'handleNewConnection'));
        $socket->listen($this->port);

        // run loop
        $this->loop->run();
    }

    public function runPeriodicTasks() {
        // Logger::log("periodic");
    }

    public function handleNewConnection($conn) {
        $net_string = '';
        $conn->on('data', function ($data) use ($conn, &$net_string) {
          $net_string .= $data;
          if ($message_text = NetString::parse(trim($net_string))) {

            $cmd_array = json_decode($message_text, true);
            Logger::log("cmd_array: ".print_r($cmd_array, true));
            if ($cmd_array) {
                $response = \PHPIntel\Daemon\Dispatcher::dispatchCommand($cmd_array);
                $conn->write(json_encode($response));
            } else {

                $conn->write(json_encode(array('success' => false, 'msg' => 'could not parse command')));
            }

            $conn->end();
          }
        });
    }
}
