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

    var $driver = '';

    var $database = array();

    /**
     * Database constructor.
     * @param $root
     * @param $kinds
     * @param $selected_schema
     * @throws Exception
     */
    public function __construct($root, $kinds, $selected_schema)
    {
        if ($root === null) {
            die($this->Prints('Base url required!', true, 'light_red'));
        }

        $database = include "{$root}/config/database.php";
        if (isset($database[$selected_schema])) {
            $this->database = $database[$selected_schema];
        }

        $input = true;
        $configuration = array();
        while ($input) {
            $db = $this->Read('Database Type (mysql, oracle, sqlsrv, mongo) (selected: ' . $this->database['dbType'] . ')');
            if (strlen($db) <= 0) {
                $db = $this->database['dbType'];
            }
            $host = $this->Read('Hostname (selected: ' . $this->database['host'] . ')');
            if (strlen($host) <= 0) {
                $host = $this->database['host'];
            }
            $port = $this->Read('Port (selected: ' . $this->database['port'] . ')');
            if (strlen($port) <= 0) {
                $port = $this->database['port'];
            }
            $schema = $this->Read('Schema Name (selected: ' . $selected_schema . ')');
            if (strlen($schema) <= 0) {
                $schema = $selected_schema;
            }
            $dbName = $this->Read('Database Name (selected: ' . $this->database['dbName'] . ')');
            if (strlen($dbName) <= 0) {
                $dbName = $this->database['dbName'];
            }
            $user = $this->Read('Username (selected: ' . $this->database['user'] . ')');
            if (strlen($user) <= 0) {
                $user = $this->database['user'];
            }
            $pass = $this->Read('Password (selected: ******)');
            if (strlen($pass) <= 0) {
                $pass = $this->database['pass'];
            }
            $driver = $this->Read('Driver (selected: ' . $this->database['driver'] . ')');
            if (strlen($driver) <= 0) {
                $driver = $this->database['driver'];
            }
            $ignored = $this->Read('Ignored Table Prefix (selected: ' . $this->database['ignoreTableWithPrefix'] . ')');
            if (strlen($ignored) <= 0) {
                $ignored = '_';
            }
            $hide = $this->Read('Hide Column (Example: created,modified,cuid,muid,dflag,password)');
            if (strlen($hide) <= 0) {
                $hide = 'created,modified,cuid,muid,dflag,password';
            }

            $hide = explode(',', $hide);
            $hide_store = [];
            foreach ($hide as $key => $val) {
                $val = trim($val);
                $hide[$key] = $val;

                $hide_store[] = "'{$val}'";
            }

            $this->driver = $driver;

            $cf = file_get_contents(__DIR__ . "/template/config/database_item");

            $cf = str_replace('{{type}}', $db, $cf);
            $cf = str_replace('{{schema}}', $schema, $cf);
            $cf = str_replace('{{host}}', $host, $cf);
            $cf = str_replace('{{user}}', $user, $cf);
            $cf = str_replace('{{pass}}', $pass, $cf);
            $cf = str_replace('{{dbname}}', $dbName, $cf);
            $cf = str_replace('{{port}}', $port, $cf);
            $cf = str_replace('{{driver}}', $driver, $cf);
            $cf = str_replace('{{ignored}}', $ignored, $cf);
            $cf = str_replace('{{hide}}', implode(", ", $hide_store), $cf);

            $configuration[$schema] = $cf;

            if ($kinds === 'setup') {
                $this->Setup($root, $db, $host, $port, $dbName, $user, $pass, $schema, $ignored, $hide);
            }
            if ($kinds === 'refresh') {
                $this->Setup($root, $db, $host, $port, $dbName, $user, $pass, $schema, $ignored, $hide);
            }
            if ($kinds === 'generate') {
                $this->Generate($root, $db, $host, $port, $dbName, $user, $pass, $schema, $ignored, $hide);
            }

            $more = $this->Read('Add another database connection schema? (y/n)');
            if ($more !== 'y') {
                $input = false;
            }
        }

        $freshadd = false;
        if ($kinds !== 'refresh') {
            $database = file_get_contents("{$root}/config/database.php");
            if (strpos($database, 'return $db;') === false) {
                $freshadd = true;
                $database = file_get_contents(__DIR__ . "/template/config/database");
            }
            $holder = "\n";

            foreach ($configuration as $item) {
                $holder .= $item;
                $holder .= "\n";
            }
            if ($freshadd) {
                $database = str_replace('{{configuration}}', $holder, $database);
            } else {
                $database = str_replace('return $db;', $holder . "\n" . 'return $db;', $database);
            }
            file_put_contents("{$root}/config/database.php", $database);
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
     * @param $schema
     */
    public function Setup($root, $db, $host, $port, $dbName, $user, $pass, $schema, $ignored, $hide)
    {
        switch ($db) {
            case 'mysql':
                $this->PDO = $this->SetupMySQL($host, $port, $dbName, $user, $pass);
                break;
            case 'oracle':
                $this->SetupOracle($host, $port, $dbName, $user, $pass);
                break;
            case 'sqlsrv':
                $this->PDO = $this->SetupSqlServer($host, $port, $dbName, $user, $pass);
                break;
            case 'mongo':
                $this->SetupMongo($host, $port, $dbName, $user, $pass);
                break;
            default:
                die($this->Prints(sprintf("Sorry, database '%s' not yet supported.", $db), true, 'light_red'));
        }

        $statement = $this->PDO->prepare($this->query);
        $statement->execute();

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $key => $val) {
            if (substr($val['TABLE_NAME'], 0, 1) !== $ignored) {
                echo $this->Prints("Creating model {$val['TABLE_NAME']}.php on schema {$schema}", false);

                if ($db === 'mysql') {
                    $statement = $this->PDO->prepare("DESC " . $val['TABLE_NAME']);
                }
                if ($db === 'sqlsrv') {
                    $statement = $this->PDO->prepare("exec sp_columns " . $val['TABLE_NAME']);
                }
                $statement->execute();

                $column = $statement->fetchAll(PDO::FETCH_ASSOC);
                $property = "";
                $primary = "";

                $fieldlist = [];
                $fieldlist_filtered = [];
                $data = [];

                foreach ($column as $v) {
                    if ($db === 'mysql') {
                        $initValue = 'null';
                        $fieldlist[] = strtolower($v['Field']);

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
                        if (strpos($v['Type'], 'decimal') !== false) {
                            $initValue = 0.0;
                        }

                        $data[$v['Field']] = $initValue;

                        $property .= file_get_contents(__DIR__ . "/template/model/model_vars");
                        $property = str_replace('{{field}}', $v['Field'], $property);
                        $nullable = ($v['Null'] === 'YES') ? '' : 'not null';
                        $property = str_replace('{{type}}', "{$v['Type']} $nullable {$v['Extra']}", $property);
                        $property = str_replace('{{value}}', $initValue, $property);
                    }
                    if ($db === 'sqlsrv') {
                        $initValue = 'null';
                        $fieldlist[] = strtolower($v['COLUMN_NAME']);

                        if (strpos($v['TYPE_NAME'], 'identity') !== false) {
                            $primary = $v['COLUMN_NAME'];
                        }

                        if (strpos($v['TYPE_NAME'], 'char') !== false) {
                            $initValue = "''";
                        }
                        if (strpos($v['TYPE_NAME'], 'text') !== false) {
                            $initValue = "''";
                        }
                        if (strpos($v['TYPE_NAME'], 'int') !== false) {
                            $initValue = 0;
                        }
                        if (strpos($v['TYPE_NAME'], 'double') !== false) {
                            $initValue = 0;
                        }

                        $data[$v['COLUMN_NAME']] = $initValue;

                        $property .= file_get_contents(__DIR__ . "/template/model/model_vars");
                        $property = str_replace('{{field}}', $v['COLUMN_NAME'], $property);
                        $nullable = ($v['IS_NULLABLE'] === 'YES') ? '' : 'not null';
                        $property = str_replace('{{type}}', "{$v['TYPE_NAME']} $nullable", $property);
                        $property = str_replace('{{value}}', $initValue, $property);
                    }
                }

                foreach ($column as $v) {
                    if (!in_array($v['Field'], $hide)) {
                        if ($db === 'mysql') {
                            $fieldlist_filtered[] = strtolower($v['Field']);
                        }
                        if ($db === 'sqlsrv') {
                            $fieldlist_filtered[] = strtolower($v['COLUMN_NAME']);
                        }
                    }
                }

                $model_file = file_get_contents(__DIR__ . "/template/model/model");
                $model_file = str_replace('{{table}}', $val['TABLE_NAME'], $model_file);
                $model_file = str_replace('{{dbtype}}', $db, $model_file);
                $model_file = str_replace('{{primary}}', $primary, $model_file);
                $model_file = str_replace('{{variables}}', $property, $model_file);
                $model_file = str_replace('{{schema}}', $schema, $model_file);

                if (!is_dir("{$root}/plugins/model/{$schema}")) {
                    mkdir("{$root}/plugins/model/{$schema}", 0777, true);
                }
                file_put_contents($root . "/plugins/model/{$schema}/{$val['TABLE_NAME']}.php", $model_file);

                //region generate model test classes
                $test_file = file_get_contents(__DIR__ . "/template/model/model_contract_tests");
                $test_file = str_replace('{{table}}', $val['TABLE_NAME'], $test_file);

                if (!is_dir("{$root}/tests/unit/model/{$schema}")) {
                    mkdir("{$root}/tests/unit/model/{$schema}", 0777, true);
                }
                if (!file_exists("{$root}/tests/unit/model/{$schema}/{$val['TABLE_NAME']}ModelTest.php")) {
                    file_put_contents("{$root}/tests/unit/model/{$schema}/{$val['TABLE_NAME']}ModelTest.php", $test_file);
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

                if (!is_dir("{$root}/tests/unit/controller/{$schema}")) {
                    mkdir("{$root}/tests/unit/controller/{$schema}", 0777, true);
                }
                if (!file_exists("{$root}/tests/unit/controller/{$schema}/{$val['TABLE_NAME']}ControllerTest.php")) {
                    file_put_contents("{$root}/tests/unit/controller/{$schema}/{$val['TABLE_NAME']}ControllerTest.php", $test_file);
                }
                //end region generate model test classes

                //region model contracts
                if ($db === 'sqlsrv') {
                    $contracts_file = file_get_contents(__DIR__ . "/template/model/model_contract_sqlsrv");
                } else {
                    $contracts_file = file_get_contents(__DIR__ . "/template/model/model_contract_mysql");
                }
                $contracts_file = str_replace('{{table}}', $val['TABLE_NAME'], $contracts_file);
                $contracts_file = str_replace('{{schema}}', $schema, $contracts_file);
                $contracts_file = str_replace('{{primary}}', $primary, $contracts_file);
                $contracts_file = str_replace('{{primary-conditions}}', "$primary = @1", $contracts_file);
                $contracts_file = str_replace('{{conditions}}', "dflag = 0", $contracts_file);

                $contracts_file = str_replace('{{column}}', implode(', ', $fieldlist_filtered), $contracts_file);
                $contracts_file = str_replace('{{table-specs}}', '"' . implode('",' . PHP_EOL . '"', $fieldlist_filtered) . '"', $contracts_file);

                if (!is_dir("{$root}/model/{$schema}")) {
                    mkdir("{$root}/model/{$schema}", 0777, true);
                }
                if (!file_exists("{$root}/model/{$schema}/{$val['TABLE_NAME']}Contracts.php")) {
                    file_put_contents("{$root}/model/{$schema}/{$val['TABLE_NAME']}Contracts.php", $contracts_file);
                }
                //end of region model contracts
            }
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
     * @param $schema
     * @throws Exception
     */
    public function Generate($root, $db, $host, $port, $dbName, $user, $pass, $schema, $ignored, $hide)
    {
        switch ($db) {
            case 'mysql':
                $this->GenerateMySQL($root, $host, $port, $dbName, $user, $pass, $schema);
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
                die($this->Prints(sprintf("Sorry, database '%s' not yet supported.", $db), true, 'light_red'));
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
                $this->query = "SELECT TABLE_NAME, TABLE_TYPE, ENGINE
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = '{$dbName}'
                AND TABLE_TYPE = 'BASE TABLE'
                ORDER BY TABLE_TYPE ASC;";
            }
            return $dbi;
        } catch (Exception $ex) {
            die($this->Prints("Failed to connect.", true, 'light_red'));
        }

    }

    public function SetupOracle($host, $port, $dbName, $user, $pass)
    {
        //todo: generate oracle support via PDO interface
        die($this->Prints("Sorry, this option not yet supported."));
    }

    public function SetupSqlServer($host, $port, $dbName, $user, $pass)
    {
        try {
            if ($this->driver === 'odbc') {
                //connection from pdo_odbc
                $pdoConnection = "odbc:Driver={SQL Server};Server=$host";
                if (strlen($dbName) > 0) {
                    $pdoConnection = "odbc:Driver={SQL Server};Server=$host;Database=$dbName";
                }
            }
            if ($this->driver === 'sqlsrv') {
                //connection from pdo_sqlsrv
                $pdoConnection = "sqlsrv:Server=$host,$port";
                if (strlen($dbName) > 0) {
                    $pdoConnection = "sqlsrv:Server=$host,$port;Database=$dbName";
                }
            }

            $dbi = new PDO($pdoConnection, $user, $pass);
            $dbi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if (strlen($dbName) > 0) {
                $this->query = "SELECT TABLE_NAME, TABLE_TYPE, TABLE_SCHEMA
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_CATALOG = '{$dbName}'
                AND TABLE_TYPE = 'BASE TABLE'
                ORDER BY TABLE_TYPE ASC;";
            }
            return $dbi;
        } catch (Exception $ex) {
            die($this->Prints("Failed to connect.", true, 'light_red'));
        }
    }

    public function SetupMongo($host, $port, $dbName, $user, $pass)
    {
        //todo: generate mongo support via PDO interface
        die($this->Prints("Sorry, this option not yet supported."));
    }

    /**
     * @param $root
     * @param $host
     * @param $port
     * @param $dbName
     * @param $user
     * @param $pass
     * @param $schema
     * @throws Exception
     */
    public function GenerateMySQL($root, $host, $port, $dbName, $user, $pass, $schema)
    {
        if (strlen($dbName) === 0) {
            die($this->Prints('Database connection setup required.', true, 'light_red'));
        }
        $this->PDO = $this->SetupMySQL($host, $port, '', $user, $pass);

        $fileList = scandir($root . '/plugins/model/' . $schema);
        foreach ($fileList as $file) {
            if (in_array('php', explode('.', $file))) {
                $file = explode('.', $file);
                $model = '\\plugins\\model\\' . $schema . '\\' . $file[0];
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

                echo $this->Prints(sprintf("Generating model %s.php", $positioning['Table']), false);

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
        preg_match_all('(#[ a-zA-Z0-9-:.,+()/_\\\@]+)', $raw_docs, $result, PREG_PATTERN_ORDER);
        if (count($result[0]) > 0) {
            foreach ($result[0] as $key => $value) {

                $preg = explode(' ', $value);

                $data['clause'][$key] = str_replace('#', '', $preg[0]);
                $data['command'][$key] = $preg[1];

                $data['value'][$key] = '';

                foreach ($preg as $k => $v) {
                    switch ($k) {
                        case 0:
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
        return $this->Prints('Database setting completed', true, 'green');
    }

}
