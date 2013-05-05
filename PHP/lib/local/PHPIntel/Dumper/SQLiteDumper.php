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

    public function dump(array $entities)
    {
        $db = SQLite::getDBHandle($this->sqlite_filepath);
        $db->beginTransaction();
        $sth = $db->prepare('INSERT INTO entity (label, completion, scope, type, class, filepath) VALUES (?,?,?,?,?,?)');

        foreach($entities as $entity) {
            $sth->execute(array($entity['label'], $entity['completion'], $entity['scope'], $entity['type'], $entity['class'], $entity['filepath']));
        }

        $db->commit();
    }

}
