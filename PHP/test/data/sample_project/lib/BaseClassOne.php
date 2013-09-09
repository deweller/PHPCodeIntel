<?php

use \Exception;

class BaseClassOne {

    const SOME_CONSTANT = 'foo';

    protected $foo_prop = null;

    public function __construct($foo_prop) {
        $this->foo_prop = $foo_prop;
    }

    public function methodBaseOne() { return 'blah'; }
    public function methodBaseTwo($var_one) { return 'blah'; }

    protected function methodProOne() { return 'blah'; }

    public static function staticOne() { return 'blah'; }

    // public static function testFunction() {
    // }

}
