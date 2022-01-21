<?php

namespace pukoconsole;

use pukoconsole\util\Echos;

/**
 * Class Language
 * @package pukoconsole
 */
class Language
{

    var $glob_pattern = "/say\([A-Za-z$\'\"_, \[\]\n\t]+\)/";
    var $say_pattern = "/[$]|(?!\[)[[\'\"][A-Za-z0-9_]+[\'\"](?!\])/";

    /**
     * @param $root
     * @param $kinds
     */
    public function __construct($root, $kinds)
    {
        if ($root === null) {
            die(Echos::Prints('Invalid command!', true, 'light_red'));
        }

        //retreive lang files
        $lang_files = file_get_contents(realpath($root . "/assets/master/id.master.json"));
        $lang_vars = json_decode($lang_files, true);

        // Path to your textfiles
        $path = realpath($root . "/{$kinds}/");
        $fileList = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($fileList as $item) {
            if ($item->isFile() && stripos($item->getPathName(), 'php') !== false) {
                $file_contents = file_get_contents($item->getPathName());

                $m = [];
                preg_match_all($this->glob_pattern, $file_contents, $m);

                foreach ($m as $second_item) {
                    foreach ($second_item as $thrid_item) {
                        $second_m = [];
                        preg_match_all($this->say_pattern, $thrid_item, $second_m);

                        foreach ($second_m as $result_first_item) {
                            $say_directive = $result_first_item[0];
                            $say_directive = str_replace('"', '', $say_directive);
                            $say_directive = str_replace("'", '', $say_directive);
                            $say_variables = sizeof($result_first_item) - 1;

                            $value_var = "";
                            for ($i = 0; $i < $say_variables; $i ++) {
                                $value_var .= " %s";
                            }

                            if ($say_variables > 0) {
                                $localization_string = "Describe message about {$say_directive} with parameters{$value_var}";
                            } else {
                                $localization_string = "Describe message about {$say_directive}";
                            }

                            if (!isset($lang_vars[$say_directive])) {
                                $lang_vars[$say_directive] = $localization_string;
                            }

                        }
                    }
                }
            }
        }

        //write back lang files
        file_put_contents($root . "/assets/master/id.master.json", json_encode($lang_vars, JSON_PRETTY_PRINT));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return Echos::Prints("Language file id.master.json updated!", true, 'green');
    }

}
