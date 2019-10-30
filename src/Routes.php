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
            default:
                die(Echos::Prints('Command not supported'));
                break;
        }
    }

    public function add($segment)
    {
        if (isset($segment[$this->attribute])) {
            die(Echos::Prints("Routes already registered!"));
        }

        $controller = Input::Read('Controller name (separate directory with \ key)');
        $function = Input::Read('Function name');
        $accept = Input::Read('Accept [GET,POST, PUT, PATCH, DELETE, OPTIONS] separated by comma');

        $data = [
            "controller" => $controller,
            "function" => $function,
            "accept" => explode(",", strtoupper($accept))
        ];
        $this->routes['router'][$this->attribute] = $data;

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

    public function update($segment)
    {
        if (!isset($segment[$this->attribute])) {
            die(Echos::Prints("Routes is not registered! Add them first."));
        }

        $controller = Input::Read('Controller name (separate directory with \ key)');
        $function = Input::Read('Function name');
        $accept = Input::Read('Accept [GET,POST, PUT, PATCH, DELETE, OPTIONS] separated by comma');

        $data = [
            "controller" => $controller,
            "function" => $function,
            "accept" => explode(",", strtoupper($accept))
        ];
        $this->routes['router'][$this->attribute] = $data;

        file_put_contents(
            "{$this->root}/config/routes.php",
            '<?php $routes = ' . $this->var_export54($this->routes) . '; return $routes;'
        );

        return Echos::Prints("Routes {$function} modified.");
    }

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

    public function errorOrLost($type)
    {
        $controller = Input::Read('Controller name (separate directory with \ key)');
        $function = Input::Read('Function name');
        $accept = Input::Read('Accept [GET,POST, PUT, PATCH, DELETE, OPTIONS] separated by comma');

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

}
