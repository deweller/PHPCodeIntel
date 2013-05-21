<?php

use PHPIntel\Test\TestProject;
use PHPIntel\Reader\SQLiteReader;


use \PHPUnit_Framework_Assert as PHPUnit;

class InheritanceTest extends \PHPUnit_Framework_TestCase
{
    ////////////////////////////////////////////////////////////////////////
    // tests

    public function testInheritanceStorage()
    {
        $test_sqlite_filepath = TestProject::scan();

        // make sure files are added to inheritence chain
        $reader = new SQLiteReader($test_sqlite_filepath);
        $parent_class = $reader->getParentClass('Sub\ChildClassOne');
        PHPUnit::assertEquals('BaseClassOne', $parent_class);

        // TestProject::cleanup($test_sqlite_filepath);
    }




    ////////////////////////////////////////////////////////////////////////
    // util

}
