<?php

namespace PHPIntel\Project;

use \ArrayObject;

use \Exception;

/*
* Project
* represents a project
*
* Projects look like this
*   name: visible function name, like getNameByID
*   completion: the full method signature with function name and variable parameters like getNameByID($id)
*   filepath: the complete filepath
*   class: the fully qualified classname like Acme\Utils\MyClass
*   type: method, variable, constant
*   visibility: public, protected, private
*   scope: instance or static
*/
class Project extends ArrayObject
{

    public function getSQLiteDBFilepath() {
        return $this['db_file'];
    }

}
