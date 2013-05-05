<?php

use PHPIntel\Scanner\ProjectScanner;
use PHPIntel\FileIntelBuilder;
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

        $intel = new FileIntelBuilder();
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
        $expected_entities = $this->buildTestEntities();

        PHPUnit::assertEquals($expected_entities, $read_entities);

        // clean up
        if (file_exists($test_sqlite_filepath)) { unlink($test_sqlite_filepath); }
    }

    ////////////////////////////////////////////////////////////////////////
    // util

    protected function buildTestEntities()
    {
        $test_entities = array();
        $entities_data = yaml_parse_file($GLOBALS['BASE_PATH'].'/test/yaml/project_entities.yaml');

        foreach ($entities_data['project'] as $entities_entry) {
            $test_entities[] = new Entity($entities_entry);
        }

        return $test_entities;
    }
}
