<?php

namespace PHPIntel\Daemon;

use PHPIntel\Dumper\SQLiteDumper;
use PHPIntel\Scanner\ProjectScanner;
use PHPIntel\Completions\Formatter;
use PHPIntel\Reader\SQLiteReader;
use PHPIntel\Intel\IntelBuilder;

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


    public static function executeCommand_scanFile($source_file, $sqlite_db_file) {
        $intel = new IntelBuilder();
        $entities = $intel->extractFromFile($source_file);

        $dumper = new SQLiteDumper($sqlite_db_file);
        $dumper->dump($entities);

        return self::successMessage();
    }

    public static function executeCommand_scanProject($include_dirs, $sqlite_db_file) {
        $dumper = new SQLiteDumper($sqlite_db_file);
        $intel = new IntelBuilder();
        $scanner = new ProjectScanner(array('include_dirs' => $include_dirs));
        $scanner->scanAndDumpProject($intel, $dumper);

        return self::successMessage();
    }

    public static function executeCommand_autoComplete($file_text, $sqlite_db_file) {
        $reader = new SQLiteReader($sqlite_db_file);
        $entities = $reader->read();

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
