<?php

namespace pukoconsole;

use Exception;
use pukoconsole\util\Echos;
use pukoconsole\util\Input;

/**
 * Class Ui
 * @package pukoconsole
 */
class Ui
{

    use Echos;

    var $table = '';

    var $schema = '';

    /**
     * Ui constructor.
     * @param $root
     * @param $kinds
     * @throws Exception
     */
    function __construct($root, $kinds)
    {
        if ($root === null) {
            die(Echos::Prints('Base url required!', true, 'light_red'));
        }

        echo Echos::Prints('Make sure you already have Backend API for handle crud process!', true, 'yellow');

        $this->schema = Input::Read('Database Schema (primary)');
        if (strlen($this->schema) <= 0) {
            $this->schema = 'primary';
        }
        $this->table = Input::Read('Table');
        if (strlen($this->table) <= 0) {
            die(Echos::Prints('Table required!', true, 'light_red'));
        }

        $database = file_get_contents("{$root}/config/database.php");
        var_dump($database);

        switch ($kinds) {
            case "datatables":

            default:
                throw new Exception('UI type not supported');
        }

    }

    public function __toString()
    {
        return Echos::Prints('Generate UI setting completed',true, 'green');
    }


}
