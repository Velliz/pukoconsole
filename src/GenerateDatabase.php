<?php

namespace pukoconsole;

use Exception;
use PDO;
use pukoconsole\util\Echos;

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

        $configuration = require("{$root}/config/database.php");
        if (!isset($configuration['primary'])) {
            throw new Exception('database connecton file error!');
        }
        $configuration = $configuration['primary'];

        switch ($configuration['dbType']) {
            case 'mysql':
                $this->PDO = $this->GenerateSchema($configuration['host'], $configuration['port'],
                    $configuration['dbName'], $configuration['user'], $configuration['pass']);
                break;
            default:
                die(Echos::Prints(sprintf("Sorry, database '%s' not yet supported.", $configuration['dbType'])));
        }

        try {
            $statement = $this->PDO->prepare($this->query);
            $statement->execute();

            //todo: scan all model and generate the table
        } catch (Exception $ex) {
            die(Echos::Prints("Database creation failed or database already exists."));
        }
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