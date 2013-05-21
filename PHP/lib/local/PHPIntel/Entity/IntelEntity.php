<?php

namespace PHPIntel\Entity;

use \ArrayObject;
use \Exception;

/*
* Entity
* represents an interesting entity in a class, like a method, constant or variable
*
* Entities look like this
*   label: visible function name, like getNameByID
*   completion: the full method signature with function name and variable parameters like getNameByID($id)
*   filepath: the complete filepath
*   class: the fully qualified classname like Acme\Utils\MyClass
*   type: method, variable, constant
*   visibility: public, protected, private
*   scope: instance or static
*
*/
class IntelEntity extends ArrayObject
{

}
