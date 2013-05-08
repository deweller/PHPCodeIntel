<?php

use PHPIntel\Scanner\ProjectScanner;
use PHPIntel\Test\EntityBuilder;
use PHPIntel\Intel\IntelBuilder;
use PHPIntel\Reader\SQLiteReader;
use PHPIntel\Dumper\SQLiteDumper;
use PHPIntel\Entity\Entity;

use \PHPUnit_Framework_Assert as PHPUnit;

class ScanProjectTest extends \PHPUnit_Framework_TestCase
{
    ////////////////////////////////////////////////////////////////////////
    // tests

    public function testScanProjectDirectory()
    {
        $test_sqlite_filepath = $GLOBALS['BASE_PATH'].'/test/data/sample_project/.test_intel.sqlite3';
        // clear old test file
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

        // read scanned dirs
        $reader = new SQLiteReader($test_sqlite_filepath);
        $read_entities = $reader->read();
        $expected_entities = EntityBuilder::buildTestEntities('project_entities.yaml');

        PHPUnit::assertEquals($expected_entities, $read_entities);

        // run again and make sure entities are cleared first and not double-added
        $scanner->scanAndDumpProject($intel, $dumper);
        $read_entities = $reader->read();
        PHPUnit::assertEquals($expected_entities, $read_entities);

        // clean up
        if (file_exists($test_sqlite_filepath)) { unlink($test_sqlite_filepath); }
    }




    ////////////////////////////////////////////////////////////////////////
    // util

}
