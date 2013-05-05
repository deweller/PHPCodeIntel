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
    protected $sqlite_filepath = null;

    public function __construct($sqlite_filepath)
    {
        $this->sqlite_filepath = $sqlite_filepath;
    }

    public function read()
    {

        $entities = array();

        if (file_exists($this->sqlite_filepath)) {
            $db = SQLite::getDBHandle($this->sqlite_filepath);
            $sql = "SELECT * FROM entity";
            foreach ($db->query($sql, \PDO::FETCH_ASSOC) as $row) {
                $entities[] = new Entity($row);
            } 
        }

        return $entities;
    }
}
