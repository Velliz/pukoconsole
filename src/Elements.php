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

/**
 * Class Elements
 * @package pukoconsole
 */
class Elements
{

    use Commons, Echos;

    var $type = '';
    var $command = '';
    var $config = array();

    public function __construct($root, $config, $type, $command)
    {
        $this->config = $config;
        $this->command = $command;
        $this->type = $type;

        if ($type === '' || $type === null) {
            die(Echos::Prints('Element name must defined!'));
        }

        if ($command === 'add') {
            $this->AddElements($root, $type);
        }
        if ($command === "download") {
            $this->DownloadElements($root);
        }
    }

    /**
     * @param $root
     * @param $type
     */
    public function AddElements($root, $type)
    {
        $ltype = strtolower($type);

        $element = file_get_contents(__DIR__ . "/template/plugins/elements");
        $path = '\pukoframework\Framework::$factory->getRoot()' . " . {$type}::class . '.html'";
        $element = str_replace('{{path}}', $path, $element);
        $element = str_replace('{{namespaces}}', $ltype, $element);
        $element = str_replace('{{class}}', $type, $element);

        if (!is_dir("{$root}/plugins/elements/{$ltype}")) {
            mkdir("{$root}/plugins/elements/{$ltype}");
        }
        if (!file_exists("/plugins/elements/{$ltype}/{$type}.php")) {
            file_put_contents("{$root}/plugins/elements/{$ltype}/{$type}.php", $element);
        }

        //region html template
        $html = file_get_contents(__DIR__ . "/template/plugins/html");
        $html = str_replace('{{namespaces}}', $ltype, $html);
        if (!file_exists("{$root}/plugins/elements/{$ltype}/{$ltype}.html")) {
            file_put_contents("{$root}/plugins/elements/{$ltype}/{$ltype}.html", $html);
        }
        //end region html template

        //region js template
        $js = file_get_contents(__DIR__ . "/template/plugins/js");
        if (!file_exists("{$root}/plugins/elements/{$ltype}/{$ltype}.js")) {
            file_put_contents("{$root}/plugins/elements/{$ltype}/{$ltype}.js", $js);
        }
        //end region js template

        //region css template
        $css = file_get_contents(__DIR__ . "/template/plugins/css");
        if (!file_exists("{$root}/plugins/elements/{$ltype}/{$ltype}.css")) {
            file_put_contents("{$root}/plugins/elements/{$ltype}/{$ltype}.css", $css);
        }
        //end region css template
    }

    public function DownloadElements($root)
    {

        $url = "{$this->config['repo']}/{$this->type}";
        $ltype = strtolower($this->type);

        $data = json_decode($this->download($url), true);

        if (!is_dir("{$root}/plugins/elements/{$ltype}")) {
            mkdir("{$root}/plugins/elements/{$ltype}");
        }

        foreach ($data as $single) {
            if (!isset($single['download_url'])) {
                die(Echos::Prints('Error when downloading elements.'));
            }

            $file = $this->download($single['download_url']);
            if (!file_exists("{$root}/plugins/elements/{$single['path']}")) {
                file_put_contents("{$root}/plugins/elements/{$single['path']}", $file);
                echo Echos::Prints("Downloading... {$single['name']}", false);
            }
        }
    }

    public function __toString()
    {
        return Echos::Prints("Element {$this->command} created.");
    }

}