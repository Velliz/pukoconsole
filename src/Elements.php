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

    public function __construct($root, $type, $command)
    {
        if ($type === '' || $type === null) {
            die('element name must defined');
        }

        $lowerType = strtolower($type);

        if ($command === 'add') {

            $html = sprintf("strtolower(%s::class . '.html')", $type);
            $path = sprintf("ROOT . '/' . str_replace('\\\\', '/', %s)", $html);

            $varPhpFile = "<?php

namespace plugins\\elements\\$lowerType;

use pte\\Parts;

class $type extends Parts
{

    /**
     * @return string
     */
    public function Parse()
    {" . '
        $this->pte->SetValue($this->data);
        $this->pte->SetHtml(' . $path . ");
        return" . ' $this->pte->Output();' . "
    }

}";

            if (!file_exists('plugins/elements/' . $lowerType)) {
                mkdir('plugins/elements/' . $lowerType, 0777, true);
            }
            if (!file_exists('plugins/elements/' . $lowerType . '/' . $type . '.php')) {
                file_put_contents('plugins/elements/' . $lowerType . '/' . $type . '.php', $varPhpFile);
            }

            $newHtmlFile = <<<HTML
{!css(<link href="plugins/elements/?/?.css" rel="stylesheet" type="text/css"/>)}
{!js(<script type="text/javascript" src="plugins/elements/?/?.js"></script>)}
<!-- your code here -->
HTML;

            $newHtmlFile = str_replace('?', $lowerType, $newHtmlFile);

            if (!file_exists('plugins/elements/' . $lowerType . '/' . $lowerType . '.html')) {
                file_put_contents('plugins/elements/' . $lowerType . '/' . $lowerType . '.html', $newHtmlFile);
            }


            $newJsFile = <<<JS
// your code here
JS;

            if (!file_exists('plugins/elements/' . $lowerType . '/' . $lowerType . '.js')) {
                file_put_contents('plugins/elements/' . $lowerType . '/' . $lowerType . '.js', $newJsFile);
            }


            $newCssFile = <<<CSS
/* your css here */
CSS;

            if (!file_exists('plugins/elements/' . $lowerType . '/' . $lowerType . '.css')) {
                file_put_contents('plugins/elements/' . $lowerType . '/' . $lowerType . '.css', $newCssFile);
            }

            echo "\n";
            echo 'elements created';
            echo "\n";
        }

        if ($command === 'download') {
            $url = 'https://api.github.com/repos/Velliz/elements/contents/' . $type;
            $data = json_decode($this->download($url), true);

            if (!file_exists('plugins/elements/' . $lowerType)) {
                mkdir('plugins/elements/' . $lowerType, 0777, true);
            }

            foreach ($data as $single) {
                if (!isset($single['download_url'])) {
                    die('error when downloading elements');
                }

                $file = $this->download($single['download_url']);
                if (!file_exists('plugins/elements/' . $single['path'])) {
                    file_put_contents('plugins/elements/' . $single['path'], $file);
                    echo 'downloading... ' . $single['name'];
                    echo "\n";
                }
            }

            echo "\n";
            echo 'elements downloaded';
            echo "\n";
        }
    }

}