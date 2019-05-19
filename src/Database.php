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

use Exception;
use PDO;
use ReflectionClass;
use pukoconsole\util\Echos;
use pukoconsole\util\Input;

/**
 * Class Database
 * @package pukoconsole
 */
class Database
{

    use Input, Echos;

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
     * @param $kinds
     * @throws \ReflectionException
     */
    public function __construct($root = null, $kinds)
    {
        if ($root === null) {
            die(Echos::Prints('Base url required'));
        }

        $input = true;
        $configuration = array();
        while ($input) {
            $db = Input::Read('Database Type (mysql, oracle, sqlsrv, mongo)');
            if (strlen($db) <= 0) {
                $db = 'mysql';
            }
            $host = Input::Read('Hostname (Default: localhost)');
            if (strlen($host) <= 0) {
                $host = 'localhost';
            }
            $port = Input::Read('Port (Default: 3306)');
            if (strlen($port) <= 0) {
                $port = '3306';
            }
            $schema = Input::Read('Schema Name (primary)');
            if (strlen($schema) <= 0) {
                $schema = 'primary';
            }
            $dbName = Input::Read('Database Name');
            $user = Input::Read('Username');
            $pass = Input::Read('Password');

            $cf = file_get_contents(__DIR__ . "/template/config/database_item");

            $cf = str_replace('{{type}}', $db, $cf);
            $cf = str_replace('{{schema}}', $schema, $cf);
            $cf = str_replace('{{host}}', $host, $cf);
            $cf = str_replace('{{user}}', $user, $cf);
            $cf = str_replace('{{pass}}', $pass, $cf);
            $cf = str_replace('{{dbname}}', $dbName, $cf);
            $cf = str_replace('{{port}}', $port, $cf);

            $configuration[$schema] = $cf;

            if ($kinds === 'setup') {
                $this->Setup($root, $db, $host, $port, $dbName, $user, $pass);
            }
            if ($kinds === 'generate') {
                $this->Generate($root, $db, $host, $port, $dbName, $user, $pass);
            }

            $more = Input::Read('Tambahkan koneksi lain? (y/n)');
            if ($more !== 'y') {
                $input = false;
            }
        }

        $database = file_get_contents(__DIR__ . "/template/config/database");
        $holder = "";

        foreach ($configuration as $item) {
            $holder .= $item;
            $holder .= "\n";
        }

        $database = str_replace('{{configuration}}', $holder, $database);
        file_put_contents("{$root}/config/database.php", $database);

    }

