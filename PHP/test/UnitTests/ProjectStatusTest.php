<?php

use PHPIntel\Project\Status\ProjectStatus;
use PHPIntel\Logger\Logger;
use PHPIntel\Test\TestProject;

use \PHPUnit_Framework_Assert as PHPUnit;

class ProjectStatusTest extends \PHPUnit_Framework_TestCase
{
    ////////////////////////////////////////////////////////////////////////
    // tests

    public function testUpdateScanTime()
    {

        $project = TestProject::getTestProject();
        $project_status = new ProjectStatus($project);
        $now = $project_status->updateLastScanTime();
        
        ////////////////////////////////////////////////////////////////////////
        // check for time

        $read_scan_time = $project_status->getLastScanTime();
        // Logger::log("read_scan_time=$read_scan_time");
        PHPUnit::assertTrue($now > 0);
        PHPUnit::assertEquals($now, $read_scan_time);

        // do it again to test replace
        $now = $project_status->updateLastScanTime($now+1);
        $read_scan_time = $project_status->getLastScanTime();
        // Logger::log("read_scan_time=$read_scan_time");
        PHPUnit::assertTrue($now > 0);
        PHPUnit::assertEquals($now, $read_scan_time);


        // clean up
        TestProject::cleanup($project['db_file']);
    }




    ////////////////////////////////////////////////////////////////////////
    // util

}
