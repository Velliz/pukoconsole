<?php

namespace pukoconsole;

use pukoconsole\util\Echos;
use pukoconsole\util\Input;

/**
 * Class Secure
 * @package pukoconsole
 */
class Secure
{

    use Echos, Input;

    public function __construct($root)
    {
        echo Echos::Prints("AES-256 secure initialization ...");

        $identifier = Input::Read("Identifier");
        $key = Input::Read("Secure key");
        $cookies = Input::Read("Cookies name");
        $session = Input::Read("Session name");

        $configuration = file_get_contents(__DIR__ . "/template/config/encryption");

        $configuration = str_replace('{{key}}', $identifier, $configuration);
        $configuration = str_replace('{{identifier}}', $key, $configuration);
        $configuration = str_replace('{{cookies}}', $cookies, $configuration);
        $configuration = str_replace('{{session}}', $session, $configuration);

        file_put_contents($root . "/config/encryption.php", $configuration);
    }

    public function __toString()
    {
        return Echos::Prints("secure config created!");
    }

}