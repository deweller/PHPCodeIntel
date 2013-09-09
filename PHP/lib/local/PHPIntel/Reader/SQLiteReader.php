<?php

namespace PHPIntel\Reader;

use PHPIntel\SQLite\SQLite;
use PHPIntel\Entity\ClassEntity;
use PHPIntel\Context\Context;
use PHPIntel\Logger\Logger;
use PHPIntel\Entity\IntelEntity;

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

    public function lookupByContext(Context $context)
    {
        switch ($context['entityType']) {

            case 'className':
                return $this->lookupClassEntitiesByContext($context);
                break;

            case 'constructor':
                return $this->lookupConstructorEntitiesByContext($context);
                break;

        }

        return $this->lookupMemberEntitiesByContext($context);

    }


    /**
     * looks up the parent if any from the inheritance chain
     * @param  string $class_name the qualified name of the class like MyClass or Acme\MyClass
     * @return mixed  the string name of the parent class or null if not found
     */
    public function getParentClass($class_name)
    {
        // build lookup query
        $sql_text = "SELECT * FROM inheritance WHERE name = ?";
        $query_vars = array($class_name);

        $results = $this->executeQuery($sql_text, $query_vars);
        if ($results) {
            foreach ($results as $row) {
                return $row['parent'];
            }
        }

        return null;
    }

    public function getAllEntities()
    {
        $sql_text = "SELECT * FROM entity";

        return $this->buildEntitiesByQuery($sql_text);
    }

    public function getAllClasses()
    {
        $classes = array();

        $sql_text = "SELECT * FROM inheritance";
        $results = $this->executeQuery($sql_text);
        if ($results) {
            foreach ($results as $row) {
                $classes[] = new ClassEntity($row);
            }
        }

        return $classes;
    }

    protected function getDBHandle()
    {
        return SQLite::getDBHandle($this->sqlite_filepath);
    }

    protected function mergeEntities($parent_entities, $entities)
    {
        if (!$parent_entities) { return $entities; }

        $merged_entities = array();

        foreach($parent_entities as $parent_entity) {
            $merged_entities[$parent_entity['name']] = $parent_entity;
        }

        foreach($entities as $entity) {
            $merged_entities[$entity['name']] = $entity;
        }

        return array_values($merged_entities);
    }

    protected function buildParentEntitiesByContext(Context $context)
    {
        $parent_entities = null;
        $parent_class = $this->getParentClass($context['class']);
        // Logger::log("\$parent_class=$parent_class");

        if ($parent_class) {
            $parent_context = $context->getParentContext($parent_class);
            if ($parent_context) {
                $parent_entities = $this->lookupByContext($parent_context);
            }
        }

        return $parent_entities;
    }

    protected function lookupMemberEntitiesByContext($context) {
        $parent_entities = $this->buildParentEntitiesByContext($context);

        $entities = $this->buildEntitiesByContext_member($context);

        return $this->mergeEntities($parent_entities, $entities);
    }


    protected function buildEntitiesByContext_member(Context $context)
    {
        // build lookup query
        $sql_text = "SELECT * FROM entity WHERE scope = ? AND class = ? AND visibility <= ?";
        $query_vars = array($context['scope'], $context['class'], SQLite::visibilityTextToNumber($context['visibility']));

        // add prefix if it exists
        if (isset($context['prefix']) AND $context['prefix']) {
            $sql_text .= " AND completion LIKE ?";
            $query_vars[] = $context['prefix'].'%';
        }

        // Logger::log("sql_text=$sql_text query_vars=".print_r($query_vars, true));
        return $this->buildEntitiesByQuery($sql_text, $query_vars);
    }


    protected function lookupConstructorEntitiesByContext(Context $context) {
        // first lookup all class entities
        $class_entities = $this->lookupClassEntitiesByContext($context);

        $entities_out = array();
        foreach($class_entities as $class_entity) {
            $constructor_entity = $this->buildFirstConstructorEntityByName($class_entity['name']);
            // Logger::log("\$constructor_entity=".print_r($constructor_entity, true));
            if (!$constructor_entity) { continue; }

            $constructor_entity['name'] = preg_replace('!^__construct(.*)!i', $class_entity['shortName'].'$1', $constructor_entity['name']);
            $constructor_entity['completion'] = preg_replace('!^__construct(.*)!i', $class_entity['shortName'].'$1', $constructor_entity['completion']);
            $entities_out[] = $constructor_entity;
        }

        // Logger::log("entities_out=".print_r($entities_out, true));
        return $entities_out;
    } 

    protected function buildFirstConstructorEntityByName($class_name) {
        // see if each has a constructor,
        $constructor_entity = $this->buildConstructorEntityForClassName($class_name);
        if ($constructor_entity) {
            // if so, use it
            return $constructor_entity;
        }

        //  if not, travel up the inheritance chain until we find a class that does.
        if ($parent_class_name = $this->getParentClass($class_name)) {
            return $this->buildFirstConstructorEntityByName($parent_class_name);
        }

        // no constructor and no parent
        return null;
    }

    protected function buildConstructorEntityForClassName($class_name)
    {
        // build lookup query
        $sql_text = "SELECT * FROM entity WHERE scope = 'instance' AND type = 'method' AND name = '__construct' AND class = ?";
        $query_vars = array($class_name);
        $entities = $this->buildEntitiesByQuery($sql_text, $query_vars);
        if ($entities) { return $entities[0]; }
        return null;
    }

    protected function lookupClassEntitiesByContext($context) {
        // build lookup query
        $sql_text = "SELECT name, shortName FROM inheritance";
        $query_vars = array();

        // add prefix if it exists
        if (strlen($context['prefix'])) {
            $sql_text .= " WHERE shortName LIKE ?";
            $query_vars[] = $context['prefix'].'%';
        }

        // Class entities
        return $this->buildClassEntitiesByQuery($sql_text, $query_vars);
    }

    protected function buildClassEntitiesByQuery($sql_text, $query_vars=array())
    {
        $class_entities = array();
        $results = $this->executeQuery($sql_text, $query_vars);
        foreach ($results as $row) {
            $data = $row;
            $class_entities[] = new ClassEntity($data);
        }

        return $class_entities;
    }


    protected function buildEntitiesByQuery($sql_text, $query_vars=array())
    {
        $entities = array();
        $results = $this->executeQuery($sql_text, $query_vars);
        foreach ($results as $row) {
            $data = $row;
            $data['visibility'] = SQLite::visibilityNumberToText($row['visibility']);
            $entities[] = new IntelEntity($data);
        }

        return $entities;
    }

    protected function executeQuery($sql_text, $query_vars=array())
    {
        if (file_exists($this->sqlite_filepath)) {
            $db = $this->getDBHandle();
            if (!$db) { throw new Exception("Unable to initialize SQLite DB", 1); }
            $sth = $db->prepare($sql_text);
            if (!$sth) { throw new Exception("Unable to prepare statement for sql_text $sql_text", 1); }

            $sth->execute($query_vars);
            $sth->setFetchMode(\PDO::FETCH_ASSOC);

            return $sth;
        }

        return array();
    }

}
