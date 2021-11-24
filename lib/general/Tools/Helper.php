<?php


namespace Intensa\Logger\Tools;


class Helper
{
    public static function convertToBytes($val)
    {
        if (!is_string($val)) {
            return false;
        }

        if (!preg_match('/^\s*(?<val>\d+)(?:\.\d+)?\s*(?<unit>[gmk]?)\s*$/i', $val, $match)) {
            return false;
        }

        $val = (int)$match['val'];

        switch (strtolower($match['unit'] ?? '')) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
}