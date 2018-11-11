<?php

namespace pukoconsole\util;

/**
 * Trait Echos
 * @package pukoconsole\util
 */
trait Echos
{

    public static function Prints($var, $break = true)
    {
        if ($break) {
            return sprintf("\n%s\n", $var);
        }
        return sprintf("%s\n", $var);
    }

}