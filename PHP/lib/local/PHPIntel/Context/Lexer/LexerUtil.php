<?php

namespace PHPIntel\Context\Lexer;

use \Exception;

/*
* LexerUtil
*/
class LexerUtil
{

    public static function buildTokenPositionMap($tokens) {
        $pos_map = array();
        $pos = 0;
        foreach($tokens as $offset => $token) {
            $pos_map[$offset] = $pos;
            if (is_string($token)) {
                $pos += strlen($token);
            } else {
                $pos += strlen($token[1]);
            }
        }

        return $pos_map;
    }

    public static function findTokenOffsetByStringPosition($tokens, $position_map, $str_position)
    {
        // start at one character back
        $str_position = $str_position - 1;

        for ($offset = count($tokens)-1; $offset >= 0; $offset--) { 
            if ($position_map[$offset] <= $str_position) {
                return $offset;
            }
        }

        if ($offset < 0) { throw new Exception("Token at position $position not found", 1); }

        return null;
    }

    public static function buildTokenDescriptionsByPosition($tokens, $position_map) {
        $out = array();
        foreach($tokens as $offset => $token) {
            $pos = $position_map[$offset];
            $out[$pos] = self::buildTokenDescriptionArray($token);
        }

        return $out;
    }

    public static function buildTokenDescriptionArray($token) {
      if (is_string($token)) {
          return array(-1, $token);
      }

      return array($token[0], $token[1]);
    }
}
