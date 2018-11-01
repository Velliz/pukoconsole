<?php

namespace pukoconsole;

/**
 * Class Auth
 * @package pukoconsole
 */
class Auth
{

    public function __construct($value)
    {
        $varNewFile = file_get_contents("template/plugins/auth");
        if ($value === null) {
            exit('class_name not specified. example: php puko setup base_auth UserAuth');
        }
        $varNewFile = str_replace('{{class}}', $value, $varNewFile);
        if (!is_dir('plugins/auth')) {
            mkdir('plugins/auth');
        }
        file_put_contents("plugins/auth/" . $value . '.php', $varNewFile);

        echo "\n... initialization completed.\n";
        exit;
    }
}