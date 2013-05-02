<?php

use \Exception;

class SimpleClassOne {

  public function functionOne() { return 'blah'; }
  public function functionTwo($var_one) { return 'blah'; }
  public function functionThree($var_one, $var_two='default') { return 'blah'; }
  public function functionFour($var_one, &$ref_var_two, array $var_three_array) { return 'blah'; }
  public function functionFive($var_one, SimpleClassOne $class_two) { return 'blah'; }
  public function functionSix($var_one=null) { return 'blah'; }

}

// view.run_command("php_code_intel_scan_file")

// Test me:
// $a = new SimpleClassOne();
// $a->  // <== trigger autocomplete here

