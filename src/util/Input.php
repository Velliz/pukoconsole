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
     * @param bool $trim
     * @return null|string|string[]
     */
    public static function Read($variable, $trim = true)
    {
        echo sprintf('%s :', $variable);

        if (!$trim) {
            return fgets(STDIN);
        }
        return preg_replace('/\s+/', '', fgets(STDIN));
    }

}