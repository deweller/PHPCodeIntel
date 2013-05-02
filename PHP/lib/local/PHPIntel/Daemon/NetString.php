<?php

namespace PHPIntel\Daemon;

use \Exception;

/*
* NetString
* netstring parser
*/
class NetString
{

    /**
     * Parses a netstring
     * 
     * when this method receives a single netstring like 11:hello world,
     * it will return hello world
     * @param string $net_string a partial or complete netstring
     * @return string the netstring contents if the net string is complete, null otherwise
     */
    static function parse($net_string)
    {
        $net_string_length = null;

        if (sscanf($net_string, "%d", $net_string_length) < 1) { return null; }

        $starting_pos = strlen($net_string_length);
        if (substr($net_string, $starting_pos, 1) != ':') { return null; }

        $starting_pos = $starting_pos + 1;
        $full_length = $starting_pos + $net_string_length + 1;

        // check to see if we have the full netstring
        if (strlen($net_string) == $full_length) {
            // done
            return substr($net_string, $starting_pos, $net_string_length);
        }

        return null;
    }

}
