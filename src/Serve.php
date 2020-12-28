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

    var $port = 4000;

    /**
     * Serve constructor.
     * @param null $port
     */
    public function __construct($port = null)
    {
        if ($port === null) {
            $port = 4000;
        }
        $this->port = $port;
        echo Echos::Prints("Puko project initialized at localhost:{$port}", true, 'blue');
        echo Echos::Prints("Press (Ctrl + C) to stop.");
        echo exec("php -S localhost:{$port} routes.php");

        return true;
    }

    public function __toString()
    {
        return Echos::Prints("PHP server starred on port {$this->port}.", true, 'blue');
    }

}
