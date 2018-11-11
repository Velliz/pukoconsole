<?php

namespace pukoconsole;

/**
 * Class Secure
 * @package pukoconsole
 */
class Secure
{

    public function __construct($root)
    {
        echo "\nStart AES-256 secure initialization ...\n\n";
        echo "identifier   : ";
        $identifier = preg_replace('/\s+/', '', fgets(STDIN));
        echo "secure key   : ";
        $key = preg_replace('/\s+/', '', fgets(STDIN));
        echo "cookies name : ";
        $cookies = preg_replace('/\s+/', '', fgets(STDIN));
        echo "session name : ";
        $session = preg_replace('/\s+/', '', fgets(STDIN));

        $configuration = file_get_contents("template/config/encryption");
        $configuration = str_replace('{{key}}', $identifier, $configuration);
        $configuration = str_replace('{{identifier}}', $key, $configuration);
        $configuration = str_replace('{{cookies}}', $cookies, $configuration);
        $configuration = str_replace('{{session}}', $session, $configuration);

        file_put_contents("config/encryption.php", $configuration);

        echo "\n... initialization completed.\n";
        exit;
    }

}