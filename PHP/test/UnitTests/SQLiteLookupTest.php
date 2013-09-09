<?php

use PHPIntel\Context\Context;
use PHPIntel\Entity\ClassEntity;
use PHPIntel\Logger\Logger;
use PHPIntel\Reader\SQLiteReader;
use PHPIntel\Test\EntityBuilder;
use PHPIntel\Test\TestProject;
use \PHPUnit_Framework_Assert as PHPUnit;

class SQLiteLookupTest extends \PHPUnit_Framework_TestCase
{
    ////////////////////////////////////////////////////////////////////////
    // tests

    public function testLookups()
    {
        // setup
        $test_sqlite_filepath = TestProject::scan();


        $lookup_specs = yaml_parse_file($GLOBALS['BASE_PATH'].'/test/yaml/lookup/lookups.yaml');
        foreach($lookup_specs as $lookup_spec) {
            $this->doLookup($test_sqlite_filepath, $lookup_spec['context'], $lookup_spec['completions']);
        }

        // clean up
        TestProject::cleanup($test_sqlite_filepath);
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
            if ($read_entity instanceof ClassEntity) {
                $actual_completions[] = $read_entity['shortName'];
            } else {
                $actual_completions[] = $read_entity['completion'];
            }
        }

        PHPUnit::assertEquals($expected_completions, $actual_completions);
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
