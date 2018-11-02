<?php

namespace pukoconsole;

use PDO;
use pukoconsole\util\Echos;
use pukoconsole\util\Input;

class Database
{

    use Input, Echos;

    var $args = array();

    var $PDO = null;

    var $query = '';

    /**
     * Database constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $db = Input::Read('Database Type (mysql, oracle, sqlsrv, mongo)');
        switch ($db) {
            case 'mysql':
                $this->PDO = $this->GenerateMySQL();
                break;
            default:
                die(Echos::Prints(sprintf("Sorry, database '%s' not yet supported.", $db)));
        }

        $statement = $this->PDO->prepare($this->query);
        $statement->execute();

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $key => $val) {

            echo Echos::Prints(sprintf("Creating model %s.php", $val['TABLE_NAME']));

            $statement = $this->PDO->prepare("DESC " . $val['TABLE_NAME']);
            $statement->execute();

            $column = $statement->fetchAll(PDO::FETCH_ASSOC);
            $property = "";
            $primary = "";

            foreach ($column as $k => $v) {
                if ($v['Key'] === 'PRI') {
                    $primary = $v['Field'];
                }
                $initValue = 'null';
                if (strpos($v['Type'], 'char') !== false) {
                    $initValue = "''";
                }
                if (strpos($v['Type'], 'text') !== false) {
                    $initValue = "''";
                }
                if (strpos($v['Type'], 'int') !== false) {
                    $initValue = 0;
                }
                if (strpos($v['Type'], 'double') !== false) {
                    $initValue = 0;
                }
                $property .= "
    /**
     * #Column " . $v['Field'] . " " . $v['Type'] . "
     */
    var $" . $v['Field'] . " = " . $initValue . ";
";

            }
            $model_file = file_get_contents("template/model/model");
            $model_file = str_replace('{{table}}', $val['TABLE_NAME'], $model_file);
            $model_file = str_replace('{{primary}}', $primary, $model_file);
            $model_file = str_replace('{{variables}}', $property, $model_file);
            if (!is_dir('plugins/model')) {
                mkdir('plugins/model');
            }
            file_put_contents("plugins/model/" . $val['TABLE_NAME'] . ".php", $model_file);
        }
    }

    public function GenerateMySQL()
    {
        $host = Input::Read('Hostname (Default: localhost)');
        $port = Input::Read('Port (Default: 3306)');
        $dbName = Input::Read('Database Name');

        $user = Input::Read('Username');
        $pass = Input::Read('Password');

        $pdoConnection = "mysql:host=$host;port=$port;dbname=$dbName";
        $dbi = new PDO($pdoConnection, $user, $pass);
        $dbi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->query = "SELECT TABLE_NAME 
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_NAME like '%' 
        AND TABLE_SCHEMA = '$dbName'";

        return $dbi;
    }

    public function GenerateOracle()
    {
        //todo: generate oracle database connection
    }

    public function GenerateSqlServer()
    {
        //todo: generate oracle database connection
    }

    public function GenerateMongo()
    {
        //todo: generate oracle database connection
    }

    public function __toString()
    {
        return 'Database setting completed';
    }

}