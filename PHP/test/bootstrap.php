<?php

use PHPIntel\Logger\Logger;

$GLOBALS['BASE_PATH'] = dirname(__DIR__);

# bootstrap
require __DIR__.'/../lib/vendor/autoload.php';

// create a monolog log for debugging
Logger::init(Monolog\Logger::DEBUG);

