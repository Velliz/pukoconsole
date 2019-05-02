<?php

namespace pukoconsole;

use Exception;
use pukoconsole\util\Echos;
use pukoconsole\util\Input;

/**
 * Class GenerateDatabase
 * @package pukoconsole
 */
class GenerateDatabase
{

    /**
     * Database constructor.
     * @param null $root
     * @throws Exception
     */
    public function __construct($root = null)
    {
        if ($root === null) {
            die(Echos::Prints('Base url required'));
        }

        $db = Input::Read('Database Type (mysql, oracle, sqlsrv, mongo)');

        $host = Input::Read('Hostname (Default: localhost)');
        $port = Input::Read('Port (Default: 3306)');
        $dbName = Input::Read('Database Name');

        $user = Input::Read('Username');
        $pass = Input::Read('Password');

        $configuration = file_get_contents(__DIR__ . "/template/config/database");

        $configuration = str_replace('{{type}}', $db, $configuration);
        $configuration = str_replace('{{host}}', $host, $configuration);
        $configuration = str_replace('{{user}}', $user, $configuration);
        $configuration = str_replace('{{pass}}', $pass, $configuration);
        $configuration = str_replace('{{dbname}}', $dbName, $configuration);
        $configuration = str_replace('{{port}}', $port, $configuration);

        file_put_contents("{$root}/config/database.php", $configuration);

    }

}