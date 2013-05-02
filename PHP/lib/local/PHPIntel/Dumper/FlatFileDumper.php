<?php

namespace PHPIntel\Dumper;

use \Exception;

/*
* FlatFileDumper
* dumps entities to a simple flat file format
*/
class FlatFileDumper
{
    public function dump(array $entities, $filepath)
    {

        $fd = fopen($filepath, 'w');

        foreach($entities as $entity) {
            $line = $this->serializeEntity($entity);
            fwrite($fd, $line."\n");
        }

        fclose($fd);
    }

    protected function serializeEntity($entity) {
        return $entity['label'].'|'.$entity['completion'];
    }
}
