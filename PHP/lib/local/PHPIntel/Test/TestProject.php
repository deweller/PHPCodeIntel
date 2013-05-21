<?php

namespace PHPIntel\Test;

use PHPIntel\Entity\IntelEntity;
use PHPIntel\Scanner\ProjectScanner;
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
        // clear old test file
        $test_sqlite_filepath = $GLOBALS['BASE_PATH'].'/test/data/sample_project/.test_intel.sqlite3';
        if (file_exists($test_sqlite_filepath)) { unlink($test_sqlite_filepath); }

        $dumper = new SQLiteDumper($test_sqlite_filepath);

        $intel = new IntelBuilder();
        $scanner = new ProjectScanner(array(
            'include_dirs' => array(
                $GLOBALS['BASE_PATH'].'/test/data/sample_project/lib',
                $GLOBALS['BASE_PATH'].'/test/data/sample_project/vendor',
            ),
        ));
        $scanner->scanAndDumpProject($intel, $dumper);

        return $test_sqlite_filepath;
    }


    public static function cleanup($test_sqlite_filepath)
    {
        // clean up
        if (file_exists($test_sqlite_filepath)) { unlink($test_sqlite_filepath); }
    }

}