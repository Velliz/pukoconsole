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
        echo $this->Prints("AES-256 secure initialization ...", true, 'blue');

        $identifier = $this->Read("Identifier");
        $key = $this->Read("Secure key");
        $cookies = $this->Read("Cookies name");
        $session = $this->Read("Session name");
        $expired = $this->Read("Session expired number (in days) or blank for infinity");
        $expiredText = $this->Read("Session expired display text", false);
        $error = $this->Read("Error display text", false);

        $configuration = file_get_contents(__DIR__ . "/template/config/encryption");

        $configuration = str_replace('{{key}}', $identifier, $configuration);
        $configuration = str_replace('{{identifier}}', $key, $configuration);
        $configuration = str_replace('{{cookies}}', $cookies, $configuration);
        $configuration = str_replace('{{session}}', $session, $configuration);
        $configuration = str_replace('{{expired}}', $expired, $configuration);
        $configuration = str_replace('{{expiredText}}', $expiredText, $configuration);
        $configuration = str_replace('{{errorText}}', $error, $configuration);

        file_put_contents("{$root}/config/encryption.php", $configuration);
    }

    public function __toString()
    {
        return $this->Prints("secure config created!", true, 'green');
    }

}
