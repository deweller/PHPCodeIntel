#!/usr/local/bin/php
<?php 


/**
 * Dumps intel entities for the SimpleClassOne.php file into ./testdump.txt
 */

use PHPIntel\FileIntelBuilder;
use PHPIntel\Dumper\FlatFileDumper;

require dirname(__DIR__).'/bootstrap.php';

$intel = new FileIntelBuilder();
$entities = $intel->extractFromFile($GLOBALS['BASE_PATH'].'/test/data/SimpleClassOne.php');

$dumper = new FlatFileDumper();
$dumper->dump($entities, './testdump.txt');
