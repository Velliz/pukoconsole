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
 * Class Serve
 * @package pukoconsole
 */
class Serve
{

    use Echos;

    /**
     * Serve constructor.
     * @param null $port
     */
    public function __construct($port = null)
    {
        if ($port === null) {
            $port = 4000;
        }
        echo Echos::Prints("Puko project initialized at localhost:{$port}");
        echo Echos::Prints("Press (Ctrl + C) to stop.");
        echo exec("php -S localhost:{$port} routes.php");

        return true;
    }

}