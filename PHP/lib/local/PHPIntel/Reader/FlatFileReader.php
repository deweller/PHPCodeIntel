<?php

namespace PHPIntel\Reader;

use PHPIntel\Entity\Entity;

use \Exception;

/*
* FlatFileReader
* reads entities from a flat file
*/
class FlatFileReader
{

    public function read($filepath)
    {

        $entities = array();

        if (file_exists($filepath)) {
            $fd = fopen($filepath, 'r');
            while (($line = fgets($fd)) !== false) {
                if ($entity = $this->unserializeEntity($line)) {
                    $entities[] = $entity;
                }
            }
            fclose($fd);
        }

        return $entities;
    }

    protected function unserializeEntity($line) {
        $sep_pos = strpos($line, '|');
        $entity = new Entity(array(
            'label'      => substr($line, 0, $sep_pos),
            'completion' => substr(rtrim($line), $sep_pos + 1),
        ));
        return $entity;
    }
}
