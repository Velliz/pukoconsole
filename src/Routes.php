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

    /**
     * Routes constructor
     * @param $root
     * @param $directive
     * @param $action
     * @param $attribute
     */
    public function __construct($root, $directive, $action, $attribute)
    {
        if ($root === null) {
            die(Echos::Prints('Base url required'));
        }
        $this->root = $root;

        $this->directive = $directive;
        $this->action = $action;
        $this->attribute = $attribute;

        $this->routes = include "{$root}/config/routes.php";

        if (in_array($this->directive, array('view', 'service', 'console'))) {
            $this->structure($this->routes['router']);
        } else if (in_array($this->directive, array('error', 'lost'))) {
            $this->errorOrLost($this->directive);
        } else if ($this->directive === 'list') {
            $this->lists($this->routes['router']);
        } else {
            $this->structure($this->routes[$this->directive]);
        }
    }

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
                die(Echos::Prints('Command not supported'));
                break;
        }
    }

    /**
     * @param $segment
     * @return string
     */
    public function add($segment)
    {
        if (isset($segment[$this->attribute])) {
            die(Echos::Prints("Routes already registered!"));
        }

        $controller = Input::Read('Controller (use \ to place in sub-directories) ex "entities\\reports"');
        $function = Input::Read('Function name');
        if ($this->directive === 'console') {
            $accept = 'get';
        } else {
            $accept = Input::Read('Accept? [GET,POST,PUT,PATCH,DELETE] multiple by commas');
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

        return Echos::Prints("Routes {$cNamespaces} {$function} added.");
    }

    /**
     * @param $segment
     * @return string
     */
    public function update($segment)
    {
        if (!isset($segment[$this->attribute])) {
            die(Echos::Prints("Routes is not registered! Add them first."));
        }

        $controller = Input::Read('Controller (use \ to place in sub-directories) ex "entities\\reports"');
        $function = Input::Read('Function name');
        if ($this->directive === 'console') {
            $accept = 'get';
        } else {
            $accept = Input::Read('Accept? [GET,POST,PUT,PATCH,DELETE] multiple by commas');
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

        return Echos::Prints("Routes {$function} modified.");
    }

    /**
     * @param array $routes
     */
    public function lists($routes = array())
    {
        $count = count($routes);
        echo Echos::Prints("Routes list found ({$count}) entries.");
        foreach ($routes as $key => $value) {
            $accept = implode(",", $value["accept"]);
            echo Echos::Prints("{$key} => {$value["controller"]}@{$value["function"]} [{$accept}]", false);
        }
    }

    public function remove()
    {
        die(Echos::Prints('To risky. Please delete them manually.'));
    }

    public function crud($segment)
    {
        //limit to service only?
        if ($this->directive !== 'service') {
            die(Echos::Prints("Aborting! Only applicable to service"));
        }

        //check if entity is available on the database.
        $base = explode('/', $this->attribute);
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
            "accept" => ["PUT", "POST"]
        ];
        $routes_new["{$entity}/{?}/delete"] = [
            "controller" => "{$schema}\\{$entity}",
            "function" => "delete",
            "accept" => ["DELETE"]
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
                die(Echos::Prints("Aborting! Routes '{$route}' already registered!"));
            }
        }

        $validations = file_get_contents(__DIR__ . "/template/controller/controller_crud_validation");
        $vars = file_get_contents(__DIR__ . "/template/controller/controller_crud_vars");
        $responses = file_get_contents(__DIR__ . "/template/controller/controller_crud_responses");

        $train_validation = $train_vars = $train_responses = "";

        //baca dulu dari model plugins daftar variable yang ada
        $model = '\\plugins\\model\\' . $schema . '\\' . $entity;

        $object = new $model;

        $pdc = new ReflectionClass($object);
        foreach ($pdc->getProperties() as $prop) {
            $column = $this->PDCParser($prop->getDocComment());
            $col = isset($column['command'][0]) ? $column['command'][0] : '';
            $attr = isset($column['value'][0]) ? $column['value'][0] : '';

            if ($col !== '' && $attr !== '') {
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

        echo Echos::Prints(sprintf("Generating CRUD controller\%s\%s.php done!", $schema, $entity), false);


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
        foreach ($this->lang as $key => $val) {
            $ctrl = "{$this->root}/assets/html/{$val}/{$controller}";
            if (!is_dir($ctrl)) {
                mkdir($ctrl, 0777, true);
            }
            $html = file_get_contents(__DIR__ . "/template/assets/html");
            $fname = "{$this->root}/assets/html/{$val}/{$controller}/{$function}.html";
            file_put_contents($fname, $html);
        }
    }

    /**
     * @param $type
     */
    public function errorOrLost($type)
    {
        $controller = Input::Read('Controller (use \ to place in sub-directories) ex "entities\\reports"');
        $function = Input::Read('Function name');
        if ($this->directive === 'console') {
            $accept = 'get';
        } else {
            $accept = Input::Read('Accept? [GET,POST,PUT,PATCH,DELETE] multiple by commas');
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
        return Echos::Prints('Routes initialization complete.');
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
