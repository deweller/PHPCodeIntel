<?php

namespace PHPIntel;

use PHPIntel\Collector\IntelCollector;


/*
* FileIntelBuilder
* extracts entities from a file
*/
class FileIntelBuilder
{
    public function __construct()
    {
    }


    /**
     * extracts entities from a file
     * @param type $file 
     * @return type
     */
    public function extractFromFile($file)
    {
        $intel_collector = new IntelCollector();

        $parser = new \PHPParser_Parser(new \PHPParser_Lexer());
        $traverser = new \PHPParser_NodeTraverser();
        $traverser->addVisitor($intel_collector);

        try {
            // parse into statements
            $stmts = $parser->parse(file_get_contents($file));

            // the collector will visit all the nodes and collect data
            $stmts = $traverser->traverse($stmts);

        } catch (\PHPParser_Error $e) {
            echo 'Parse Error: ', $e->getMessage();
        }

        return $intel_collector->getIntelEntities();
    }
}
