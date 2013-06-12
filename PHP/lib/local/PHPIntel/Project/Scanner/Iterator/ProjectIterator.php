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
        Logger::log("\$project['exclude_patterns']=".print_r($project['exclude_patterns'], true));

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
        $filepath = parent::current();

        // skip dot directories like .DS_Store and .git
        if (substr(basename($filepath), 0, 1) == '.') { return false; }

        // if not .php file, then reject it
        if (substr($filepath, -4) != '.php') { return false; }

        // skip exclude patterns
        if ($this->project['exclude_patterns']) {
            foreach ($this->project['exclude_patterns'] as $exclude_pattern) {
                if (preg_match('/'.$exclude_pattern.'/', $filepath)) {
                    return false;
                }
            }
        }

        // all others are valid
        return true;
    }
}
