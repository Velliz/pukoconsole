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
            die($this->Prints("Missing required cli parameter!", true, 'light_red'));
        }

        echo $this->Prints("puko framework project initialized at console", true, 'blue');
        echo $this->Prints("press (Ctrl + C) to stop");
        echo exec("php cli {$command}");

        return true;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->Prints("Console is finished running!");
    }

}
