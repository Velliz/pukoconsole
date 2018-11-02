<?php

namespace pukoconsole\util;

/**
 * Trait Input
 * @package pukoconsole\util
 */
trait Input
{

    /**
     * @param $variable
     * @return null|string|string[]
     */
    public static function Read($variable)
    {
        echo sprintf('%s :', $variable);
        return preg_replace('/\s+/', '', fgets(STDIN));
    }

}