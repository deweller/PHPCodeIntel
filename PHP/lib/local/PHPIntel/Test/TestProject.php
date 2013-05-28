<?php

namespace PHPIntel\Test;

use PHPIntel\Entity\IntelEntity;
use PHPIntel\Project\Project;
use PHPIntel\Project\Scanner\ProjectScanner;
use PHPIntel\Intel\IntelBuilder;
use PHPIntel\Dumper\SQLiteDumper;

use \Exception;

/*
* TestProject
* $test_sqlite_filepath = TestProject::scan();
* TestProject::cleanup($test_sqlite_filepath);
*/
class TestProject
{

    public static function scan()
    {
        $project = self::getTestProject();
        $test_sqlite_filepath = $project['db_file'];

        // clear old test file
        if (file_exists($test_sqlite_filepath)) { unlink($test_sqlite_filepath); }

        $dumper = new SQLiteDumper($test_sqlite_filepath);

        $intel = new IntelBuilder();
        $scanner = new ProjectScanner($project);
        $scanner->scanAndDumpProject($intel, $dumper);

        return $test_sqlite_filepath;
    }

    public static function getTestProject() {
        return new Project(array(
            'db_file'   => $GLOBALS['BASE_PATH'].'/test/data/sample_project/.test_intel.sqlite3',
            'scan_dirs' => array(
                $GLOBALS['BASE_PATH'].'/test/data/sample_project/lib',
                $GLOBALS['BASE_PATH'].'/test/data/sample_project/vendor',
            ),
        ));
    }


    public static function cleanup($test_sqlite_filepath)
    {
        // clean up
        if (file_exists($test_sqlite_filepath)) { unlink($test_sqlite_filepath); }
    }

}