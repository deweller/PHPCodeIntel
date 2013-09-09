<?php

namespace PHPIntel\SQLite;

use \PDO;
use \Exception;

/*
* SQLite
*/
class SQLite
{

    public static function visibilityTextToNumber($visibility_text) {
        switch ($visibility_text) {
            case 'public': return 1;
            case 'protected': return 2;
            case 'private': return 3;
        }

        return null;
    }
    public static function visibilityNumberToText($visibility_number) {
        switch ($visibility_number) {
            case 1: return 'public';
            case 2: return 'protected';
            case 3: return 'private';
        }

        return null;
    }

    public static function getDBHandle($filepath) {
        $file_exists = file_exists($filepath);
        $db = new PDO("sqlite:{$filepath}");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // create the tables if they don't exist yet
        if (!$file_exists) {
            
            $db->exec("PRAGMA synchronous = NORMAL"); // we value speed over crash-proof data

            ////////////////////////////////////////////////////////////////////////
            // entity

            $db->exec("
CREATE TABLE IF NOT EXISTS entity (
    name TEXT,
    completion TEXT,

    filepath TEXT,
    class TEXT,
    shortClassName TEXT,
    type TEXT,
    visibility INTEGER,
    scope TEXT
)");
            $db->exec("CREATE INDEX IF NOT EXISTS entity_completion_idx ON entity (completion)");
            $db->exec("CREATE INDEX IF NOT EXISTS entity_scope_class_idx ON entity (scope, class)");
            $db->exec("CREATE INDEX IF NOT EXISTS entity_shortName_idx ON entity (shortClassName)");


            ////////////////////////////////////////////////////////////////////////
            // inheritance

            $db->exec("
CREATE TABLE IF NOT EXISTS inheritance (
    name TEXT,
    shortName TEXT,
    parent TEXT
)");
            $db->exec("CREATE INDEX IF NOT EXISTS inheritance_name_idx ON inheritance (name)");
            $db->exec("CREATE INDEX IF NOT EXISTS inheritance_shortName_idx ON inheritance (shortName)");


            ////////////////////////////////////////////////////////////////////////
            // project

            $db->exec("
CREATE TABLE IF NOT EXISTS project (
    filepath TEXT,
    last_scan INTEGER
)");
            $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS project_filepath_idx ON project (filepath)");



        }

        return $db;
    }
}
