<?php

namespace pukoconsole;

use pukoconsole\util\Commons;
use pukoconsole\util\Echos;
use pukoconsole\util\Input;

/**
 * Class Models
 * @package pukoconsole
 */
class Models
{

    use Commons, Echos;

    private $name;

    private $schema;

    private $data_type = array(
        'int',
        'longtext',
        'number',
        'time',
        'decimal',
        'char',
        'varchar',
        'double',
        'text',
        'tinyint',
        'bigint',
        'bool',
        'boolean',
        'float',
        'string',
        'binary',
        'date',
        'blob',
        'longblob',
        'numeric',
        'datetime',
        'timestamp',
        'year',
        'bit',
        'enum',
    );

    public function __construct($root, $act, $model_name, $schema)
    {
        $this->schema = $schema;
        switch ($act) {
            case 'add':
                $this->add($root, $model_name, $schema);
                break;
            case 'update':
            case 'remove':
                break;
            default:
                die(Echos::Prints('Command not valid!'));
                break;
        }
    }

    public function add($root, $model_name, $schema)
    {
        if (empty($model_name)) {
            die(Echos::Prints('Model name required!'));
        }
        if (file_exists($root . "/plugins/model/{$schema}/{$this->name}.php")) {
            die(Echos::Prints('Model already exists! Please update instead creating new one.'));
        }

        $this->name = $model_name;

        $input = '';
        $data = array();
        while ($input !== 'no') {
            $column = Input::Read("Type column name");
            $type = Input::Read("Data type");
            while (!in_array($type, $this->data_type)) {
                $type = Input::Read("Data type");
            }
            $length = Input::Read("Data type (length)");
            $pk = $ai = $unsigned = '';
            if (in_array($type, array('int', 'integer', 'bigint'))) {
                $unsigned = Input::Read("Unsigned? (yes/no)");
                $pk = Input::Read("Primary key? (yes/no)");
                $ai = Input::Read("Auto increment? (yes/no)");
            }
            $notnull = Input::Read("Null-able? (yes/no)");
            $input = Input::Read("Add more column? (yes/no)");
            $data[] = array(
                'Field' => $column,
                'Type' => $type . (empty($length) ? "" : "({$length})"),
                'Null' => strtoupper($notnull),
                'Key' => ($pk === 'yes' ? 'PRI' : ''),
                'Default' => null,
                'Extra' => null,
                'AI' => ($ai === 'yes' ? true : false),
                'UNSIGNED' => ($unsigned === 'yes' ? true : false),
            );
        }

        $property = "";
        $primary = "";

        foreach ($data as $k => $v) {
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
            $property = str_replace('{{type}}', $v['Type'], $property);
            $property = str_replace('{{value}}', $initValue, $property);
        }

        $model_file = file_get_contents(__DIR__ . "/template/model/model");
        $model_file = str_replace('{{table}}', $this->name, $model_file);
        $model_file = str_replace('{{primary}}', $primary, $model_file);
        $model_file = str_replace('{{variables}}', $property, $model_file);
        $model_file = str_replace('{{schema}}', $schema, $model_file);

        if (!is_dir("{$root}/plugins/model/{$schema}")) {
            mkdir("{$root}/plugins/model/{$schema}", 0777, true);
        }
        file_put_contents($root . "/plugins/model/{$schema}/{$this->name}.php", $model_file);

        //region generate model test classes
        $test_file = file_get_contents(__DIR__ . "/template/model/model_contract_tests");
        $test_file = str_replace('{{table}}', $this->name, $test_file);

        if (!is_dir("{$root}/tests/unit/model/{$schema}")) {
            mkdir("{$root}/tests/unit/model/{$schema}", 0777, true);
        }
        if (!file_exists("{$root}/tests/unit/model/{$schema}/{$this->name}ModelTest.php")) {
            file_put_contents("{$root}/tests/unit/model/{$schema}/{$this->name}ModelTest.php", $test_file);
        }

        $keyval = "";
        $pointer = sizeof($data);
        foreach ($data as $field => $value) {
            if ($pointer == sizeof($data)) {
                if (!is_array($value)) {
                    $keyval .= "'{$field}' => {$value},\n";
                }
            } elseif ($pointer < sizeof($data) && $pointer > 1) {
                if (!is_array($value)) {
                    $keyval .= "    '{$field}' => {$value},\n";
                }
            } elseif ($pointer == 1) {
                if (!is_array($value)) {
                    $keyval .= "    '{$field}' => {$value}";
                }
            }
            $pointer--;
        }
        $destruct = "array(
            {$keyval}
            );";

        $test_file = file_get_contents(__DIR__ . "/template/controller/controller_tests");
        $test_file = str_replace('{{table}}', $this->name, $test_file);
        $test_file = str_replace('{{data}}', $destruct, $test_file);

        if (!is_dir("{$root}/tests/unit/controller/{$schema}")) {
            mkdir("{$root}/tests/unit/controller/{$schema}", 0777, true);
        }
        if (!file_exists("{$root}/tests/unit/controller/{$schema}/{$this->name}ControllerTest.php")) {
            file_put_contents("{$root}/tests/unit/controller/{$schema}/{$this->name}ControllerTest.php", $test_file);
        }
        //end region generate model test classes
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return Echos::Prints(
            "plugins/model/{$this->schema}/{$this->name}.php 
                | tests/unit/model/{$this->schema}/{$this->name}ModelTest.php 
                | tests/unit/controller/{$this->schema}/{$this->name}ControllerTest.php 
                created!");
    }

}