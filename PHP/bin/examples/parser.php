#!/usr/local/bin/php
<?php 

use PHPIntel\Context\Parser\TolerantParser;
use PHPIntel\Logger\Logger;
use PHPIntel\Context\Visitor\VariableClassResolverVisitor;
use PHPIntel\Context\Lexer\Lexer;

/**
 * test the parser
 */


require dirname(__DIR__).'/bootstrap.php';

$php_content = <<<'EOT'
<?php
class MyClass {
    public function myMethod() {
        $this->something
    }
}
EOT;


$lexer = new Lexer();
$parser = new TolerantParser($lexer);

try {
    // parse into statements
    $statements = $parser->parse($php_content);
    Logger::log("statements=".print_r($statements, true));

} catch (Exception $e) {
    Logger::error($e);
}

// the collector will visit all the nodes and collect data
$visitor = new VariableClassResolverVisitor($variable, $current_position);
$traverser = new \PHPParser_NodeTraverser();
$traverser->addVisitor($visitor);
$traverser->traverse($statements);

