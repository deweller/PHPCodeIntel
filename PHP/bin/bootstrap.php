<?php

/**
 * include this file first from other files in the bin directory
 */


use PHPIntel\Logger\Logger;

// set the base path
$GLOBALS['BASE_PATH'] = dirname(__DIR__);

// bootstrap autoloader
require __DIR__.'/../lib/vendor/autoload.php';

// create a monolog log for debugging
Logger::init(Monolog\Logger::DEBUG);

