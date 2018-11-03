<?php

namespace pukoconsole;

use Exception;
use pukoconsole\util\Echos;

/**
 * Class Console
 * @package pukoconsole
 */
class Console
{

    use Echos;

    var $args = array();

    var $root = '';

    const COMMAND = 1;
    const DIRECTIVE = 2;
    const ACTION = 3;
    const ATTRIBUTE = 4;
    const EPHEMERAL = 5;

    public function __construct($root, $args)
    {
        $this->root = $root;
        $this->args = $args;
    }

    /**
     * @throws Exception
     */
    public function Execute()
    {
        switch ($this->GetCommand(Console::COMMAND)) {
            case 'setup':
                echo $this->Setup($this->GetCommand(Console::DIRECTIVE));
                break;
            case 'routes':
                break;
            case 'element':
                break;
            case 'docs':
                break;
            case 'serve':
                new Serve();
                break;
            case 'help':
                echo $this->Help();
                break;
            case 'version':
                echo $this;
                break;
            default:
                echo $this->Help();
                break;
        }
        return null;
    }

    /**
     * @param $kind
     * @return Database|string
     * @throws Exception
     */
    public function Setup($kind)
    {
        switch ($kind) {
            case 'db':
                return new Database();
                break;
            case 'secure':
                break;
            case 'auth':
                break;
            case 'controller':
                break;
        }

        return Echos::Prints("Setup executed!");
    }

    public function Help()
    {
        echo "\npukoframework console commands list:\n";
        echo "  setup    Setup puko framework installation\n";
        echo "           [db]\n";
        echo "           [secure]\n";
        echo "           [base_auth] [class_name]\n";
        echo "           [base_controller] [class_name] [view/service]\n";
        echo "\n";
        echo "  routes   Setup puko framework routes\n";
        echo "           [view/service/error/not_found] [add/update/delete/list] [url]\n";
        echo "\n";
        echo "  serve    Start puko on localhost:[port]\n";
        echo "\n";
        echo "  element  Puko elements\n";
        echo "           [add/download]\n";
        echo "\n";
        echo "  help     Show help menu\n";
        echo "\n";
        echo "  version  Show console version\n";

        return '';
    }

    /**
     * @param int $type
     * @return mixed|null
     */
    public function GetCommand($type = Console::COMMAND)
    {
        return isset($this->args[$type]) ? $this->args[$type] : null;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return Echos::Prints("Puko Console v1.0.1");
    }

}