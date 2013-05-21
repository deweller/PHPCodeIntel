<?php

namespace PHPIntel\Test;

use \Exception;

/*
* EntityUtil
*/
class EntityUtil
{
    public static function sortedEntities($entities, $field_one /*, $field_two */) {
        $out = array();
        foreach($entities as $entity) {
            $out[] = $entity;
        }

        $args = func_get_args();
        $fields = array_slice($args, 1);

        usort($out, function($a, $b) use ($fields) {
            foreach($fields as $field) {
                if (isset($a[$field]) AND isset($b[$field])) {
                    if ($a[$field] > $b[$field]) { return 1; }
                    if ($a[$field] < $b[$field]) { return -1; }
                }
            }

            // equal
            return 0;
        });

        return $out;
    }
}
