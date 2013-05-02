#!/usr/local/bin/php
<?php 

/**
 * shows how the scanner works
 */


use PHPIntel\Daemon\Dispatcher;

require dirname(__DIR__).'/bootstrap.php';

$file_text = '';
$php_intel_file = $GLOBALS['BASE_PATH'].'/test/data/.php_intel_data';
$completions = Dispatcher::executeCommand_autoComplete($file_text, $php_intel_file);
echo "completions dump:\n";
print_r($completions);
