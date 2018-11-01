<?php

namespace pukoconsole;

use Exception;

/**
 * Class Console
 * @package pukoconsole
 */
class Console
{

    var $args = array();

    var $root = '';

    public function __construct($root, $args)
    {
        $this->root = $root;
        $this->args = $args;
    }

    /**
     * @param $command
     * @throws Exception
     */
    public function Execute($command)
    {
        switch ($command) {
            case 'setup':
                echo $this->Setup($this->args['kind']);
                break;
            case 'routes':
                break;
            case 'element':
                break;
            case 'docs':
                break;
            case 'help':
                echo $this->Help();
                break;
            case 'version':
                echo $this;
                break;
            default:
                throw new Exception('command not supported');
                break;
        }
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
                return new Database($this->args);
                break;
            case 'secure':
                break;
            case 'auth':
                break;
            case 'controller':
                break;
        }

        return 'Setup executed succesfuly!';
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

    public function __toString()
    {
        return 'Puko Console v0.1.0';
    }

}