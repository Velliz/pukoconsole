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
 * Class Auth
 * @package pukoconsole
 */
class Auth
{

    use Echos;

    var $value = '';

    /**
     * Auth constructor.
     * @param $root
     * @param $value
     */
    public function __construct($root, $value)
    {
        if ($value === null) {
            die(Echos::Prints("class_name not specified! example: php puko setup auth UsersAuth", true, 'light_red'));
        }

        $this->value = $value;

        $template = file_get_contents(__DIR__ . "/template/plugins/auth");

        $template = str_replace('{{class}}', $value, $template);
        if (!is_dir($root . '/plugins/auth')) {
            mkdir($root . '/plugins/auth', 0777, true);
        }

        file_put_contents($root . "/plugins/auth/{$value}.php", $template);
    }

    public function __toString()
    {
        return Echos::Prints("Auth template with name {$this->value} created!", true, 'green');
    }
}
