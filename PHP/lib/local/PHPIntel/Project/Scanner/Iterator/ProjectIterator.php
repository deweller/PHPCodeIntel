<?php

namespace PHPIntel\Project\Scanner\Iterator;

use \FilterIterator;
use PHPIntel\Project\Project;
use PHPIntel\Logger\Logger;
use \Exception;

/*
* ProjectIterator
*/
class ProjectIterator extends FilterIterator
{
    public function __construct(Project $project)
    {
        $this->project = $project;

        $append_iterator = new \AppendIterator();
        foreach ($project['scan_dirs'] as $scan_dir) {
            $directory_iterator = new \RecursiveDirectoryIterator($scan_dir, \FilesystemIterator::SKIP_DOTS);
            $outer_iterator = new \RecursiveIteratorIterator($directory_iterator);
            $append_iterator->append($outer_iterator);
        }
        parent::__construct($append_iterator);
    }

    public function accept()
    {
        // skip dot directories like .DS_Store and .git
        if (substr(basename(parent::current()), 0, 1) == '.') { return false; }

        // if not .php file, then reject it
        //  (unimplemented)

        // all others are valid
        return true;
    }
}
