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
use ReflectionClass;
use pukoconsole\util\Commons;
use pukoconsole\util\Echos;
use pukoconsole\util\Input;

/**
 * Class Routes
 * @package pukoconsole
 */
class Routes
{

    use Echos, Input, Commons;

    var $directive = null;
    var $action = null;
    var $attribute = null;
    var $root = null;

    var $lang = array('en', 'id');

    /**
     * @var array|mixed
     */
    var $routes = array();
    var $database = array();

    /**
     * Routes constructor
     * @param $root
     * @param $directive
     * @param $action
     * @param $attribute
     * @throws Exception
     */
    public function __construct($root, $directive, $action, $attribute)
    {
        if ($root === null) {
            die($this->Prints('Base url required!', true, 'light_red'));
        }
        if ($attribute === null) {
            $attribute = "";
        }
        $this->root = $root;

        $this->directive = $directive;
        $this->action = $action;

        $attribute = str_replace("?", "{?}", $attribute);

        $this->attribute = $attribute;

        $this->routes = include "{$root}/config/routes.php";
        $this->database = include "{$root}/config/database.php";

        if (in_array($this->directive, array('view', 'service', 'console', 'socket'))) {
            $this->structure($this->routes['router']);
        } else if (in_array($this->directive, array('error', 'lost'))) {
            $this->errorOrLost($this->directive);
        } else if ($this->directive === 'list') {
            $this->lists($this->routes['router']);
        } else if ($this->directive === 'dir') {
            $this->directories($this->routes['router']);
        } else if ($this->directive === 'resort') {
            $this->resorts($this->routes['router']);
        } else {
            if (!isset($this->routes[$this->directive])) {
                die($this->Prints('Command not supported!', true, 'light_red'));
            }
            $this->structure($this->routes[$this->directive]);
        }
    }

    /**
     * @param $pages
     * @throws Exception
     */
    public function structure($pages)
    {
        switch ($this->action) {
            case 'add':
                $this->add($pages);
                break;
            case 'update':
                $this->update($pages);
                break;
            case 'remove':
                $this->remove();
                break;
            case 'crud':
                $this->crud($pages);
                break;
            default:
                die($this->Prints('Command not supported!', true, 'light_red'));
        }
    }

    /**
     * @param $segment
     * @return string
     */
    public function add($segment)
    {
        if (isset($segment[$this->attribute])) {
            die($this->Prints("Routes already registered!", true, 'light_red'));
        }

        $controller = $this->Read('Controller (use \ to place in sub-directories) ex "entities\\reports"');
        $function = $this->Read('Function name');
        if ($this->directive === 'console' || $this->directive === 'socket') {
            $accept = 'get';
        } else {
            $accept = $this->Read('Accept? [GET,POST,PUT,PATCH,DELETE] multiple by commas');
        }

        $data = [
            "controller" => $controller,
            "function" => $function,
            "accept" => explode(",", strtoupper($accept))
        ];
        $this->routes['router'][$this->attribute] = $data;

        //sort ascending the routes definitions
        ksort($this->routes['router']);

        file_put_contents(
            "{$this->root}/config/routes.php",
            '<?php $routes = ' . $this->var_export54($this->routes) . '; return $routes;'
        );

        if ($this->directive === 'view') {
            $this->ProcessAssets($controller, $function);
        }

        $controllerExplode = explode('\\', $controller);

        $className = $controllerExplode[sizeof($controllerExplode) - 1];
        $namespaces = '';
        for ($i = 0; $i < sizeof($controllerExplode) - 1; $i++) {
            $namespaces .= $controllerExplode[$i] . "\\";
        }

        if (sizeof($controllerExplode) == 1) {
            $namespaces = "controller";
            $cNamespaces = $namespaces;
        } else {
            //region complex namespaces
            $namespaces = "controller\\" . rtrim($namespaces, "\\");
            $cNamespaces = Routes::StringReplaceSlash($namespaces);
        }

        $this->ProcessController($cNamespaces, $className, $function, $this->directive);

        return $this->Prints("Routes {$cNamespaces} {$function} added.", true, 'green');
    }

    /**
     * @param $segment
     * @return string
     */
    public function update($segment)
    {
        if (!isset($segment[$this->attribute])) {
            die($this->Prints("Routes is not registered! Add them first.", true, 'light_red'));
        }

        $controller = $this->Read('Controller (use \ to place in sub-directories) ex "entities\\reports"');
        $function = $this->Read('Function name');
        if ($this->directive === 'console' || $this->directive === 'socket') {
            $accept = 'get';
        } else {
            $accept = $this->Read('Accept? [GET,POST,PUT,PATCH,DELETE] multiple by commas');
        }

        $data = [
            "controller" => $controller,
            "function" => $function,
            "accept" => explode(",", strtoupper($accept))
        ];
        $this->routes['router'][$this->attribute] = $data;

        //sort ascending the routes definitions
        ksort($this->routes['router']);

        file_put_contents(
            "{$this->root}/config/routes.php",
            '<?php $routes = ' . $this->var_export54($this->routes) . '; return $routes;'
        );

        return $this->Prints("Routes {$function} modified.", true, 'green');
    }

