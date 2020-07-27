<?php

namespace pukoconsole\util;

/**
 * Trait Echos
 * @package pukoconsole\util
 */
trait Echos
{

    /**
     * @param $var
     * @param bool $break
     * @return string
     */
    public static function Prints($var, $break = true)
    {
        if ($break) {
            return sprintf("\n%s\n", $var);
        }
        return sprintf("%s\n", $var);
    }

}