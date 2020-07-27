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
                    $this->config,
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
                return new Serve($this->GetCommand(Console::DIRECTIVE));
                break;
            case 'tests':
                return new Tests($this->GetCommand(Console::DIRECTIVE));
                break;
            case 'help':
                return $this->Help();
                break;
            case 'cli':
                return new Cli($this->GetCommand(Console::DIRECTIVE));
                break;
            case 'generate':
                return $this->Generate($this->GetCommand(Console::DIRECTIVE));
                break;
            case 'version':
                return $this;
                break;
            default:
                return $this->NotFound();
                break;
        }
    }

    /**
     * @param $kind
     * @return string
     * @throws Exception
     */
    public function Generate($kind)
    {
        switch ($kind) {
            case 'db':
                return new Database($this->root, 'generate');
                break;
            default:
                return Echos::Prints("Setup exited with no process executed!");
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
                return new Database($this->root, 'setup');
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
            case 'model':
                return new Models(
                    $this->root,
                    $this->GetCommand(Console::ACTION),
                    $this->GetCommand(Console::ATTRIBUTE),
                    $this->GetCommand(Console::EPHEMERAL)
                );
            default:
                return Echos::Prints("Setup exited with no process executed!");
                break;
        }
    }

    /**
     * @return false|string
     */
    public function Help()
    {
        return file_get_contents(__DIR__ . "/config/help.md");
    }

    public function NotFound()
    {
        return "Command bot found!";
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