    /**
     * @param array $routes
     */
    public function lists($routes = array())
    {
        $count = count($routes);
        echo $this->Prints("Routes list found ({$count}) entries.", true, 'green');
        foreach ($routes as $key => $value) {
            $accept = implode(",", $value["accept"]);
            echo $this->Prints("{$key} => {$value["controller"]}@{$value["function"]} [{$accept}]", false);
        }
    }

    /**
     * @param array $routes
     */
    public function directories($routes = array())
    {
        foreach ($routes as $key => $value) {
            $accept = implode(",", $value["accept"]);
            echo $this->Prints("{$key} [{$accept}]", false);
        }
    }

    /**
     * @param $routes
     */
    public function resorts($routes = array())
    {
        $count = count($routes);
        echo $this->Prints("Re-sorting ({$count}) entries.", true, 'green');

        //sort ascending the routes definitions
        ksort($this->routes['router']);

        file_put_contents(
            "{$this->root}/config/routes.php",
            '<?php $routes = ' . $this->var_export54($this->routes) . '; return $routes;'
        );
    }

    public function remove()
    {
        die($this->Prints('To risky. Please delete them manually.', true, 'light_red'));
    }

    /**
     * @param $segment
     * @throws Exception
     */
    public function crud($segment)
    {
        //limit to service only?
        if ($this->directive !== 'service') {
            die($this->Prints("Aborting! Only applicable to service", true, 'light_red'));
        }

        echo $this->Prints("Make sure you already successful execute 'php puko setup db' before executing this command!", true, 'yellow');

        //check if entity is available on the database.
        $base = explode('/', $this->attribute);
        if (sizeof($base) !== 2) {
            die($this->Prints("Aborting! incorrect parameter: schema/table", true, 'light_red'));
        }
        $schema = $base[0];
        $entity = $base[1];

        $routes_new = [];
        $routes_new["{$entity}/create"] = [
            "controller" => "{$schema}\\{$entity}",
            "function" => "create",
            "accept" => ["POST"]
        ];
        $routes_new["{$entity}/{?}/update"] = [
            "controller" => "{$schema}\\{$entity}",
            "function" => "update",
            "accept" => ["PUT"]
        ];
        $routes_new["{$entity}/{?}/delete"] = [
            "controller" => "{$schema}\\{$entity}",
            "function" => "delete",
            "accept" => ["DELETE"]
        ];
        $routes_new["{$entity}/explore"] = [
            "controller" => "{$schema}\\{$entity}",
            "function" => "explore",
            "accept" => ["POST"]
        ];
        $routes_new["{$entity}/table"] = [
            "controller" => "{$schema}\\{$entity}",
            "function" => "table",
            "accept" => ["POST"]
        ];
        $routes_new["{$entity}/search"] = [
            "controller" => "{$schema}\\{$entity}",
            "function" => "search",
            "accept" => ["POST"]
        ];
        $routes_new["{$entity}/{?}"] = [
            "controller" => "{$schema}\\{$entity}",
            "function" => "read",
            "accept" => ["GET"]
        ];

        //check if routes exist.
        foreach ($routes_new as $route => $val) {
            if (isset($segment[$route])) {
                die($this->Prints("Aborting! Routes '{$route}' already registered!", true, 'light_red'));
            }
        }

        $validations = file_get_contents(__DIR__ . "/template/controller/controller_crud_validation");
        $vars = file_get_contents(__DIR__ . "/template/controller/controller_crud_vars");
        $responses = file_get_contents(__DIR__ . "/template/controller/controller_crud_responses");

        $train_validation = $train_vars = $train_responses = "";

        //baca dulu dari model plugins daftar variable yang ada
        $model = '\\plugins\\model\\' . $schema . '\\' . $entity;

        try {
            $object = new $model;
        } catch (\Error $ex) {
            die($this->Prints("Aborting! Controller file required '{$model}.php' not found! maybe you entered wrong schema/table name?", true, 'light_red'));
        }

        $pdc = new ReflectionClass($object);
        foreach ($pdc->getProperties() as $prop) {
            $column = $this->PDCParser($prop->getDocComment());
            $col = isset($column['command'][0]) ? $column['command'][0] : '';
            $attr = isset($column['value'][0]) ? $column['value'][0] : '';

            if ($col !== '' && $attr !== '') {
                //exclude column
                if (!in_array($col, $this->database[$schema]['hideColumns'])) {
                    //validations
                    $validation_copy = $validations;
                    $validation_copy = str_replace('{{variable}}', $col, $validation_copy);
                    $validation_copy = str_replace('{{ucase_variable}}', strtoupper($col), $validation_copy);
                    $train_validation .= $validation_copy;

                    //vars
                    $vars_copy = $vars;
                    $vars_copy = str_replace('{{entity}}', $entity, $vars_copy);
                    $vars_copy = str_replace('{{variable}}', $col, $vars_copy);
                    $train_vars .= $vars_copy;

                    //responses
                    $response_copy = $responses;
                    $response_copy = str_replace('{{entity}}', $entity, $response_copy);
                    $response_copy = str_replace('{{variable}}', $col, $response_copy);
                    $train_responses .= $response_copy;
                }
            }
        }

        $crud_file = file_get_contents(__DIR__ . "/template/controller/controller_crud");
        $crud_file = str_replace('{{schema}}', $schema, $crud_file);
        $crud_file = str_replace('{{entity}}', $entity, $crud_file);
        $crud_file = str_replace('{{validations}}', $train_validation, $crud_file);
        $crud_file = str_replace('{{vars}}', $train_vars, $crud_file);
        $crud_file = str_replace('{{responses}}', $train_responses, $crud_file);

        if (!is_dir("{$this->root}/controller/{$schema}")) {
            mkdir("{$this->root}/controller/{$schema}", 0777, true);
        }
        if (!file_exists("{$this->root}/controller/{$schema}/{$entity}.php")) {
            file_put_contents("{$this->root}/controller/{$schema}/{$entity}.php", $crud_file);
        }

        foreach ($routes_new as $k => $v) {
            $this->routes['router'][$k] = $v;
        }

        //sort ascending the routes definitions
        ksort($this->routes['router']);

        file_put_contents(
            "{$this->root}/config/routes.php",
            '<?php $routes = ' . $this->var_export54($this->routes) . '; return $routes;'
        );

        echo $this->Prints(sprintf("Generating Service CRUD in controller\%s\%s.php done!", $schema, $entity), true, 'green');
        echo $this->Prints(sprintf("Don't forget to rebuild the language: php puko language controller/%s", $schema), true, 'green');
    }

