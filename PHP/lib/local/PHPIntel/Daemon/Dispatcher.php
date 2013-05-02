<?php

namespace PHPIntel\Daemon;

use PHPIntel\Dumper\FlatFileDumper;
use PHPIntel\Completions\Formatter;
use PHPIntel\Reader\FlatFileReader;
use PHPIntel\FileIntelBuilder;

use \Exception;

/*
* Dispatcher
* executes command requests that come from the daemon
*/
class Dispatcher
{
    public function __construct()
    {
    }

    public static function dispatchCommand($cmd_array) {
        $cmd_name = $cmd_array['cmd'];
        $out = array();
        
        $out = call_user_func_array(array('self', 'executeCommand_'.$cmd_name), $cmd_array['args']);
        return $out;
    }


    public static function executeCommand_scanFile($source_file, $php_intel_file) {
        $intel = new FileIntelBuilder();
        $entities = $intel->extractFromFile($source_file);

        $dumper = new FlatFileDumper();
        $dumper->dump($entities, $php_intel_file);

        return self::successMessage();
    }

    public static function executeCommand_autoComplete($file_text, $php_intel_file) {
        $reader = new FlatFileReader();
        $entities = $reader->read($php_intel_file);

        $formatter = new Formatter();
        $completions = $formatter->formatEntitiesAsCompletions($entities);

        return self::successMessage($completions);
    }


    public static function successMessage($msg='ok') {
        $out = array('success' => true);
        $out['msg'] = $msg;
        return $out;
    }

}
