#!/usr/local/bin/php
<?php 


/**
 * Dumps intel entities for the SimpleClassOne.php file into ./testdump.txt
 */

use PHPIntel\FileIntelBuilder;
use PHPIntel\Dumper\SQLiteDumper;

require dirname(__DIR__).'/bootstrap.php';

$sqlite_db_file = '/tmp/test_dump.sqlite3';

$intel = new FileIntelBuilder();
$entities = $intel->extractFromFile($source_file);

$dumper = new SQLiteDumper($sqlite_db_file);
$dumper->dump($entities);
