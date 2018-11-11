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
        if ($type === '' || $type === null) {
            die('element name must defined');
        }

        $lowerType = strtolower($type);

        if ($command === 'add') {

            $html = sprintf("strtolower(%s::class . '.html')", $type);
            $path = sprintf("ROOT . '/' . str_replace('\\\\', '/', %s)", $html);

            $varPhpFile = file_get_contents(__DIR__ . "/template/plugins/elements");

            if (!file_exists(__DIR__ . '/plugins/elements/' . $lowerType)) {
                mkdir(__DIR__ . '/plugins/elements/' . $lowerType, 0777, true);
            }
            if (!file_exists(__DIR__ . '/plugins/elements/' . $lowerType . '/' . $type . '.php')) {
                file_put_contents(__DIR__ . '/plugins/elements/' . $lowerType . '/' . $type . '.php', $varPhpFile);
            }

            $newHtmlFile = file_get_contents(__DIR__ . "/template/plugins/html");

            $newHtmlFile = str_replace('?', $lowerType, $newHtmlFile);

            if (!file_exists('plugins/elements/' . $lowerType . '/' . $lowerType . '.html')) {
                file_put_contents('plugins/elements/' . $lowerType . '/' . $lowerType . '.html', $newHtmlFile);
            }

            $newJsFile = file_get_contents(__DIR__ . "/template/plugins/js");

            if (!file_exists('plugins/elements/' . $lowerType . '/' . $lowerType . '.js')) {
                file_put_contents('plugins/elements/' . $lowerType . '/' . $lowerType . '.js', $newJsFile);
            }

            $newCssFile = file_get_contents(__DIR__ . "/template/plugins/css");

            if (!file_exists('plugins/elements/' . $lowerType . '/' . $lowerType . '.css')) {
                file_put_contents('plugins/elements/' . $lowerType . '/' . $lowerType . '.css', $newCssFile);
            }
        }

        if ($command === "download") {
            $this->DownloadRepo($root);
        }
    }

    public function DownloadRepo($root)
    {

        $url = "{$this->config['repo']}/{$this->type}";
        $lowerType = strtolower($this->type);

        $data = json_decode($this->download($url), true);

        if (!file_exists("{$root}/plugins/elements/{$lowerType}")) {
            mkdir("{$root}/plugins/elements/{$lowerType}", 0777, true);
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