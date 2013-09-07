<?php

use \Exception;

class BaseClassOne {

    const SOME_CONSTANT = 'foo';

    public function methodBaseOne() { return 'blah'; }
    public function methodBaseTwo($var_one) { return 'blah'; }

    protected function methodProOne() { return 'blah'; }

    public static function staticOne() { return 'blah'; }

    // public static function testFunction() {
    // }

}
