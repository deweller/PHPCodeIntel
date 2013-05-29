<?php

namespace PHPIntel\Context\Visitor;

use PHPIntel\Logger\Logger;
use PHPIntel\Context\Visitor\CurrentClassResolverVisitor;

/*
* SimpleClassNameResolverVisitor
* attempts to resolve the full class name of a simple class name
*/
class SimpleClassNameResolverVisitor extends CurrentClassResolverVisitor
{


    protected $resolved_class_name = null;

    protected $simple_class_name = null;
    protected $in_simple_class_name = 0;

    public function __construct($simple_class_name)
    {
        $this->simple_class_name = $simple_class_name;
    }

    public function getResolvedClassName()
    {
        return $this->resolved_class_name;
    }


    public function enterNode(\PHPParser_Node $node)
    {
        // $this->debugDumpNode($node, function($code) use ($node) { return "(before) Entering ".$node->getType().": $code"; });

        $node_type = $node->getType();

        // enterNode_before_
        switch ($node_type) {
            case 'Expr_ClassConstFetch':
                $method = "enterNode_before_".$node->getType();
                call_user_func(array($this, $method), $node);
                break;
        }

        parent::enterNode($node);

        // $this->debugDumpNode($node, function($code) use ($node) { return "Entering ".$node->getType().": $code"; });

        // enterNode_
        switch ($node->getType()) {
            case 'Name_FullyQualified':
                $method = "enterNode_".$node->getType();
                call_user_func(array($this, $method), $node);
                break;
        }

    }

    public function leaveNode(\PHPParser_Node $node)
    {
        parent::leaveNode($node);

        switch ($node->getType()) {
            // delegate to $this->leaveNode_Stmt_Class($node);
            case 'Expr_ClassConstFetch':
                $method = "leaveNode_".$node->getType();
                call_user_func(array($this, $method), $node);
                break;
        }
    }

    protected function enterNode_before_Expr_ClassConstFetch($node)
    {
        $simple_name = (string)$node->class;
        if ($simple_name == $this->simple_class_name) {
            $this->in_simple_class_name = 1;
        }
    }

    protected function leaveNode_Expr_ClassConstFetch($node)
    {
        $this->in_simple_class_name = 0;
    }


    protected function enterNode_Name_FullyQualified($node)
    {
        if ($this->in_simple_class_name) {
            $this->resolved_class_name = $node->toString();
        }
    }


}
