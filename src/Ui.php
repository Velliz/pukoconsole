<?php

namespace pukoconsole;

use Exception;
use PDO;
use pukoconsole\util\Commons;
use pukoconsole\util\Echos;
use pukoconsole\util\Input;

/**
 * Class Ui
 * @package pukoconsole
 */
class Ui
{

    use Echos, Commons;

    var $root = '';

    var $table = '';

    var $schema = '';

    /**
     * @var PDO
     */
    var $PDO;

    var $lang = array('en', 'id');

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
        $this->root = $root;

        echo Echos::Prints('Make sure you already have Backend API for handle crud process!', true, 'yellow');

        $this->schema = Input::Read('Database Schema (primary)');
        if (strlen($this->schema) <= 0) {
            $this->schema = 'primary';
        }
        $this->table = Input::Read('Table');
        if (strlen($this->table) <= 0) {
            die(Echos::Prints('Table required!', true, 'light_red'));
        }

        $database = include("{$root}/config/database.php");
        $dbconn = $database[$this->schema];

        switch ($dbconn['dbType']) {
            case 'mysql':
                $this->PDO = $this->SetupMySQL($dbconn['host'], $dbconn['port'], $dbconn['dbName'], $dbconn['user'], $dbconn['pass']);
                $statement = $this->PDO->prepare("DESC " . $this->table);
                break;
            case 'sqlsrv':
                $this->PDO = $this->SetupSqlServer($dbconn['host'], $dbconn['port'], $dbconn['dbName'], $dbconn['user'], $dbconn['pass']);
                $statement = $this->PDO->prepare("exec sp_columns " . $this->table);
                break;
            default:
                die(Echos::Prints(sprintf("Sorry, database '%s' not yet supported.", $dbconn['dbType']), true, 'light_red'));
        }

        $statement->execute();
        $column = $statement->fetchAll(PDO::FETCH_ASSOC);

        $fields = [];
        switch ($kinds) {
            case "datatables":
                //routes exists check
                $routes = include "{$root}/config/routes.php";
                if (isset($routes['router']["{$this->schema}/{$this->table}"])) {
                    die(Echos::Prints("Aborting! Routes '{$this->schema}/{$this->table}' already registered!", true, 'light_red'));
                }

                //get database column
                foreach ($column as $k => $v) {
                    if ($dbconn['dbType'] === 'mysql') {
                        $fields[] = strtolower($v['Field']);
                    }
                    if ($dbconn['dbType'] === 'sqlsrv') {
                        $fields[] = strtolower($v['COLUMN_NAME']);
                    }
                }

                //thead pre-populate data
                $thead = "";
                foreach ($fields as $field) {
                    $thead_tpl = file_get_contents(__DIR__ . "/template/assets/ui/datatables/thead");
                    $thead_tpl = str_replace('{{column}}', $field, $thead_tpl);
                    $thead .= $thead_tpl;
                }

                //form-group pre-populate data
                $form_group = "";
                foreach ($fields as $field) {
                    $form_group_tpl = file_get_contents(__DIR__ . "/template/assets/ui/datatables/form_group");
                    $form_group_tpl = str_replace('{{column}}', $field, $form_group_tpl);
                    $form_group .= $form_group_tpl;
                }

                //var-group pre-populate data
                $var_group = "";
                foreach ($fields as $field) {
                    $var_group_tpl = file_get_contents(__DIR__ . "/template/assets/ui/datatables/var_group");
                    $var_group_tpl = str_replace('{{column}}', $field, $var_group_tpl);
                    $var_group .= $var_group_tpl;
                }

                //assign-group pre-populate data
                $assign_group = "";
                foreach ($fields as $field) {
                    $assign_group_tpl = file_get_contents(__DIR__ . "/template/assets/ui/datatables/assign_group");
                    $assign_group_tpl = str_replace('{{column}}', $field, $assign_group_tpl);
                    $assign_group_tpl = str_replace('{{table}}', $this->table, $assign_group_tpl);
                    $assign_group .= $assign_group_tpl;
                }

                //rowcallback-group pre-populate data
                $rowcallback_group = "";
                foreach ($fields as $key => $field) {
                    $rowcallback_group_tpl = file_get_contents(__DIR__ . "/template/assets/ui/datatables/rowcallback_group");
                    if ($key === sizeof($fields) - 1) {
                        $rowcallback_group_tpl = str_replace('{{idx}}', "$('td:eq({{idx}})', row).html(updates + deletes);", $rowcallback_group_tpl);
                    } else {
                        $rowcallback_group_tpl = str_replace('{{idx}}', $key, $rowcallback_group_tpl);
                    }
                    $rowcallback_group .= $rowcallback_group_tpl;
                }

                $js = "assets/script/ui/{$this->schema}/{$this->table}.js";

                $this->ProcessController('controller/ui', $this->schema, $this->table, 'view');
                $this->ProcessPresentation('ui/' . $this->schema, $this->table, [
                    'table' => $this->table,
                    'thead' => $thead,
                    'form_group' => $form_group,
                    'js' => $js,
                ]);
                $this->ProcessLogic($this->schema, $this->table, [
                    'table' => $this->table,
                    'var_group' => $var_group,
                    'rowcallback_group' => $rowcallback_group,
                    'assign_group' => $assign_group
                ]);

                //routes appending
                $routes['router']["{$this->schema}/{$this->table}"] = [
                    "controller" => "ui\\{{$this->schema}}",
                    "function" => $this->table,
                    "accept" => [
                        "GET"
                    ]
                ];
                //sort ascending the routes definitions
                ksort($routes['router']);

                file_put_contents(
                    "{$this->root}/config/routes.php",
                    '<?php $routes = ' . $this->var_export54($routes) . '; return $routes;'
                );

                break;
            default:
                throw new Exception('UI type not supported');
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
            return $dbi;
        } catch (Exception $ex) {
            die(Echos::Prints("Failed to connect.", true, 'light_red'));
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
    public function SetupSqlServer($host, $port, $dbName, $user, $pass)
    {
        try {
            $pdoConnection = "odbc:Driver={SQL Server};Server=$host";
            if (strlen($dbName) > 0) {
                $pdoConnection = "odbc:Driver={SQL Server};Server=$host;Database=$dbName";
            }
            $dbi = new PDO($pdoConnection, $user, $pass);
            $dbi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $dbi;
        } catch (Exception $ex) {
            die(Echos::Prints("Failed to connect.", true, 'light_red'));
        }
    }

    /**
     * @param $namespace
     * @param $class
     * @param $function
     * @param $kind
     */
    public function ProcessController($namespace, $class, $function, $kind)
    {
        $dir = "{$this->root}/{$namespace}";
        $path = "{$this->root}/{$namespace}/{$class}.php";
        $replacement = "    public function {$function}() {}\n\n}";

        if (!file_exists($path)) {
            if (strpos($namespace, '/') !== false) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
            }
            $ctrl = file_get_contents(__DIR__ . "/template/controller/{$kind}");
            $ctrl = str_replace("{{namespace}}", Routes::StringReplaceBackSlash($namespace), $ctrl);
            $ctrl = str_replace("{{class}}", $class, $ctrl);
        } else {
            $ctrl = file_get_contents($path);
        }

        $last_colon = strrpos($ctrl, "}");
        $ctrl = substr_replace($ctrl, $replacement, $last_colon);

        file_put_contents($path, $ctrl);
    }

