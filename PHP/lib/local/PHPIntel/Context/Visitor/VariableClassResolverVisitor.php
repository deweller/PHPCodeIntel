<?php

namespace PHPIntel\Context\Visitor;

use PHPIntel\Logger\Logger;
use PHPIntel\Context\Visitor\CurrentClassResolverVisitor;

/*
* VariableClassResolverVisitor
* attempts to resolve the class name of a variable
*/
class VariableClassResolverVisitor extends CurrentClassResolverVisitor
{


    protected $resolved_class_name_for_variable = null;

    protected $variable_name = null;
    protected $in_variable_stack = 0;

    public function __construct($variable_name_with_dollar)
    {
        $this->variable_name = substr($variable_name_with_dollar, 1);
    }

    public function getResolvedClassName()
    {
        return $this->resolved_class_name_for_variable;
    }


    public function enterNode(\PHPParser_Node $node)
    {
        parent::enterNode($node);

        // $this->debugDumpNode($node, function($code) use ($node) { return "Entering ".$node->getType().": $code"; });

        switch ($node->getType()) {
            // delegate to $this->enterNode_Stmt_Class($node);
            case 'Param':
            case 'Expr_Assign':
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
            case 'Param':
            case 'Expr_Assign':
                $method = "leaveNode_".$node->getType();
                call_user_func(array($this, $method), $node);
                break;
        }
    }

    protected function enterNode_Param($node)
    {
        // see if we are assigning to the variable name that we care about
        if ($node->name == $this->variable_name) {
            // watch for a Name_FullyQualified
            ++$this->in_variable_stack;
        }
    }

    protected function enterNode_Expr_Assign($node)
    {
        // see if we are assigning to the variable name that we care about
        if ($node->var->name == $this->variable_name) {
            // watch for a Name_FullyQualified
            ++$this->in_variable_stack;
        }
    }

    protected function enterNode_Name_FullyQualified($node)
    {
        if ($this->in_variable_stack) {
            $this->resolved_class_name_for_variable = $node->toString();
        }
    }

    protected function leaveNode_Param($node)
    {
        if ($this->in_variable_stack) {
            --$this->in_variable_stack;
        }
    }

    protected function leaveNode_Expr_Assign($node)
    {
        if ($this->in_variable_stack) {
            --$this->in_variable_stack;
        }
    }

}
