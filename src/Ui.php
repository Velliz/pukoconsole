<?php

namespace pukoconsole;

use pukoconsole\util\Echos;

/**
 * Class Ui
 * @package pukoconsole
 */
class Ui
{

    use Echos;

    /**
     * Ui constructor.
     * @param $root
     * @param $kinds
     */
    function __construct($root, $kinds)
    {
        if ($root === null) {
            die(Echos::Prints('Base url required!', true, 'light_red'));
        }

        echo Echos::Prints('Make sure you already have Backend API for handle crud process!', true, 'yellow');


    }

}
