<?php

namespace PHPIntel\Context\Parser;

use \PHPParser_Parser;
use PHPIntel\Logger\Logger;
use \Exception;

/*
* TolerantParser
*/
class TolerantParser extends PHPParser_Parser
{

    public function parse($code)
    {
        try {
            return parent::parse($code);
        } catch (\PHPParser_Error $e) {
            // echo "this->yyval dump:\n";
            // print_r($this->yyval);

            // echo "this->yyastk dump:\n";
            // print_r($this->yyastk);
            // got an error, but we will return the statements anyway
            Logger::log($e);

            return $this->yyastk;
        }
    }

    

}
