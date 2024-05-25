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

    public function __construct($root, $config, $command, $type)
    {
        $this->config = $config;
        $this->command = $command;
        $this->type = $type;

        if ($type === '' || $type === null) {
            die($this->Prints('Element name must defined!', true, 'light_red'));
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
        $element = str_replace('{{namespaces}}', $ltype, $element);
        $element = str_replace('{{class}}', $type, $element);

        if (!is_dir("{$root}/plugins/elements/{$ltype}")) {
            mkdir("{$root}/plugins/elements/{$ltype}", 0777, true);
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

    /**
     * @param $root
     */
    public function DownloadElements($root)
    {

        $url = "{$this->config['repo']}/{$this->type}";
        $ltype = strtolower($this->type);

        $data = json_decode($this->download($url), true);

        if (!is_dir("{$root}/plugins/elements/{$ltype}")) {
            mkdir("{$root}/plugins/elements/{$ltype}", 0777, true);
        }

        foreach ($data as $single) {
            if (!isset($single['download_url'])) {
                die($this->Prints('Error when downloading elements.'));
            }

            $file = $this->download($single['download_url']);
            if (!file_exists("{$root}/plugins/elements/{$single['path']}")) {
                file_put_contents("{$root}/plugins/elements/{$single['path']}", $file);
                echo $this->Prints("Downloading... {$single['name']}", false);
            }
        }
    }

    public function __toString()
    {
        return $this->Prints("Element {$this->type} created.", true, 'green');
    }

}
