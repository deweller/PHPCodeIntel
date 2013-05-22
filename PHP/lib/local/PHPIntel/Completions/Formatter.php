<?php

namespace PHPIntel\Completions;

use \Exception;

/*
* Formatter
* formats entities for sublime
*/
class Formatter
{

    public function formatEntitiesAsCompletions($entities) {
        $out = array();
        foreach($entities as $entity) {
            $out[] = array($entity['name']."\t"."function", $this->escapeForSublime($entity['completion']));
        }
        return $out;
    }


    protected function escapeForSublime($text) {
      return str_replace('$','\\$', $text);
    }
}
