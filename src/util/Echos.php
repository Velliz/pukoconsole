<?php

namespace pukoconsole\util;

/**
 * Trait Echos
 * @package pukoconsole\util
 */
trait Echos
{

    /**
     * @param string $var
     * @param bool $break
     * @param string $color
     * @param string $bg
     * @return string
     */
    public function Prints($var = '', $break = true, $color = '', $bg = ''): string
    {
        $c = new Colors();
        if ($break) {
            return $c->getColoredString(sprintf("\n%s\n", $var), $color, $bg);
        }
        return $c->getColoredString(sprintf("%s\n", $var), $color, $bg);
    }

}
