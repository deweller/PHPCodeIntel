<?php

namespace PHPIntel\Context\Visitor;

use PHPIntel\Logger\Logger;

/*
* VariableClassResolverVisitor
* attempts to resolve the class name of a variable
*/
class CurrentClassResolverVisitor extends \PHPParser_NodeVisitor_NameResolver
{

    const STATE_NOT_IN_CLASS = 1;
    const STATE_IN_CLASS = 2;
    const STATE_EXITED_CLASS = 3;

    protected $last_class_name = null;
    protected $in_class_state = 1; // 1 = STATE_NOT_IN_CLASS

    public function getCurrentClassName() {
        if ($this->in_class_state == self::STATE_IN_CLASS OR $this->in_class_state == self::STATE_EXITED_CLASS) {
            return $this->last_class_name;
        }

        return null;
    }

    public function enterNode(\PHPParser_Node $node)
    {
        parent::enterNode($node);
        // $this->debugDumpNode($node, function($code) use ($node) { return "Entering ".$node->getType().": $code"; });

        if ($this->in_class_state == self::STATE_EXITED_CLASS AND $node->getType() != 'Stmt_Class') {
            $this->in_class_state = self::STATE_NOT_IN_CLASS;
        }

        switch ($node->getType()) {
            // delegate to $this->enterNode_Stmt_Class($node);
            case 'Stmt_Class':
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
            case 'Stmt_Class':
                $method = "leaveNode_".$node->getType();
                call_user_func(array($this, $method), $node);
                break;
        }
    }

    protected function enterNode_Stmt_Class($node)
    {
        // Logger::log("enterNode_Stmt_Class node->namespacedName=$node->namespacedName");
        $this->last_class_name = (string)$node->namespacedName;

        $this->in_class_state = self::STATE_IN_CLASS;
    } 

    protected function leaveNode_Stmt_Class($node)
    {
        $this->in_class_state = self::STATE_EXITED_CLASS;
    }



    protected function debugDumpNode($node, $callback=null)
    {
        $printer = new \PHPParser_PrettyPrinter_Zend();
        $msg = $printer->prettyPrint(array($node));
        if ($callback) {
            $msg = $callback($msg);
        }
        Logger::log($msg);
    }




}
