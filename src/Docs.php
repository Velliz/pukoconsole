<?php
/**
 * pukoconsole.
 * Advanced console util that make pukoframework get things done on the fly.
 * Copyright (c) 2018, Didit Velliz
 *
 * @author Didit Velliz
 * @link https://github.com/velliz/pukoconsole
 * @since Version 0.1.0
 */

namespace pukoconsole;

use pukoconsole\util\Echos;

/**
 * Class Docs
 * @package pukoconsole
 */
class Docs
{

    use Echos;

    public function __construct($root, $command, $value)
    {
        //todo: automated creating docs
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->Prints('Command not supported!', true, 'yellow');
    }


}
