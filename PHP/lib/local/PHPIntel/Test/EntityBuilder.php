<?php

namespace PHPIntel\Test;

use PHPIntel\Entity\Entity;

use \Exception;

/*
* EntityBuilder
*/
class EntityBuilder
{
    public function __construct()
    {
    }

    public static function buildTestEntities($relative_yaml_path)
    {
        $test_entities = array();
        $entities_data = yaml_parse_file($GLOBALS['BASE_PATH'].'/test/yaml/entities/'.$relative_yaml_path);

        foreach ($entities_data as $entities_entry) {
            $entities_entry['filepath'] = str_replace('==data_path==', $GLOBALS['BASE_PATH'].'/test/data', $entities_entry['filepath']);
            $test_entities[] = new Entity($entities_entry);
        }

        return $test_entities;
    }

}