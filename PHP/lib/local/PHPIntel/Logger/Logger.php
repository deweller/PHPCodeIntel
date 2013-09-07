<?php

namespace PHPIntel\Logger;

use \Monolog\Handler\StreamHandler;
use \Monolog\Formatter\LineFormatter;
use Monolog\Logger as Monolog;
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
        self::$LOGGER = new Monolog('phpci');
        if (!is_dir($GLOBALS['BASE_PATH'].'/var/log/')) {
            mkdir($GLOBALS['BASE_PATH'].'/var/log/', 0777, true);
        }
        $handler = new StreamHandler($GLOBALS['BASE_PATH'].'/var/log/debug.log', $default_level);
        $handler->setFormatter(new LineFormatter("[%datetime%] %level_name%: %message%\n"));
        self::$LOGGER->pushHandler($handler);
    }

    /**
     * logs to the default logger
     * @param string $message a message
     * @param int $level like Monolog::DEBUG
     */
    public static function log($message, $level=Monolog::DEBUG) {
        if (!isset(self::$LOGGER)) { return; }

        if ($message instanceof Exception) {
            $message = self::exceptionToText($message);
        }
        self::$LOGGER->log($level, $message);
    }

    public static function error($message)
    {
        self::log($message, Monolog::ERROR);
    }

    public static function exceptionToText($e) {
      return get_class($e).": ".rtrim($e->getMessage())."\n  at ".$e->getFile().":".$e->getLine();
    }
}
