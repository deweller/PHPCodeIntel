#!/usr/local/bin/php
<?php 

/**
 * Dumps intel entities for a single file
 * Usage: ./dump_intel.php <php_file_in> <data_file_out>
 */

use PHPIntel\FileIntelBuilder;
use PHPIntel\Dumper\FlatFileDumper;

require dirname(__DIR__).'/bootstrap.php';

$source_file = $_SERVER['argv'][0];
$dest_file = $_SERVER['argv'][1];
if (!strlen($source_file)) { throw new Exception("source file not found.", 1); }

$intel = new FileIntelBuilder();
$entities = $intel->extractFromFile($source_file);

$dumper = new FlatFileDumper();
$dumper->dump($entities, $dest_file);
