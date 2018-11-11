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
use pukoconsole\util\Echos;

/**
 * Class Console
 * @package pukoconsole
 */
class Console
{

    use Echos;

    /**
     * @var array
     */
    var $config = array();

    /**
     * @var array
     */
    var $args = array();

    /**
     * @var string
     */
    var $root = __DIR__;

    const COMMAND = 1;
    const DIRECTIVE = 2;
    const ACTION = 3;
    const ATTRIBUTE = 4;
    const EPHEMERAL = 5;

    /**
     * Console constructor.
     * @param $root
     * @param $args
     */
    public function __construct($root, $args)
    {
        $this->root = $root;
        $this->args = $args;
        if (!file_exists(__DIR__ . "/config/init.php")) {
            die(Echos::Prints('Console init file not found'));
        }
        $this->config = (include __DIR__ . "/config/init.php");
    }

    /**
     * @throws Exception
     */
    public function Execute()
    {
        switch ($this->GetCommand(Console::COMMAND)) {
            case 'setup':
                return $this->Setup($this->GetCommand(Console::DIRECTIVE));
                break;
            case 'routes':
                return new Routes($this->root,
                    $this->GetCommand(Console::DIRECTIVE),
                    $this->GetCommand(Console::ACTION),
                    $this->GetCommand(Console::ATTRIBUTE)
                );
                break;
            case 'element':
                return new Elements(
                    $this->root,
                    $this->GetCommand(Console::DIRECTIVE),
                    $this->GetCommand(Console::ACTION)
                );
                break;
            case 'docs':
                return new Docs(
                    $this->root,
                    $this->GetCommand(Console::DIRECTIVE),
                    $this->GetCommand(Console::ACTION)
                );
                break;
            case 'serve':
                return new Serve();
                break;
            case 'help':
                return $this->Help();
                break;
            case 'version':
                return $this;
                break;
            default:
                return $this->Help();
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
                return new Database($this->root);
                break;
            case 'secure':
                return new Secure($this->root);
                break;
            case 'auth':
                return new Auth(
                    $this->root,
                    $this->GetCommand(Console::ACTION)
                );
                break;
            case 'controller':
                return new Controller(
                    $this->root,
                    $this->GetCommand(Console::ACTION),
                    $this->GetCommand(Console::ATTRIBUTE)
                );
                break;
            default:
                return Echos::Prints("Setup exited with no process executed!");
                break;
        }
    }

    public function Help()
    {
        $help = file_get_contents(__DIR__ . "/config/help.md");
        return $help;
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
        return Echos::Prints("Puko Console {$this->config['version']}");
    }

}