    public function Setup($root, $db, $host, $port, $dbName, $user, $pass)
    {
        switch ($db) {
            case 'mysql':
                $this->PDO = $this->SetupMySQL($host, $port, $dbName, $user, $pass);
                break;
            case 'oracle':
                $this->SetupOracle($host, $port, $dbName, $user, $pass);
                break;
            case 'sqlsrv':
                $this->SetupSqlServer($host, $port, $dbName, $user, $pass);
                break;
            case 'mongo':
                $this->SetupMongo($host, $port, $dbName, $user, $pass);
                break;
            default:
                die(Echos::Prints(sprintf("Sorry, database '%s' not yet supported.", $db)));
        }

        $statement = $this->PDO->prepare($this->query);
        $statement->execute();

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $key => $val) {

            echo Echos::Prints(sprintf("Creating model %s.php", $val['TABLE_NAME']), false);

            $statement = $this->PDO->prepare("DESC " . $val['TABLE_NAME']);
            $statement->execute();

            $column = $statement->fetchAll(PDO::FETCH_ASSOC);
            $property = "";
            $primary = "";

            $data = [];

            foreach ($column as $k => $v) {
                $initValue = 'null';

                if ($v['Key'] === 'PRI') {
                    $primary = $v['Field'];
                }

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

                $data[$v['Field']] = $initValue;

                $property .= file_get_contents(__DIR__ . "/template/model/model_vars");
                $property = str_replace('{{field}}', $v['Field'], $property);
                $property = str_replace('{{type}}', "{$v['Type']} {$v['Extra']}", $property);
                $property = str_replace('{{value}}', $initValue, $property);
            }

            $model_file = file_get_contents(__DIR__ . "/template/model/model");
            $model_file = str_replace('{{table}}', $val['TABLE_NAME'], $model_file);
            $model_file = str_replace('{{primary}}', $primary, $model_file);
            $model_file = str_replace('{{variables}}', $property, $model_file);

            if (!is_dir("{$root}/plugins/model")) {
                mkdir("{$root}/plugins/model");
            }
            file_put_contents($root . "/plugins/model/" . $val['TABLE_NAME'] . ".php", $model_file);

            //region generate model test classes
            $test_file = file_get_contents(__DIR__ . "/template/model/model_contract_tests");
            $test_file = str_replace('{{table}}', $val['TABLE_NAME'], $test_file);

            if (!is_dir("{$root}/tests/unit/model")) {
                mkdir("{$root}/tests/unit/model");
            }
            if (!file_exists("{$root}/tests/unit/model/{$val['TABLE_NAME']}ModelTest.php")) {
                file_put_contents("{$root}/tests/unit/model/{$val['TABLE_NAME']}ModelTest.php", $test_file);
            }

            $keyval = "";
            $pointer = sizeof($data);
            foreach ($data as $field => $value) {
                if ($pointer == sizeof($data)) {
                    $keyval .= "'{$field}' => {$value},\n";
                } elseif ($pointer < sizeof($data) && $pointer > 1) {
                    $keyval .= "            '{$field}' => {$value},\n";
                } elseif ($pointer == 1) {
                    $keyval .= "            '{$field}' => {$value}";
                }
                $pointer--;
            }
            $destruct = "array(
            {$keyval}
            );";

            $test_file = file_get_contents(__DIR__ . "/template/controller/controller_tests");
            $test_file = str_replace('{{table}}', $val['TABLE_NAME'], $test_file);
            $test_file = str_replace('{{data}}', $destruct, $test_file);

