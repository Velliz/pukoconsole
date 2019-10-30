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
            die(Echos::Prints("Cli parameter required!"));
        }

        echo Echos::Prints("Puko project initialized at cli");
        echo Echos::Prints("Press (Ctrl + C) to stop.");
        echo exec("php cli.php {$command}");

        return true;
    }

    public function __toString()
    {
        return Echos::Prints("Console is finished running!");
    }

}