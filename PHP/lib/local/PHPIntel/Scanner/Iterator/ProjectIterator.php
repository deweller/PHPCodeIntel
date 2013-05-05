<?php

namespace PHPIntel\Scanner\Iterator;

use \FilterIterator;
use PHPIntel\Logger\Logger;
use \Exception;

/*
* ProjectIterator
*/
class ProjectIterator extends FilterIterator
{
    public function __construct($settings)
    {
        $this->settings = $settings;

        $append_iterator = new \AppendIterator();
        foreach ($settings['include_dirs'] as $include_dir) {
            $directory_iterator = new \RecursiveDirectoryIterator($include_dir, \FilesystemIterator::SKIP_DOTS);
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
