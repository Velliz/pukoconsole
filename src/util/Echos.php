<?php

namespace pukoconsole\util;

/**
 * Trait Echos
 * @package pukoconsole\util
 */
trait Echos
{

    public static function Prints($var)
    {
        return sprintf("\n%s\n", $var);
    }

}