<?php

namespace Sub;

use \BaseClassOne;

use \Exception;

class ChildClassOne extends BaseClassOne {

    // override base
    public function methodBaseTwo($var_one) { return 'blah'; }

    public function methodChildOne() { return 'blah'; }

}
