<?php

namespace PHPIntel\Context\Visitor;

use PHPIntel\Logger\Logger;

/*
* ContextBuilderVisitor
* collects interesting entities from a php class file
*/
class ContextBuilderVisitor extends \PHPParser_NodeVisitor_NameResolver
{

    protected $current_class_name = null;

    public function enterNode(\PHPParser_Node $node)
    {
        parent::enterNode($node);

        // $this->debugDumpNode($node, function($code) use ($node) { return "Entering ".$node->getType().": $code"; });


        // switch ($node->getType()) {
        //     // delegate to $this->enterNode_Stmt_ClassMethod($node);
        //     case 'Stmt_Class':
        //     case 'Stmt_ClassMethod':
        //         $method = "enterNode_".$node->getType();
        //         call_user_func(array($this, $method), $node);
        //         break;
        // }
    }

    public function leaveNode(\PHPParser_Node $node)
    {
        parent::leaveNode($node);

        // switch ($node->getType()) {
        //     // delegate to $this->leaveNode_Stmt_ClassMethod($node);
        //     case 'Stmt_Class':
        //         $method = "leaveNode_".$node->getType();
        //         call_user_func(array($this, $method), $node);
        //         break;
        // }
    }

    protected function enterNode_Stmt_ClassMethod($node) {
        $function_name = $node->name;
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
