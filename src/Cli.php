<?php

namespace pukoconsole;

use pukoconsole\util\Commons;
use pukoconsole\util\Echos;

/**
 * Class Cli
 * @package pukoconsole
 */
class Cli
{
    use Commons, Echos;

    /**
     * Cli constructor.
     * @param null $command
     */
    public function __construct($command = null)
    {
        if ($command === null) {
            die(Echos::Prints("Missing required cli parameter!", true, 'light_red'));
        }

        echo Echos::Prints("puko framework project initialized at console", true, 'blue');
        echo Echos::Prints("press (Ctrl + C) to stop");
        echo exec("php cli {$command}");

        return true;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return Echos::Prints("Console is finished running!");
    }

}
