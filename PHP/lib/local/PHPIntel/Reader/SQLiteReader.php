<?php

namespace PHPIntel\Reader;

use PHPIntel\SQLite\SQLite;
use PHPIntel\Logger\Logger;
use PHPIntel\Entity\Entity;

use \Exception;

/*
* SQLiteReader
* reads entities from an SQLite 3 database file
*/
class SQLiteReader
{
    public function __construct()
    {
    }

    public function read($filepath)
    {

        $entities = array();

        if (file_exists($filepath)) {
            $db = SQLite::getDBHandle($filepath);
            $sql = "SELECT * FROM entity";
            foreach ($db->query($sql, \PDO::FETCH_ASSOC) as $row) {
                $entities[] = new Entity($row);
            } 
        }

        return $entities;
    }
}
