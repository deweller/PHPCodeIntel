<?php

use PHPIntel\FileIntelBuilder;

use \PHPUnit_Framework_Assert as PHPUnit;
use \Exception;

class AdminControllerTest extends \PHPUnit_Framework_TestCase {


  ////////////////////////////////////////////////////////////////////////
  // tests

  public function testExtractMethodSignatures() {
    $intel = new FileIntelBuilder();
    $intel->extractFromFile($GLOBALS['BASE_PATH'].'/test/data/SimpleClassOne.php');
  }



  ////////////////////////////////////////////////////////////////////////
  // util


}