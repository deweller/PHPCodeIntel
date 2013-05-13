<?php

use PHPIntel\Dumper\SQLiteDumper;
use PHPIntel\Logger\Logger;
use PHPIntel\Test\EntityBuilder;
use PHPIntel\Context\Context;
use PHPIntel\Scanner\ProjectScanner;
use PHPIntel\Intel\IntelBuilder;
use PHPIntel\Reader\SQLiteReader;
use PHPIntel\Entity\Entity;

use \PHPUnit_Framework_Assert as PHPUnit;

class SQLiteLookupTest extends \PHPUnit_Framework_TestCase
{
    ////////////////////////////////////////////////////////////////////////
    // tests

    public function testLookup()
    {
        // setup
        $test_sqlite_filepath = $this->scanTestProject();

        $context = new Context(array(
          'scope'  => 'static',
          'class'  => 'BaseClassOne',
          'prefix' => '',
        ));
        $reader = new SQLiteReader($test_sqlite_filepath);
        $read_entities = $reader->lookupByContext($context);
        PHPUnit::assertNotEmpty($read_entities);
        PHPUnit::assertEquals($this->findExpectedProjectEntities($context), $read_entities);

        // clean up
        $this->cleanupScanData();
    }

    ////////////////////////////////////////////////////////////////////////
    // util

    protected function scanTestProject()
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

    protected function cleanupScanData()
    {
        // clean up
        if (file_exists($test_sqlite_filepath)) { unlink($test_sqlite_filepath); }
    }

    protected function findExpectedProjectEntities($context) {
        $scope = $context['scope'];
        $class = $context['class'];

        if (!isset($this->project_entities_by_scope_and_class)) {
            $this->project_entities_by_scope_and_class = array();

            foreach (EntityBuilder::buildTestEntities('project_entities.yaml') as $entity) {
                $this->project_entities_by_scope_and_class[$entity['scope'].':'.$entity['class']][] = $entity;
            }
        }

        return $this->project_entities_by_scope_and_class["{$scope}:{$class}"];
    }

}
