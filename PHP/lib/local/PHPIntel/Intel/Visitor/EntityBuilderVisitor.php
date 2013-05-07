<?php

namespace PHPIntel\Intel\Visitor;


use PHPIntel\Entity\Entity;
use PHPIntel\Logger\Logger;

/*
* EntityBuilderVisitor
* collects interesting entities from a php class file
*/
class EntityBuilderVisitor extends \PHPParser_NodeVisitorAbstract
{

    protected $source_file = null;

    protected $intel_entities = array();
    protected $pretty_printer = null;


    public function __construct($source_file=null)
    {
        if ($source_file !== null) { $this->source_file = $source_file; }
    }

    public function enterNode(\PHPParser_Node $node)
    {
        switch ($node->getType()) {
            // delegate to $this->enterNode_Stmt_ClassMethod($node);
            case 'Stmt_ClassMethod':
                $method = "enterNode_".$node->getType();
                call_user_func(array($this, $method), $node);
                break;
        }
    }

    public function getIntelEntities() {
        return $this->intel_entities;
    }



    protected function enterNode_Stmt_ClassMethod($node) {
        $function_name = $node->name;

        $params_text = $this->buildParamsText($node->params);

        $this->intel_entities[] = new Entity(array(
            // 'context' => 'public:method:className', // not implemented yet.  perhaps scope (public), type (method), class (classname)
            'label'      => $function_name,
            'completion' => "{$function_name}({$params_text})",
            'filepath'   => $this->source_file,
        ));
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

}
