<?php

namespace PHPIntel\Context\Visitor;

use PHPIntel\Logger\Logger;

/*
* VariableClassResolverVisitor
* attempts to resolve the class name of a variable
*/
class VariableClassResolverVisitor extends \PHPParser_NodeVisitor_NameResolver
{

    protected $resolved_class_name_for_variable = null;

    protected $current_class_name = null;
    protected $variable_name = null;
    protected $current_position = null;

    public function __construct($variable_name_with_dollar, $current_position)
    {
        $this->variable_name = substr($variable_name_with_dollar, 1);
        $this->current_position = $current_position;
    }

    public function getResolvedClassName()
    {
        return $this->resolved_class_name_for_variable;
    }

    public function enterNode(\PHPParser_Node $node)
    {
        parent::enterNode($node);


        switch ($node->getType()) {
            // delegate to $this->enterNode_Stmt_ClassMethod($node);
            case 'Expr_Assign':
                $method = "enterNode_".$node->getType();
                call_user_func(array($this, $method), $node);
                break;
        }


        // switch ($node->getType()) {
        //     // delegate to $this->enterNode_Stmt_ClassMethod($node);
        //     case 'Stmt_Class':
        //     case 'Stmt_ClassMethod':
        //         $method = "enterNode_".$node->getType();
        //         call_user_func(array($this, $method), $node);
        //         break;
        // }
    }

    // public function leaveNode(\PHPParser_Node $node)
    // {
    //     parent::leaveNode($node);

    //     // switch ($node->getType()) {
    //     //     // delegate to $this->leaveNode_Stmt_ClassMethod($node);
    //     //     case 'Stmt_Class':
    //     //         $method = "leaveNode_".$node->getType();
    //     //         call_user_func(array($this, $method), $node);
    //     //         break;
    //     // }
    // }

    protected function enterNode_Expr_Assign($node) {
        // $this->debugDumpNode($node, function($code) use ($node) { return "Entering ".$node->getType().": $code"; });
        // Logger::log("Expr_Assign...  node: ".print_r($node, true));

        // see if we are assigning to the variable name that we care about
        if ($node->var->name == $this->variable_name) {
            // resolve the expression
            //  this is a naive approach and will probably be replaced
            if (isset($node->expr)) {
                if (isset($node->expr->class)) {
                    if (isset($node->expr->class->parts)) {
                        $this->resolved_class_name_for_variable = $node->expr->class->parts[0];
                    }
                }
            }
        }
    }

    protected function enterNode_Stmt_Class($node) {
        $this->current_class_name = $node->namespacedName;
    } 

    protected function leaveNode_Stmt_Class($node) {
        $this->current_class_name = null;
    } 



    protected function debugDumpNode($node, $callback=null) {
        $printer = new \PHPParser_PrettyPrinter_Zend();
        $msg = $printer->prettyPrint(array($node));
        if ($callback) {
            $msg = $callback($msg);
        }
        Logger::log($msg);
    }




}
