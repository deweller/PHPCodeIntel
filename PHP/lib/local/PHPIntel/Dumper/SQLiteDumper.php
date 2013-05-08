<?php

namespace PHPIntel\Dumper;

use PHPIntel\SQLite\SQLite;

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

    public function replaceEntitiesInFile(array $entities, $php_source_filepath)
    {
        $_this = $this;
        $this->executeInDBTransaction(function($db) use ($entities, $php_source_filepath, $_this) {
            $_this->clearFilepath($db, $php_source_filepath);

            $_this->dumpEntities($db, $entities);
        });
    }

    public function dump(array $entities)
    {
        $_this = $this;
        $this->executeInDBTransaction(function($db) use ($entities, $_this) {
            $_this->dumpEntities($db, $entities);
        });
    }


    public function clearFilepath($db, $php_source_filepath)
    {
      $db->prepare('DELETE FROM entity WHERE filepath=?')->execute(array($php_source_filepath));
    }

    public function dumpEntities($db, $entities)
    {
        $sth = $db->prepare('INSERT INTO entity (label, completion, scope, type, class, filepath) VALUES (?,?,?,?,?,?)');
        foreach($entities as $entity) {
            $sth->execute(array($entity['label'], $entity['completion'], $entity['scope'], $entity['type'], $entity['class'], $entity['filepath']));
        }
    }
    protected function executeInDBTransaction($callback) {
        $db = SQLite::getDBHandle($this->sqlite_filepath);
        $db->beginTransaction();

        $callback($db);

        $db->commit();
    }

}
