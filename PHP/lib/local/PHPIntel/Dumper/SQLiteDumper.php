<?php

namespace PHPIntel\Dumper;

use PHPIntel\SQLite\SQLite;

use \Exception;

/*
* SQLiteDumper
* dumps entities to an sqlite3 database
*/
class SQLiteDumper
{
    public function __construct()
    {
    }

    public function dump(array $entities, $filepath)
    {
        $db = SQLite::getDBHandle($filepath);
        $db->beginTransaction();
        $sth = $db->prepare('INSERT INTO entity (label, completion, scope, type, class, filepath) VALUES (?,?,?,?,?,?)');

        foreach($entities as $entity) {
            $sth->execute(array($entity['label'], $entity['completion'], $entity['scope'], $entity['type'], $entity['class'], $entity['filepath']));
        }

        $db->commit();
    }

}
