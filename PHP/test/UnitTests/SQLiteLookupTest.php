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

    public function testLookups()
    {
        // setup
        $test_sqlite_filepath = $this->scanTestProject();

        $lookup_specs = yaml_parse_file($GLOBALS['BASE_PATH'].'/test/yaml/lookup/lookups.yaml');
        foreach($lookup_specs as $lookup_spec) {
            $this->doLookup($test_sqlite_filepath, $lookup_spec['context'], $lookup_spec['completions']);
        }

        // clean up
        $this->cleanupScanData($test_sqlite_filepath);
    }

    ////////////////////////////////////////////////////////////////////////
    // util

    public function doLookup($sqlite_filepath, $context_data, $expected_completions)
    {
        $context = new Context($context_data);
        $reader = new SQLiteReader($sqlite_filepath);

        $actual_completions = array();
        $read_entities = $reader->lookupByContext($context);
        foreach($read_entities as $read_entity) {
            $actual_completions[] = $read_entity['completion'];
        }

        PHPUnit::assertEquals($expected_completions, $actual_completions);
    }

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
