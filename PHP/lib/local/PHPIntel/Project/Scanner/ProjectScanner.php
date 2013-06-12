<?php

namespace PHPIntel\Project\Scanner;

use PHPIntel\Dumper\Dumper;
use PHPIntel\Project\Project;
use PHPIntel\Intel\IntelBuilder;
use PHPIntel\Project\Scanner\Iterator\ProjectIterator;
use PHPIntel\Logger\Logger;

use \Exception;

/*
* ProjectScanner
*/
class ProjectScanner
{
    protected $project = null;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function scanAndDumpProject(IntelBuilder $intel, Dumper $dumper)
    {
        if (!isset($this->project['scan_dirs'])) { throw new Exception("Directories to scan not found.", 1); }

        $project_iterator = new ProjectIterator($this->project);
        foreach($project_iterator as $path) {
            Logger::log("Begin scanning file: $path");

            // for every file in the poject extract the intel and add it to the data store
            $entity_collection = $intel->extractFromFile($path);

            $dumper->replaceEntitiesInFile($entity_collection, $path);

            Logger::log("End scanning file: ".basename($path));
        }

    }


}