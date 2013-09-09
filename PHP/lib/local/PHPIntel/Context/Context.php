<?php

namespace PHPIntel\Context;

use \ArrayObject;
use \Exception;

/*
* Context
*
* scope: static or instance
* entityType: 'member' (default) for methods, vars and constants.  'className' for class names, 'contructor' for constructors
* visibility: public, protected, private
* class: the fully qualified classname like Acme\Utils\MyClass
* prefix: beginning letters for the variable or method name
* variable: a PHP variable name that needs to resolve to a class name
* _is_parent: an internal variable
*
*/
class Context extends ArrayObject
{

    public function getParentContext($parent_class) {
        if (!$parent_class)
        {
            return null;
        } 

        $new_context = clone $this;
        switch ($this['visibility'])
        {
            case 'public':
                $new_context['visibility'] = 'public';
                break;

            default:
                $new_context['visibility'] = 'protected';
                break;
        }
        $new_context['class'] = $parent_class;

        $new_context['_is_parent'] = true;

        return $new_context;
    }
}
