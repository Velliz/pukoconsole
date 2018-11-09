<?php

namespace pukoconsole;

use Exception;
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
    var $dir = null;

    /**
     * Routes constructor.
     * @param array $param
     * @param null $dir
     * @throws Exception
     */
    public function __construct($param = array(), $dir = null)
    {
        if ($dir === null) {
            die(Echos::Prints('Base url required'));
        }
        $routes = include $dir . "/config/routes.php";

        $this->directive = $param['directive'];
        $this->action = $param['action'];
        $this->attribute = $param['attribute'];
        $this->dir = $dir;

        if (in_array(array('view', 'service'), $this->directive)) {
            $this->structure($routes['page']);
        } else if ($this->directive === 'list') {
            $this->lists($routes['page']);
        } else {
            $this->structure($routes[$this->directive]);
        }
    }

    public function structure($active)
    {
        switch ($this->action) {
            case 'add':
                $this->add($active);
                break;
            case 'update':
                $this->update();
                break;
            case 'remove':
                $this->remove();
                break;
            default:
                die(Echos::Prints('Command not supported'));
                break;
        }
    }

    public function add($active)
    {
        if (isset($active[$this->attribute])) {
            die(Echos::Prints("Routes already registered!"));
        }

        $controller = Input::Read('Controller name (separate directory with \ key)');
        $function = Input::Read('Function name');
        $accept = Input::Read('Accept [GET,POST, PUT, PATCH, DELETE, OPTIONS] separated by comma');

        $data = [
            "controller" => $controller,
            "function" => $function,
            "accept" => explode(",", $accept)
        ];
        $routes['page'][$this->attribute] = $data;

        file_put_contents(
            $this->dir . "config/routes.php",
            '<?php $routes = ' . $this->var_export54($routes) . '; return $routes;'
        );

        if ($this->directive === 'view') {
            $this->process_assets();
        }

        $controllerExplode = explode('\\', $controller);

        $className = $controllerExplode[sizeof($controllerExplode) - 1];
        $namespaces = '';
        for ($i = 0; $i < sizeof($controllerExplode) - 1; $i++) {
            $namespaces .= $controllerExplode[$i] . "\\";
        }

        if (sizeof($controllerExplode) == 1) {
            //region base
            $namespaces = "controller";
            $cNamespaces = $apps . $namespaces;
            $className = $controller;
            $varNewFile = <<<PHP
<?php

namespace $cNamespaces;

use pukoframework\middleware\View;

/**
 * #Master master.html
 */
class $className extends View
{

    public function $function(){}

}

PHP;
            if (!file_exists($namespaces . "/" . $className . ".php")) {
                file_put_contents($namespaces . "/" . $className . '.php', $varNewFile);
            } else {
                $existingController = file_get_contents($namespaces . "/" . $className . '.php');
                $pos = strrpos($existingController, "}");
                $existingController = substr_replace($existingController, "    public function $function(){}\n\n}", $pos);
                file_put_contents($namespaces . "/" . $className . '.php', $existingController);
            }
        } else {
            //region complex namespaces
            $namespaces = "controller\\" . rtrim($namespaces, "\\");
            $cNamespaces = $apps . $namespaces;
            $namespaceFolder = str_replace("\\", "/", $namespaces);
            $varNewFile = <<<PHP
<?php

namespace $cNamespaces;

use pukoframework\middleware\View;

/**
 * #Master master.html
 */
class $className extends View
{

    public function $function(){}

}

PHP;
            if (!file_exists($namespaceFolder)) {
                mkdir($namespaceFolder, 0777, true);
            }
            if (!file_exists('controller/' . str_replace('\\', '/', $controller . '.php'))) {
                file_put_contents('controller/' . str_replace('\\', '/', $controller . '.php'), $varNewFile);
            } else {
                $existingController = file_get_contents('controller/' . str_replace('\\', '/', $controller . '.php'));
                $pos = strrpos($existingController, "}");
                $existingController = substr_replace($existingController, "    public function $function(){}\n\n}", $pos);
                file_put_contents('controller/' . str_replace('\\', '/', $controller . '.php'), $existingController);
            }

        }
        echo "\nroutes added\n";
    }

    public function update()
    {

    }

    public function lists($routes = array())
    {
    }

    public function remove()
    {
    }

    public function process_assets()
    {

        if (!file_exists($this->dir . "assets/html/id/" . str_replace('\\', '/', $controller))) {
            mkdir($this->dir . "assets/html/id/" . str_replace('\\', '/', $controller), 0777, true);
        }

        if (!file_exists($this->dir . "assets/html/en/" . str_replace('\\', '/', $controller))) {
            mkdir($this->dir . "assets/html/en/" . str_replace('\\', '/', $controller), 0777, true);
        }

        $html = file_get_contents($this->dir);

        file_put_contents("assets/html/id/" . str_replace('\\', '/', $controller) . '/' . $function . '.html', $html);
        file_put_contents("assets/html/en/" . str_replace('\\', '/', $controller) . '/' . $function . '.html', $html);

    }

    public function __toString()
    {
        return Echos::Prints('Routes initialization complete.');
    }

}
