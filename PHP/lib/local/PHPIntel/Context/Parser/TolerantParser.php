<?php

namespace PHPIntel\Context\Parser;

use \PHPParser_Parser;
use \PHPParser_Error;
use PHPIntel\Logger\Logger;
use \Exception;

/*
* TolerantParser
*/
class TolerantParser extends PHPParser_Parser
{

    // need to try things like adding a missing semicolon...
    

    public function parse($code)
    {
        try {
            return parent::parse($code);
        } catch (\PHPParser_Error $e) {
            // echo "this->yyval dump:\n";
            // print_r($this->yyval);

            // echo "this->yyastk dump:\n";
            // print_r($this->yyastk);
            // Logger::log("this->yyastk dump: ".print_r($this->yyastk, true));

            // got an error, but we will return the statements anyway
            Logger::log($e);

            return $this->yyastk;
        }
    }

    


}