    public function ProcessController($namespace, $class, $function, $kind)
    {
        $parameter = explode('/', $this->attribute);
        $input = array();
        foreach ($parameter as $key => $val) {
            if ($val === '{?}') {
                $input[] = '$' . "id{$key} = ''";
            }
        }
        $input = implode(', ', $input);

        $dir = "{$this->root}/{$namespace}";
        $path = "{$this->root}/{$namespace}/{$class}.php";
        $replacement = "    public function {$function}({$input}) {}\n\n}";

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
     */
    public function ProcessAssets($controller, $function)
    {
        $controller = Routes::StringReplaceSlash($controller);
        foreach ($this->lang as $val) {
            //html
            $ctrl = "{$this->root}/assets/html/{$val}/{$controller}";
            if (!is_dir($ctrl)) {
                mkdir($ctrl, 0777, true);
            }
            $html = file_get_contents(__DIR__ . "/template/assets/html");

            $html = str_replace("{{controller}}", $controller, $html);
            $html = str_replace("{{function}}", $function, $html);

            $fname = "{$this->root}/assets/html/{$val}/{$controller}/{$function}.html";
            file_put_contents($fname, $html);

            //javascript
            $ctrl = "{$this->root}/assets/scripts/{$controller}";
            if (!is_dir($ctrl)) {
                mkdir($ctrl, 0777, true);
            }
            $html = file_get_contents(__DIR__ . "/template/assets/scripts");
            $fname = "{$this->root}/assets/scripts/{$controller}/{$function}.js";
            file_put_contents($fname, $html);
        }
    }

    /**
     * @param $type
     */
    public function errorOrLost($type)
    {
        $controller = $this->Read('Controller (use \ to place in sub-directories) ex "entities\\reports"');
        $function = $this->Read('Function name');
        if ($this->directive === 'console') {
            $accept = 'get';
        } else {
            $accept = $this->Read('Accept? [GET,POST,PUT,PATCH,DELETE] multiple by commas');
        }

        $data = [
            "controller" => $controller,
            "function" => $function,
            "accept" => explode(",", strtoupper($accept))
        ];
        $this->routes[$type] = $data;

        file_put_contents(
            "{$this->root}/config/routes.php",
            '<?php $routes = ' . $this->var_export54($this->routes) . '; return $routes;'
        );
    }

    public function __toString()
    {
        return $this->Prints('Routes initialization complete.', true, 'green');
    }

    public static function StringReplaceSlash($string)
    {
        return str_replace("\\", "/", $string);
    }

    public static function StringReplaceBackSlash($string)
    {
        return str_replace("/", "\\", $string);
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

}
