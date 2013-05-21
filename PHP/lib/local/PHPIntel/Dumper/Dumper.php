<?php

namespace PHPIntel\Dumper;

use PHPIntel\SQLite\SQLite;
use PHPIntel\Entity\EntityCollection;

use \Exception;

/*
* Dumper
*/
interface Dumper
{

    public function __construct($dump_filepath);

    public function replaceEntitiesInFile(EntityCollection $entities, $php_source_filepath);
    public function dump(EntityCollection $entities);

}
