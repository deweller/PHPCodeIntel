<?php

use PHPIntel\Project\Project;
use PHPIntel\Project\Scanner\ProjectScanner;
use PHPIntel\Test\EntityUtil;
use PHPIntel\Logger\Logger;
use PHPIntel\Test\TestProject;
use PHPIntel\Test\EntityBuilder;
use PHPIntel\Intel\IntelBuilder;
use PHPIntel\Reader\SQLiteReader;
use PHPIntel\Dumper\SQLiteDumper;
use PHPIntel\Entity\IntelEntity;

use \PHPUnit_Framework_Assert as PHPUnit;

class ScanProjectTest extends \PHPUnit_Framework_TestCase
{
    ////////////////////////////////////////////////////////////////////////
    // tests

    public function testScanProjectDirectory()
    {

        $test_sqlite_filepath = TestProject::scan();
        
        // read scanned dirs
        $reader = new SQLiteReader($test_sqlite_filepath);

        // check entities
        $read_entities = $reader->getAllEntities();
        $expected_entities = EntityBuilder::buildTestEntities('project_entities.yaml');
        PHPUnit::assertEquals(EntityUtil::sortedEntities($expected_entities, 'name', 'class'), EntityUtil::sortedEntities($read_entities, 'name', 'class'));

        // check classes
        $read_classes = $reader->getAllClasses();
        $expected_classes = EntityBuilder::buildTestClassEntities('project_class_entities.yaml');
        PHPUnit::assertEquals(EntityUtil::sortedEntities($expected_classes, 'name'), EntityUtil::sortedEntities($read_classes, 'name'));


        ////////////////////////////////////////////////////////////////////////
        // run again and make sure entities are cleared first and not double-added

        $test_sqlite_filepath = $GLOBALS['BASE_PATH'].'/test/data/sample_project/.test_intel.sqlite3';
        $dumper = new SQLiteDumper($test_sqlite_filepath);
        $intel = new IntelBuilder();
        $scanner = new ProjectScanner(new Project(array(
            'scan_dirs' => array(
                $GLOBALS['BASE_PATH'].'/test/data/sample_project/lib',
                $GLOBALS['BASE_PATH'].'/test/data/sample_project/vendor',
            ),
        )));
        $scanner->scanAndDumpProject($intel, $dumper);

        // check entities
        $read_entities = $reader->getAllEntities();
        PHPUnit::assertEquals(EntityUtil::sortedEntities($expected_entities, 'name', 'class'), EntityUtil::sortedEntities($read_entities, 'name', 'class'));

        // check classes
        $read_classes = $reader->getAllClasses();
        PHPUnit::assertEquals(EntityUtil::sortedEntities($expected_classes, 'name'), EntityUtil::sortedEntities($read_classes, 'name'));



        // clean up
        TestProject::cleanup($test_sqlite_filepath);
    }




    ////////////////////////////////////////////////////////////////////////
    // util

}
