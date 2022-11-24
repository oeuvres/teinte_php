<?php

/**
 * Part of Teinte https://github.com/oeuvres/teinte
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

declare(strict_types=1);

namespace Oeuvres\Kit;
Check::extension('mbstring');

/**
 * code convention https://www.php-fig.org/psr/psr-12/
 */
class Misc
{
    /** An error message, used for preg_replace messages */
    static private $errstr = "";

    static function mois($num)
    {
        $mois = array(
            1 => 'janvier',
            2 => 'février',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
            6 => 'juin',
            7 => 'juillet',
            8 => 'août',
            9 => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'décembre',
        );
        return $mois[(int)$num];
    }

    /**
     * Build a search/replace regexp table from a sed script
     */
    public static function sed_preg($script)
    {
        $search = array();
        $replace = array();
        $lines = explode("\n", $script);
        $lines = array_filter($lines, 'trim');
        foreach ($lines as $l) {
            $l = trim($l);
            if ($l[0] != 's') continue;
            $delim = $l[1];
            list($a, $re, $rep, $flags) = explode($delim, $l);
            $mod = 'u';
            if (strpos($flags, 'i') !== FALSE) $mod .= "i"; // ignore case ?
            $search[] = $delim . $re . $delim . $mod;
            $replace[] = preg_replace(
                array('/\\\\([0-9]+)/', '/\\\\n/', '/\\\\t/'),
                array('\\$$1', "\n", "\t"),
                $rep
            );
        }
        return array($search, $replace);
    }

    /**
     * Build a search/replace regexp table from a two colums table
     */
    public static function pcre_tsv($tsv_file, $sep = "\t")
    {
        $search = []; // pattern to compile
        $sub = []; // replacement 
        $delim = '@'; // regex delimiter
        $var_search = [$delim]; // macros to replace in search pattern
        $var_replace = ["\\$delim"];
        if (true != ($ret = Filesys::readable($tsv_file))) {
            Log::error('Regex file impossible to read — ' . $ret);
            return null;
        }


        $n = 0;
        $handle = fopen($tsv_file, "r");
        // handle regex compilation warnings
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            self::$errstr = $errstr;
        });
        while (($row = fgetcsv($handle, 0, $sep)) !== FALSE) {
            $n++;
            if ($n == 1) continue; // jump first line
            if (!$row || !count($row) || !$row[0]) {
                continue; // empty lines
            }
            // just for testing, but spaces should be kept for a replace
            $trim1 = trim($row[0]);
            if ($trim1 === '') continue;
            // comment
            if ($trim1[0] == '#') continue;
            // no second cell suppose empty result
            if (($count = count($row)) < 2) {
                $row[1] = '';
            }
            // A variable to set
            $pref = '($';
            if (substr($trim1, 0, strlen($pref)) === $pref) {
                // recursive replace ?
                $var_replace[] = str_replace($var_search, $var_replace, $row[1]);
                $var_search[] = $trim1;
                continue;
            }
            $mod = 'Su';
            $pattern = str_replace($var_search, $var_replace, $row[0]);

            $pattern = $delim . $pattern . $delim . $mod;
            $replacement = preg_replace(
                array('/\\\\([0-9]+)/', '/\\\\n/', '/\\\\t/'),
                array('\\$$1', "\n", "\t"),
                $row[1]
            );
            // try to compile re
            preg_replace($pattern, $replacement, "");
            if (preg_last_error() !== PREG_NO_ERROR) {
                preg_match('/offset (\d+)/', self::$errstr, $matches);
                $offset = $matches[1];

                $rule = "";
                if ($offset) {
                    $offset = (int) $offset;
                    $rule = str_repeat('-', mb_strlen($pattern, "UTF-8"));
                    $rule[$offset] = 'V';
                    $rule .= "\n";
                }
                Log::warning(
                  "$tsv_file#$n \n" 
                . self::$errstr . "\n" 
                . $rule
                . $pattern . " => " . $replacement
                );
                continue;
            }
            $search[] = $pattern;
            $sub[] = $replacement;
        }
        restore_error_handler();
        return array($search, $sub);
    }

    /**
     * Build a map from tsv file where first col is the key.
     */
    static function tsv_map($tsvfile, $sep = "\t")
    {
        $ret = array();
        $handle = fopen($tsvfile, "r");
        $l = 0;
        while (($data = fgetcsv($handle, 0, $sep)) !== FALSE) {
            $l++;
            if (!$data || !count($data) || !$data[0]) {
                continue; // empty lines
            }
            /* Log ?
            if (isset($ret[$data[0]])) {
                echo $tsvfile,'#',$l,' not unique key:', $data[0], "\n";
            }
            */
            if (!isset($data[1])) {  // shall we log for user
                continue;
            }

            $ret[$data[0]] = stripslashes($data[1]);
        }
        fclose($handle);
        return $ret;
    }
}
