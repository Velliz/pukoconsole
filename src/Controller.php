<?php

namespace pukoconsole;

/**
 * Class Controller
 * @package pukoconsole
 */
class Controller
{

    public function __construct($value, $customValue)
    {
        if ($customValue === 'service') {
            $varNewFile = file_get_contents("template/controller/service");
        }
        if ($customValue === 'view') {
            $varNewFile = file_get_contents("template/controller/view");
        }

        if ($value === null) {
            exit('class_name not specified. example: php puko setup base_auth UserAuth');
        }
        $varNewFile = str_replace('{{class}}', $value, $varNewFile);
        if (!is_dir('plugins/controller')) {
            mkdir('plugins/controller');
        }
        file_put_contents("plugins/controller/" . $value . '.php', $varNewFile);

        echo "\n... initialization completed.\n";
        exit;
    }

}