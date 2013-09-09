<?php

namespace PHPIntel\Dumper;

use PHPIntel\SQLite\SQLite;
use PHPIntel\Logger\Logger;
use PHPIntel\Entity\EntityCollection;

use \Exception;

/*
* SQLiteDumper
* dumps entities to an sqlite3 database
*/
class SQLiteDumper implements Dumper
{

    protected $sqlite_filepath = null;

    public function __construct($sqlite_filepath)
    {
        $this->sqlite_filepath = $sqlite_filepath;
    }

    public function replaceEntitiesInFile(EntityCollection $entity_collection, $php_source_filepath)
    {
        $_this = $this;
        $this->executeInDBTransaction(function($db) use ($entity_collection, $php_source_filepath, $_this) {
            $_this->clearFilepath($db, $php_source_filepath);
            $_this->dumpIntelEntities($db, $entity_collection['entities']);

            $_this->dumpClassEntities($db, $entity_collection['classes']);
        });
    }

    public function dump(EntityCollection $entity_collection)
    {
        $_this = $this;
        $this->executeInDBTransaction(function($db) use ($entity_collection, $_this) {
            $_this->dumpIntelEntities($db, $entity_collection['entities']);
        });
    }


    public function clearFilepath($db, $php_source_filepath)
    {
      $db->prepare('DELETE FROM entity WHERE filepath=?')->execute(array($php_source_filepath));
    }

    public function dumpIntelEntities($db, array $entities)
    {
        $sth = $db->prepare('INSERT INTO entity (name, completion, filepath, class, shortClassName, type, visibility, scope) VALUES (?,?,?,?,?,?,?,?)');
        foreach($entities as $entity) {
            $sth->execute(array($entity['name'], $entity['completion'], $entity['filepath'], $entity['class'], $entity['shortClassName'], $entity['type'], SQLite::visibilityTextToNumber($entity['visibility']), $entity['scope']));
        }
    }

    public function dumpClassEntities($db, array $classes) {
        foreach($classes as $class) {
            $db->prepare('DELETE FROM inheritance WHERE name=?')->execute(array($class['name']));

            $sth = $db->prepare('INSERT INTO inheritance (name, shortName, parent) VALUES (?,?,?)');
            $res = $sth->execute(array($class['name'], $class['shortName'], $class['parent']));
        }
    }

    protected function executeInDBTransaction($callback) {
        $db = SQLite::getDBHandle($this->sqlite_filepath);
        $db->beginTransaction();

        $callback($db);

        $db->commit();
    }

}
