<?php

namespace PHPIntel\Context;

use \ArrayObject;
use \Exception;

/*
* Context
*
* scope: static or instance
* visibility: public, protected, private
* class: the fully qualified classname like Acme\Utils\MyClass
* prefix: beginning letters for the variable or method name
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

        return $new_context;
    }
}
