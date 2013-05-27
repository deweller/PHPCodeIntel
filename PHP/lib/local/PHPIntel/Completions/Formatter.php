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
            $out[] = array($entity['name']."\t".$this->formatTypeForSublime($entity), $this->escapeForSublime($entity['completion']));
        }
        return $out;
    }

    protected function formatTypeForSublime($entity) {
        // *   type: method, variable, constant
        // *   visibility: public, protected, private
        // *   scope: instance or static

        switch ($entity['type']) {
            case 'method':
                return $this->abbreviatedVisibility($entity['visibility']).' func';
            break;
            case 'variable':
                return $this->abbreviatedVisibility($entity['visibility']).' var';
            break;
            case 'constant':
                return $this->abbreviatedVisibility($entity['visibility']).' const';
            break;
        }

        return 'unknown';
    }

    protected function abbreviatedVisibility($full_visibility) {
        switch ($full_visibility) {
            case 'public':
                return 'pub';
            break;
            case 'protected':
                return 'pro';
            break;
            case 'private':
                return 'priv';
            break;
        }

        return '';
    }

    protected function escapeForSublime($text) {
      return str_replace('$','\\$', $text);
    }
}
