<?php

namespace PHPIntel\Logger;

use \Exception;

/*
* Logger
* A singleton logger for debugging
*/
class Logger
{


    /**
     * @var Logger the global logger
     */
    static $LOGGER;

    /**
     * builds the default logger
     * @param int $default_level like Monolog::DEBUG
     */
    public static function init($default_level) {
        self::$LOGGER = new \Monolog\Logger('phpci');
        $handler = new \Monolog\Handler\StreamHandler($GLOBALS['BASE_PATH'].'/var/log/debug.log', $default_level);
        $handler->setFormatter(new \Monolog\Formatter\LineFormatter("[%datetime%] %level_name%: %message%\n"));
        self::$LOGGER->pushHandler($handler);
    }

    /**
     * logs to the default logger
     * @param string $message a message
     * @param int $level like Monolog::DEBUG
     */
    public static function log($message, $level=\Monolog\Logger::DEBUG) {
        if (!isset(self::$LOGGER)) { return; }

        self::$LOGGER->log($level, $message);
    }
}
