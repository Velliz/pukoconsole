<?php

namespace pukoconsole;

use Exception;
use PDO;
use pukoconsole\util\Echos;
use pukoconsole\util\Input;

/**
 * Class GenerateDatabase
 * @package pukoconsole
 */
class GenerateDatabase
{

    /**
     * @var PDO
     */
    var $PDO;

    /**
     * @var string
     */
    var $query = '';

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

        switch ($db) {
            case 'mysql':
                $this->PDO = $this->GenerateSchema($host, $port, $dbName, $user, $pass);
                break;
            default:
                die(Echos::Prints(sprintf("Sorry, database '%s' not yet supported.", $db)));
        }

        $statement = $this->PDO->prepare($this->query);
        $statement->execute();
    }

    /**
     * @param $host
     * @param $port
     * @param $dbName
     * @param $user
     * @param $pass
     * @return PDO
     */
    public function GenerateSchema($host, $port, $dbName, $user, $pass)
    {
        try {
            $pdoConnection = "mysql:host=$host;port=$port;dbname=$dbName";
            $dbi = new PDO($pdoConnection, $user, $pass);
            $dbi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->query = "CREATE DATABASE {$dbName};";

            return $dbi;
        } catch (Exception $ex) {
            die(Echos::Prints("Failed to connect."));
        }

    }

}