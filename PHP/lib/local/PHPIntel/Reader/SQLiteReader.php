<?php

namespace PHPIntel\Reader;

use PHPIntel\SQLite\SQLite;
use PHPIntel\Context\Context;
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
        $sql_text = "SELECT * FROM entity";
        return $this->buildEntitiesByQuery($sql_text);

        $entities = array();
        $db = $this->getDBHandle();
        if (file_exists($this->sqlite_filepath)) {
            $db = $this->getDBHandle();
            $sql = "SELECT * FROM entity";
            foreach ($db->query($sql, \PDO::FETCH_ASSOC) as $row) {
                $entities[] = new Entity($row);
            } 
        }

        return $entities;
    }

    public function lookupByContext(Context $context)
    {

        // build lookup query
        $sql_text = "SELECT * FROM entity WHERE scope = ? AND class = ?";
        $query_vars = array($context['scope'], $context['class']);

        // add prefix if it exists
        if (isset($context['prefix']) AND $context['prefix']) {
            $sql_text .= " AND prefix = ?";
            $query_vars[] = $context['prefix'];
        }

        return $this->buildEntitiesByQuery($sql_text, $query_vars);
    }



    protected function getDBHandle() {
      return SQLite::getDBHandle($this->sqlite_filepath);
    }

    protected function buildEntitiesByQuery($sql_text, $query_vars=array()) {
        $entities = array();
        if (file_exists($this->sqlite_filepath)) {
            $db = $this->getDBHandle();
            $sth = $db->prepare($sql_text);
            $sth->execute($query_vars);
            $sth->setFetchMode(\PDO::FETCH_ASSOC);

            foreach ($sth as $row) {
                $entities[] = new Entity($row);
            }
        } 
        return $entities;
    }
}
