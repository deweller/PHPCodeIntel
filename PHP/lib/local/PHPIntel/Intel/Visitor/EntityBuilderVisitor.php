<?php

namespace PHPIntel\Intel\Visitor;


use PHPIntel\Entity\IntelEntity;
use PHPIntel\Entity\ClassEntity;
use PHPIntel\Logger\Logger;

/*
* EntityBuilderVisitor
* collects interesting entities from a php class file
*/
class EntityBuilderVisitor extends \PHPParser_NodeVisitor_NameResolver
{

    protected $source_file = null;

    protected $intel_entities = array();
    protected $class_entities = array();

    protected $pretty_printer = null;
    protected $current_class_entity = null;


    public function __construct($source_file=null)
    {
        if ($source_file !== null) { $this->source_file = $source_file; }
    }

    public function enterNode(\PHPParser_Node $node)
    {
        parent::enterNode($node);
        // $this->debugDumpNode($node);

        switch ($node->getType()) {
            // delegate to $this->enterNode_Stmt_ClassMethod($node);
            case 'Stmt_Class':
            case 'Stmt_ClassMethod':
                $method = "enterNode_".$node->getType();
                call_user_func(array($this, $method), $node);
                break;
        }
    }

    public function leaveNode(\PHPParser_Node $node)
    {
        parent::leaveNode($node);

        switch ($node->getType()) {
            // delegate to $this->leaveNode_Stmt_ClassMethod($node);
            case 'Stmt_Class':
                $method = "leaveNode_".$node->getType();
                call_user_func(array($this, $method), $node);
                break;
        }
    }

    public function getIntelEntities() {
        return $this->intel_entities;
    }
    public function getClassEntities() {
        return $this->class_entities;
    }


    protected function enterNode_Stmt_ClassMethod($node) {
        $function_name = $node->name;

        $params_text = $this->buildParamsText($node->params);



        $this->intel_entities[] = new IntelEntity(array(
            'label'      => $function_name,
            'completion' => "{$function_name}({$params_text})",
            'filepath'   => $this->source_file,
            'class'      => $this->current_class_entity['name'],
            'type'       => 'method',
            'visibility' => $this->visibilityFromNode($node),
            'scope'      => $this->scopeFromMethodNode($node),
        ));
    }

    protected function enterNode_Stmt_Class($node) {
        $this->current_class_entity = new ClassEntity(array(
            'name'   => (string)$node->namespacedName,
            'parent' => isset($node->extends) ? (string)$node->extends : '',
        ));

        // Logger::log("class: ".print_r((array)$this->current_class_entity, true));
    } 

    protected function leaveNode_Stmt_Class($node) {
        $this->class_entities[] = $this->current_class_entity;
        $this->current_class_entity = null;
    } 




    protected function buildParamsText($params) {
        $all_params_text = '';

        $first_param = true;
        foreach($params as $param) {
            // $param is:
            //     'name'    => $name,
            //     'default' => $default,
            //     'type'    => $type,
            //     'byRef'   => $byRef
            $param_text = '';

            if ($param->byRef) {
                $param_text .= '&';
            }

            if (isset($param->type)) {
                $param_text .= $param->type.' ';
            }

            $param_text .= '$'.$param->name;

            if (isset($param->default)) {
                $expr = $this->getPrettyPrinter()->prettyPrintExpr($param->default);
                $param_text .= '='.$expr;
            }

            $all_params_text .= ($first_param ? '' : ', ').$param_text;

            $first_param = false;
        }

        return $all_params_text;
    }

    protected function getPrettyPrinter() {
        if (!isset($this->pretty_printer)) {
            $this->pretty_printer = new \PHPParser_PrettyPrinter_Zend();
        }
        return $this->pretty_printer;
    }

    protected function visibilityFromNode($node)
    {
        switch (true) {
            case $node->isPublic($node):
                return 'public';
            case $node->isProtected($node):
                return 'protected';
            case $node->isPrivate($node):
                return 'private';
        }

        return 'unknown';
    }

    protected function scopeFromMethodNode($node) {
        if ($node->isStatic($node)) {
            return 'static';
        }
        return 'instance';
    }

    protected function debugDumpNode($node, $callback=null)
    {
        $printer = new \PHPParser_PrettyPrinter_Zend();
        $msg = $printer->prettyPrint(array($node));
        if ($callback) {
            $msg = $callback($msg);
        }
        Logger::log(get_class($node)." ".$msg);
    }

}
