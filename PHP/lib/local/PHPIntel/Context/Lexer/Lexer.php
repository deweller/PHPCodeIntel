<?php

namespace PHPIntel\Context\Lexer;


use \PHPParser_Lexer;
use PHPIntel\Logger\Logger;
use \Exception;

/*
* Lexer
*/
class Lexer extends PHPParser_Lexer
{


    public function getTokens() {
        return $this->tokens;
    }




}
