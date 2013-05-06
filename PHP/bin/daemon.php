#!/usr/local/bin/php
<?php

/**
 * runs the daemon that listens to requests sent from sublime
 * hard-coded to port 20001 for now
 */

use PHPIntel\Logger\Logger;
use PHPIntel\Daemon\Daemon;

require __DIR__.'/bootstrap.php';

$port = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : 20001;

Logger::log('starting daemon on port '.$port);
$daemon = new Daemon($port);
$daemon->run();

