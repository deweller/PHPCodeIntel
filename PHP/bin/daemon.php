#!/usr/local/bin/php
<?php

/**
 * runs the daemon that listens to requests sent from sublime
 * hard-coded to port 20001 for now
 */

use PHPIntel\Logger\Logger;
use PHPIntel\Daemon\Daemon;

require __DIR__.'/bootstrap.php';

Logger::log('starting daemon');
$daemon = new Daemon(20001);
$daemon->run();