    /**
     * @param $controller
     * @param $function
     * @param $payloads
     */
    public function ProcessPresentation($controller, $function, $payloads)
    {
        $controller = Routes::StringReplaceSlash($controller);
        foreach ($this->lang as $key => $val) {
            $ctrl = "{$this->root}/assets/html/{$val}/{$controller}";
            if (!is_dir($ctrl)) {
                mkdir($ctrl, 0777, true);
            }
            $html = file_get_contents(__DIR__ . "/template/assets/ui/datatables/presentation");
            $html = str_replace('{{table}}', $payloads['table'], $html);
            $html = str_replace('{{thead}}', $payloads['thead'], $html);
            $html = str_replace('{{form-group}}', $payloads['form_group'], $html);
            $html = str_replace('{{js}}', $payloads['js'], $html);

            $fname = "{$this->root}/assets/html/{$val}/{$controller}/{$function}.html";
            file_put_contents($fname, $html);
        }
    }

    /**
     * @param $controller
     * @param $function
     * @param $payloads
     */
    public function ProcessLogic($controller, $function, $payloads)
    {
        $controller = Routes::StringReplaceSlash($controller);
        $ctrl = "{$this->root}/assets/scripts/ui/{$controller}";
        if (!is_dir($ctrl)) {
            mkdir($ctrl, 0777, true);
        }
        $js = file_get_contents(__DIR__ . "/template/assets/ui/datatables/logic");
        $js = str_replace('{{table}}', $payloads['table'], $js);
        $js = str_replace('{{api-table}}', "{$function}/table", $js);
        $js = str_replace('{{api-update}}', $function . '/${id}/update', $js);
        $js = str_replace('{{api-delete}}', $function . '/${id}/delete', $js);
        $js = str_replace('{{api-read}}', $function . '/${id}', $js);
        $js = str_replace('{{var-group}}', $payloads['var_group'], $js);
        $js = str_replace('{{rowcallback-group}}', $payloads['rowcallback_group'], $js);
        $js = str_replace('{{assign-group}}', $payloads['assign_group'], $js);

        $fname = "{$this->root}/assets/scripts/ui/{$controller}/{$function}.js";
        file_put_contents($fname, $js);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return Echos::Prints('Generate UI setting completed', true, 'green');
    }


}
