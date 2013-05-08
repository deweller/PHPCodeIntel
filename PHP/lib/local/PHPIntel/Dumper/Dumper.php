<?php

namespace PHPIntel\Dumper;

use PHPIntel\SQLite\SQLite;

use \Exception;

/*
* Dumper
*/
interface Dumper
{

    public function __construct($dump_filepath);

    public function replaceEntitiesInFile(array $entities, $php_source_filepath);
    public function dump(array $entities);

}
