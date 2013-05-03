<?php

use PHPIntel\FileIntelBuilder;

use \PHPUnit_Framework_Assert as PHPUnit;
use \Exception;

class IntelExtractorTest extends \PHPUnit_Framework_TestCase {


  ////////////////////////////////////////////////////////////////////////
  // tests

    // note that this test doesn't do anything yet!

  public function testExtractMethodSignatures() {
    $intel = new FileIntelBuilder();
    $intel->extractFromFile($GLOBALS['BASE_PATH'].'/test/data/SimpleClassOne.php');
  }



  ////////////////////////////////////////////////////////////////////////
  // util


}