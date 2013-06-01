<?php

namespace PHPIntel\Project\Status;

use PHPIntel\Project\Project;
use PHPIntel\SQLite\SQLite;

use \Exception;

/*
* ProjectStatus
*/
class ProjectStatus
{

    protected $project = null;

    protected $rescan_delay = 86400; // rescan once a day

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function updateLastScanTime($now=null)
    {
        if ($now === null) { $now = time(); }
        $query = $this->executeQuery("INSERT OR REPLACE INTO project (filepath, last_scan) VALUES (?,?)", array($this->project['db_file'], $now));

        return $now;
    }

    public function getLastScanTime()
    {
        $query = $this->executeQuery("SELECT last_scan FROM project WHERE filepath = ?", array($this->project['db_file']));
        foreach ($query as $row) {
            return $row['last_scan'];
        }
        return null;
    }

    public function shouldRescanProject()
    {
        $last_scan_time = $this->getLastScanTime();

        if ($last_scan_time <= 0) {
            return true;
        }

        if ((time() - $last_scan_time) > $this->rescan_delay) {
            return true;
        }

        return false;
    }


    protected function executeQuery($sql_text, $query_vars=array())
    {
        $db = SQLite::getDBHandle($this->project['db_file']);
        if (!$db) { throw new Exception("Unable to initialize SQLite DB", 1); }

        $sth = $db->prepare($sql_text);
        if (!$sth) { throw new Exception("Unable to prepare statement for sql_text $sql_text", 1); }

        $sth->execute($query_vars);
        $sth->setFetchMode(\PDO::FETCH_ASSOC);

        return $sth;
    }
}
