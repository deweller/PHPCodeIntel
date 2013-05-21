<?php

namespace PHPIntel\Test;

use PHPIntel\Entity\IntelEntity;
use PHPIntel\Entity\ClassEntity;

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
            $test_entities[] = new IntelEntity($entities_entry);
        }

        return $test_entities;
    }

    public static function buildTestClassEntities($relative_yaml_path)
    {
        $test_classes = array();
        $class_entities_data = yaml_parse_file($GLOBALS['BASE_PATH'].'/test/yaml/entities/'.$relative_yaml_path);

        foreach ($class_entities_data as $class_entry) {
            $test_classes[] = new ClassEntity($class_entry);
        }

        return $test_classes;
    }

}