            if (!is_dir("{$root}/tests/unit/controller")) {
                mkdir("{$root}/tests/unit/controller");
            }
            if (!file_exists("{$root}/tests/unit/controller/{$val['TABLE_NAME']}ControllerTest.php")) {
                file_put_contents("{$root}/tests/unit/controller/{$val['TABLE_NAME']}ControllerTest.php", $test_file);
            }
            //end region generate model test classes
        }
    }

    /**
     * @param $root
     * @param $db
     * @param $host
     * @param $port
     * @param $dbName
     * @param $user
     * @param $pass
     * @throws \ReflectionException
     */
    public function Generate($root, $db, $host, $port, $dbName, $user, $pass)
    {
        switch ($db) {
            case 'mysql':
                $this->GenerateMySQL($root, $host, $port, $dbName, $user, $pass);
                break;
            case 'oracle':
                //$this->GenerateOracle($host, $port, $dbName, $user, $pass);
                break;
            case 'sqlsrv':
                //$this->GenerateSqlServer($host, $port, $dbName, $user, $pass);
                break;
            case 'mongo':
                //$this->GenerateMongo($host, $port, $dbName, $user, $pass);
                break;
            default:
                die(Echos::Prints(sprintf("Sorry, database '%s' not yet supported.", $db)));
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
    public function SetupMySQL($host, $port, $dbName, $user, $pass)
    {
        try {
            $pdoConnection = "mysql:host=$host;port=$port;";
            if (strlen($dbName) > 0) {
                $pdoConnection = "mysql:host=$host;port=$port;dbname=$dbName";
            }
            $dbi = new PDO($pdoConnection, $user, $pass);
            $dbi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if (strlen($dbName) > 0) {
                $this->query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_NAME LIKE '%' AND TABLE_SCHEMA = '{$dbName}'";
            }

            return $dbi;
        } catch (Exception $ex) {
            die(Echos::Prints("Failed to connect."));
        }

    }

    public function SetupOracle($host, $port, $dbName, $user, $pass)
    {
        //todo: generate oracle support via PDO interface
        die(Echos::Prints("Sorry, this option not yet supported."));
    }

    public function SetupSqlServer($host, $port, $dbName, $user, $pass)
    {
        //todo: generate mssql support via PDO interface
        die(Echos::Prints("Sorry, this option not yet supported."));
    }

    public function SetupMongo($host, $port, $dbName, $user, $pass)
    {
        //todo: generate mongo support via PDO interface
        die(Echos::Prints("Sorry, this option not yet supported."));
    }

    /**
     * @param $root
     * @param $host
     * @param $port
     * @param $dbName
     * @param $user
     * @param $pass
     * @throws \ReflectionException
     * @throws Exception
     */
    public function GenerateMySQL($root, $host, $port, $dbName, $user, $pass)
    {
        if (strlen($dbName) === 0) {
            throw new Exception('Database connection setup required.');
        }
        $this->PDO = $this->SetupMySQL($host, $port, '', $user, $pass);

        $fileList = scandir($root . '/plugins/model');
        foreach ($fileList as $file) {
            if (in_array('php', explode('.', $file))) {
                $file = explode('.', $file);
                $model = '\\plugins\\model\\' . $file[0];
                $object = new $model;

                $pdc = new ReflectionClass($object);
                $global = $this->PDCParser($pdc->getDocComment());

                $positioning = array();
                foreach ($global['clause'] as $key => $item) {
                    $positioning[$global['clause'][$key]] = $global['command'][$key];
                }

                $statement = $this->PDO->prepare("CREATE DATABASE IF NOT EXISTS {$dbName};");
                $statement->execute();
                $statement = $this->PDO->prepare("USE {$dbName};");
                $statement->execute();

                $primary = false;
                $tablesql = "CREATE TABLE IF NOT EXISTS {$positioning['Table']} ( \n";
                foreach ($pdc->getProperties() as $prop) {
                    $column = $this->PDCParser($prop->getDocComment());
                    $col = isset($column['command'][0]) ? $column['command'][0] : '';
                    $attr = isset($column['value'][0]) ? $column['value'][0] : '';
                    $cmd = isset($column['command'][0]) ? $column['command'][0] : '';

                    if ($col !== '' && $attr !== '') {
                        $tablesql .= "{$col} {$attr}, \n";
                    }

                    if ($positioning['PrimaryKey'] === $cmd) {
                        $primary = true;
                    }
                }
                if ($primary) {
                    $tablesql .= "PRIMARY KEY ({$positioning['PrimaryKey']}) \n";
                }
                $tablesql .= ")";

                echo Echos::Prints(sprintf("Generating model %s.php", $positioning['Table']), false);

                $statement = $this->PDO->prepare($tablesql);
                $statement->execute();
            }
        }
    }

    /**
     * @param $raw_docs
     * returned from controller
     *
     * @return array
     * @throws Exception
     */
    public function PDCParser($raw_docs)
    {
        $data = array();
        preg_match_all('(#[ a-zA-Z0-9-:.+()/_\\\@]+)', $raw_docs, $result, PREG_PATTERN_ORDER);
        if (count($result[0]) > 0) {
            foreach ($result[0] as $key => $value) {

                $preg = explode(' ', $value);

                $data['clause'][$key] = str_replace('#', '', $preg[0]);
                $data['command'][$key] = $preg[1];

                $data['value'][$key] = '';

                foreach ($preg as $k => $v) {
                    switch ($k) {
                        case 0:
                            break;
                        case 1:
                            break;
                        default:
                            if ($k !== sizeof($preg) - 1) {
                                $data['value'][$key] .= $v . ' ';
                            } else {
                                $data['value'][$key] .= $v;
                            }
                            break;
                    }
                }
            }
        }
        return $data;
    }

    public function __toString()
    {
        return Echos::Prints('Database setting completed');
    }

}