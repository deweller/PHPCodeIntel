<?php

use PHPIntel\FileIntelBuilder;
use PHPIntel\Entity\Entity;

use \PHPUnit_Framework_Assert as PHPUnit;

class IntelExtractorTest extends \PHPUnit_Framework_TestCase
{
    ////////////////////////////////////////////////////////////////////////
    // tests

    // note that this test doesn't do anything yet!

    public function testExtractMethodSignatures()
    {
        $intel = new FileIntelBuilder();
        $parsed_entities = $intel->extractFromFile($GLOBALS['BASE_PATH'].'/test/data/SimpleClassOne.php');

        $expected_entities = $this->buildTestEntities();

        PHPUnit::assertEquals($expected_entities, $parsed_entities);
    }

    ////////////////////////////////////////////////////////////////////////
    // util

    protected function buildTestEntities()
    {
        $test_entities = array();
        $entities_data = yaml_parse_file($GLOBALS['BASE_PATH'].'/test/yaml/entities.yaml');

        foreach ($entities_data['SimpleClassOne'] as $entities_entry) {
            $test_entities[] = new Entity($entities_entry);
        }

        return $test_entities;
    }
}
