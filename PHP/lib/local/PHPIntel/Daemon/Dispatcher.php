<?php

namespace PHPIntel\Daemon;

use PHPIntel\Dumper\SQLiteDumper;
use PHPIntel\Project\Status\ProjectStatus;
use PHPIntel\Project\Project;
use PHPIntel\Context\ContextBuilder;
use PHPIntel\Logger\Logger;
use PHPIntel\Project\Scanner\ProjectScanner;
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


    public static function executeCommand_scanFile($source_file, $scan_dirs, $exclude_patterns, $sqlite_db_file) {
        // Logger::log("scan_dirs:".print_r($scan_dirs, true));
        $project = new Project(array(
            'scan_dirs'        => $scan_dirs,
            'exclude_patterns' => $exclude_patterns,
            'db_file'          => $sqlite_db_file
        ));

        $intel = new IntelBuilder();
        $dumper = new SQLiteDumper($sqlite_db_file);

        // if the project has never been scanned or has not been re-scanned for a while
        //   then scan it now
        $status = new ProjectStatus($project);
        if ($status->shouldRescanProject()) {
            // rescan the entire project
            $scanner = new ProjectScanner($project);
            $scanner->scanAndDumpProject($intel, $dumper);
            $status->updateLastScanTime();

        } else {
            // just update this file
            $entity_collection = $intel->extractFromFile($source_file);
            $dumper->replaceEntitiesInFile($entity_collection, $source_file);
        }

        return self::successMessage();
    }

    public static function executeCommand_scanProject($scan_dirs, $exclude_patterns, $sqlite_db_file) {
        Logger::log("scan_dirs: ".print_r($scan_dirs, true));
        $project = new Project(array(
            'scan_dirs'        => $scan_dirs,
            'exclude_patterns' => $exclude_patterns,
            'db_file'          => $sqlite_db_file
        ));

        $dumper = new SQLiteDumper($sqlite_db_file);
        $intel = new IntelBuilder();

        $scanner = new ProjectScanner($project);
        $scanner->scanAndDumpProject($intel, $dumper);

        $status = new ProjectStatus($project);
        $status->updateLastScanTime();

        return self::successMessage();
    }

    public static function executeCommand_autoComplete($php_content, $current_position, $sqlite_db_file) {
        $builder = new ContextBuilder();
        $context = $builder->buildContext($php_content, $current_position);
        // Logger::log("context: ".print_r((array)$context, true));

        if ($context) {
            $reader = new SQLiteReader($sqlite_db_file);
            $entities = $reader->lookupByContext($context);

            $formatter = new Formatter();
            $completions = $formatter->formatEntitiesAsCompletions($entities);
        } else {
            $completions = array();
        }

        return self::successMessage($completions);
    }

    public static function executeCommand_debugSleep($time) {
        Logger::log("sleeping for $time");
        sleep($time);
        Logger::log("done sleeping for $time");

        return self::successMessage();
    }


    public static function successMessage($msg='ok') {
        $out = array('success' => true);
        $out['msg'] = $msg;
        return $out;
    }

}

