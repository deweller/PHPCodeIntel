<?php

namespace PHPIntel\Context;

use PHPIntel\Context\Parser\TolerantParser;
use PHPIntel\Context\Visitor\SimpleClassNameResolverVisitor;
use PHPIntel\Context\Visitor\CurrentClassResolverVisitor;
use PHPIntel\Context\Visitor\VariableClassResolverVisitor;
use PHPIntel\Context\Lexer\LexerUtil;
use PHPIntel\Context\Lexer\Lexer;
use PHPIntel\Context\Context;

use PHPIntel\Logger\Logger;

use \Exception;

/*
* ContextBuilder
*/
class ContextBuilder
{
    public function __construct()
    {
    }

    public function buildContext($full_php_content, $current_position)
    {
        $php_content = $this->stripPHPContentAfterPosition($full_php_content, $current_position);

        $lexer = new Lexer();
        $parser = new TolerantParser($lexer);

        try {
            // parse into statements
            $statements = $parser->parse($php_content);

        } catch (Exception $e) {
            Logger::error($e);
        }

        $tokens = $lexer->getTokens();
        $position_map = LexerUtil::buildTokenPositionMap($tokens);
        
        $context = $this->resolveContext($tokens, $position_map, $current_position, $statements);
        return $context;
    }



    public function resolveContext($tokens, $position_map, $str_position, $statements)
    {
        $token_offset = LexerUtil::findTokenOffsetByStringPosition($tokens, $position_map, $str_position);
        if ($token_offset < 2) { return null; }

        // build a string of the last three tokens
        $token_0 = LexerUtil::buildTokenDescriptionArray($tokens[$token_offset - 2]);
        $token_1 = LexerUtil::buildTokenDescriptionArray($tokens[$token_offset - 1]);
        $token_2 = LexerUtil::buildTokenDescriptionArray($tokens[$token_offset - 0]);
        // Logger::log("tokens are  0)".token_name($token_0[0]).":".$token_0[1]." 1)".token_name($token_1[0]).":".$token_1[1]." 2)".token_name($token_2[0]).":".$token_2[1]."");

        $context_data = array();
        switch (true) {

            // Classname::something
            case $token_0[0] == T_STRING AND $token_1[0] == T_DOUBLE_COLON AND $token_2[0] = T_STRING:
                $context_data['scope']      = 'static';
                $context_data['class']      = $this->resolveStaticClassName($token_0[1], $statements);
                $context_data['visibility'] = $this->resolveVisibilityForStaticClass($context_data['class'], $statements);
                $context_data['prefix']     = $token_2[1];
                break;

            // Classname::
            case $token_1[0] == T_STRING AND $token_2[0] == T_DOUBLE_COLON:
                $context_data['scope']      = 'static';
                $context_data['class']      = $this->resolveStaticClassName($token_1[1], $statements);
                $context_data['visibility'] = $this->resolveVisibilityForStaticClass($context_data['class'], $statements);
                $context_data['prefix']     = '';
                break;

            // $a->something
            case $token_0[0] == T_VARIABLE AND $token_1[0] == T_OBJECT_OPERATOR AND $token_2[0] = T_STRING:
                $context_data['scope']      = 'instance';
                $context_data['variable']   = $token_0[1];
                $context_data['visibility'] = ($context_data['variable'] == '$this' ? 'private' : 'public');
                $context_data['prefix']     = $token_2[1];
                $context_data['class']      = $this->resolveClassForVariable($context_data['variable'], $statements);
                break;

            // $a->
            case $token_1[0] == T_VARIABLE AND $token_2[0] == T_OBJECT_OPERATOR:
                $context_data['scope']      = 'instance';
                $context_data['variable']   = $token_1[1];
                $context_data['visibility'] = ($context_data['variable'] == '$this' ? 'private' : 'public');
                $context_data['prefix']     = '';
                $context_data['class']      = $this->resolveClassForVariable($context_data['variable'], $statements);
                break;
            
            default:
                break;
        }

        if ($context_data) { return new Context($context_data); }

        return null;
    }


    protected function resolveClassForVariable($variable, $statements)
    {
        if ($variable === '$this') {
            return $this->getCurrentClassName($statements);
        }

        // get the class name assigned to this variable
        $visitor = new VariableClassResolverVisitor($variable);
        $this->traverseStatements($visitor, $statements);
        return $visitor->getResolvedClassName();
    }

    protected function resolveStaticClassName($simple_class_name, $statements) {
        if ($simple_class_name == 'self') {
            // resolve self to the current class
            // unimplemented
        }

        // get the fully resolved class name for $simple_class_name
        $visitor = new SimpleClassNameResolverVisitor($simple_class_name);
        $this->traverseStatements($visitor, $statements);
        $resolved_class_name = $visitor->getResolvedClassName();
        if ($resolved_class_name) {
            return $resolved_class_name;
        }

        // could not resolve
        return $simple_class_name;
    }

    protected function resolveVisibilityForStaticClass($class_name, $statements) {
        // get the current class name
        $current_class_name = $this->getCurrentClassName($statements);

        if ($current_class_name == $class_name) {
            return 'private';
        }

        return 'public';
    }

    protected function getCurrentClassName($statements) {
        $visitor = new CurrentClassResolverVisitor($variable);
        $this->traverseStatements($visitor, $statements);

        return $visitor->getCurrentClassName();
    }


    protected function traverseStatements($visitor, $statements) {
        // visit all the nodes and collect data
        $traverser = new \PHPParser_NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);
    }


    // append a semicolon and enough closing braces to balance out the code
    protected function stripPHPContentAfterPosition($full_php_content, $current_position)
    {
        $stripped_php_content = substr($full_php_content, 0, $current_position);

        $tokens = token_get_all($stripped_php_content);
        // Logger::log("tokens: ".print_r($tokens, true));

        $brace_stack = 0;
        foreach($tokens as $token) {
            if (is_string($token)) {
                if ($token == '{') {
                    ++$brace_stack;
                }
                if ($token == '}') {
                    --$brace_stack;
                    if ($brace_stack < 0) { throw new Exception("Unbalanced brace count found.", 1); }
                }
            }
        }

        // if it ends in a -> or ::, then add a trailing character
        $ending_chars = substr($stripped_php_content, -2);
        if ($ending_chars == '->' OR $ending_chars == '::') {
            $magic_prefix = 'x';
            $stripped_php_content .= $magic_prefix;
        }

        $closed_php_content = $stripped_php_content.';';

        // add trailing braces
        for ($i=$brace_stack; $i > 0; $i--) { 
            $closed_php_content .= '}';
        }

        // Logger::log("closed_php_content=\n$closed_php_content");


        return $closed_php_content;
    }

}
