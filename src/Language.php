<?php

namespace pukoconsole;

use pukoconsole\util\Echos;

/**
 * Class Language
 * @package pukoconsole
 */
class Language
{

    /**
     * @param $root
     * @param $kinds
     */
    public function __construct($root, $kinds)
    {
        if ($root === null) {
            die(Echos::Prints('Invalid command!', true, 'light_red'));
        }

        if ($kinds === 'build') {
            // Path to your textfiles
            $path = realpath($root . "\\controller\\");
            $fileList = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::SELF_FIRST);

            foreach ($fileList as $item) {
                if ($item->isFile() && stripos($item->getPathName(), 'conf') !== false) {
                    $file_contents = file_get_contents($item->getPathName());

                    $m = [];
                    preg_match("\$this->say\([A-Za-z$\'\"_, \[\]\n\t]+\)",$file_contents,$m);

                    var_dump($m);
                }
            }
        }

    }

}
