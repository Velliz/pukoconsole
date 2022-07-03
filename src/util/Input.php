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
    public function Read($variable, $trim = true)
    {
        echo sprintf('%s :', $variable);

        if (!$trim) {
            return str_replace("\r\n", '', fgets(STDIN));
        }
        return preg_replace('/\s+/', '', fgets(STDIN));
    }

}
