<?php

use PHPIntel\Context\ContextBuilder;
use PHPIntel\Logger\Logger;
use PHPIntel\Context\Lexer\LexerUtil;
use PHPIntel\Context\Parser\TolerantParser;
use PHPIntel\Context\Lexer\Lexer;

use \PHPUnit_Framework_Assert as PHPUnit;

class ContextBuilderTest extends \PHPUnit_Framework_TestCase
{
    ////////////////////////////////////////////////////////////////////////
    // tests

    public function testBuildPositionMap()
    {
        $php_code = <<<'EOT'
<?php
$a = new Flower();
$a->
EOT;

        $tokens = $this->getTokens($php_code);
        $position_map = LexerUtil::buildTokenPositionMap($tokens);

        $names_by_pos = LexerUtil::buildTokenDescriptionsByPosition($tokens, $position_map);
        $this->assertEquals(array(T_VARIABLE, '$a'), $names_by_pos[6]);
        $this->assertEquals(array(T_STRING, 'Flower'), $names_by_pos[15]);
        $this->assertEquals(array(T_OBJECT_OPERATOR, '->'), $names_by_pos[27]);
    }


    public function testResolveContextPosition()
    {
        $php_code = <<<'EOT'
<?php
$a = new Flower();
$a->abc
EOT;

        $tokens = $this->getTokens($php_code);
        $position_map = LexerUtil::buildTokenPositionMap($tokens);
        $names_by_offset = array_values($names_by_pos = LexerUtil::buildTokenDescriptionsByPosition($tokens, $position_map));

        $pos = strpos($php_code, '$a->abc') + 7;
        $offset = LexerUtil::findTokenOffsetByStringPosition($tokens, $position_map, $pos);

        $this->assertEquals(array(T_STRING,'abc'), $names_by_offset[$offset]);
        $this->assertEquals(array(T_OBJECT_OPERATOR,'->'), LexerUtil::buildTokenDescriptionArray($tokens[$offset-1]));
        $this->assertEquals(array(T_VARIABLE,'$a'), LexerUtil::buildTokenDescriptionArray($tokens[$offset-2]));
    }

    public function testContextBuilder()
    {
        $test_specs = yaml_parse_file($GLOBALS['BASE_PATH'].'/test/yaml/context/contexts.yaml');
        foreach($test_specs as $test_spec) {
            $this->validateContext($test_spec['php'], $test_spec['context']);
        }

    }

    ////////////////////////////////////////////////////////////////////////
    // util

    protected function getTokens($php_code) {
        $lexer = new Lexer();
        $parser = new TolerantParser($lexer);
        try {
            $stmts = $parser->parse($php_code);
        } catch (Exception $e) {
        }

        return $lexer->getTokens();
    }

    protected function validateContext($php_code, $expected_context_array) {
        $php_code = '<?php'.PHP_EOL.$php_code;
        $pos = strlen($php_code);
        $builder = new ContextBuilder();
        $context = $builder->buildContext($php_code, $pos);

        PHPUnit::assertEquals($expected_context_array, (array)$context);
    }

}

