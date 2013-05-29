<?php

namespace PHPIntel\Util;

use \Exception;

/*
* Timer
*/
class Timer
{

    private static $time_intervals = array(
      'hour'   => 3600000,
      'minute' => 60000,
      'second' => 1000
    );

    public function __construct()
    {
        $this->start_time = microtime(true);
    }

    public function start()
    {
        $this->start_time = microtime(true);
    }

    public function stop()
    {
        return microtime(true) - $this->start_time;
    }

    public function formatTimeAsSeconds($time)
    {
        $ms = round($time * 1000);

        foreach (self::$time_intervals as $unit => $value) {
            if ($ms >= $value) {
                $time = floor($ms / $value * 100.0) / 100.0;
                return $time . ' ' . ($time == 1 ? $unit : $unit . 's');
            }
        }

        return $ms . ' ms';
    }

    public function elapsedTime()
    {
        return $this->formatTimeAsSeconds(microtime(true) - $this->start_time);
    }

    public function resourceUsage()
    {
        return sprintf(
          'Time: %s, Memory: %4.2fMb',
          $this->elapsedTime(),
          memory_get_peak_usage(true) / 1048576
        );
    }
}