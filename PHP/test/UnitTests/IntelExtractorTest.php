<?php

use PHPIntel\Entity\IntelEntity;
use PHPIntel\Intel\IntelBuilder;
use PHPIntel\Logger\Logger;
use PHPIntel\Test\EntityBuilder;
use \PHPUnit_Framework_Assert as PHPUnit;

class IntelExtractorTest extends \PHPUnit_Framework_TestCase
{
    ////////////////////////////////////////////////////////////////////////
    // tests

    public function testExtractMethodSignatures()
    {

        $intel = new IntelBuilder();
        $parsed_entity_collection = $intel->extractFromFile($GLOBALS['BASE_PATH'].'/test/data/SimpleClassOne.php');

        $expected_entities = EntityBuilder::buildTestEntities('entities.yaml');

        PHPUnit::assertEquals($expected_entities, $parsed_entity_collection['entities']);
    }

    ////////////////////////////////////////////////////////////////////////
    // util

}
