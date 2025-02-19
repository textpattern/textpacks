<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2025 The Textpattern Development Team
 *
 * Textpattern is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * Textpattern is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Examples:
 *
 * 1. Reindex the language files
 * php move-strings.php
 *
 * 2. Reindex the setup language files
 * php move-strings.php --dir=../lang-setup
 *
 * 3. Move 'show' and meta' from the admin-side group, and 'css_name' from the css group to the common group, and reindex
 * php move-strings.php --keys=admin-side.show,admin-side.meta,css.css_name --to=common
 *
 * 4. Move all the pre-defined role strings from the admin group to the admin-side group, and reindex
 * php move-strings.php --keys=publisher,managing_editor,staff_writer,copy_editor,designer,freelancer --from=admin --to=admin-side
 */
define('txpinterface', 'cli');

if (php_sapi_name() !== 'cli') {
    die('command line only');
}

if (!function_exists('write_ini_file')) {
    /**
     * Write an ini configuration file
     * 
     * @param string $filename
     * @param array  $array to write
     * @return bool
     */
    function write_ini_file($file, $array = array())
    {
        // check first argument is string
        if (!is_string($file)) {
            throw new \InvalidArgumentException('write_ini_file expects argument 1 to be a string.');
        }

        // check second argument is array
        if (!is_array($array)) {
            throw new \InvalidArgumentException('write_ini_file expects argument 2 to be an array.');
        }

        // process array
        $data = array();

        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $data[] = "[$key]";

                foreach ($val as $skey => $sval) {
                    if (is_array($sval)) {
                        foreach ($sval as $_skey => $_sval) {
                            if (is_numeric($_skey)) {
                                $data[] = $skey.'[]='.(is_numeric($_sval) ? $_sval : '"'.$_sval.'"');
                            } else {
                                $data[] = $skey.'['.$_skey.']='.(is_numeric($_sval) ? $_sval : '"'.$_sval.'"');
                            }
                        }
                    } else {
                        $data[] = $skey.'='.(is_numeric($sval) ? $sval : '"'.$sval.'"');
                    }
                }
            } else {
                $data[] = $key.'='.(is_numeric($val) ? $val : '"'.$val.'"');
            }
        }

        // open file pointer, init flock options
        $fp = fopen($file, 'w');

        if ($fp) {
            fwrite($fp, implode(PHP_EOL, $data).PHP_EOL);
        }

        fclose($fp);

        return true;
    }
}

/**
 * Custom sort to ignore (most) txp_ prefixed keys
 *
 * @param  [type] $a First comparator value
 * @param  [type] $b Second comparator value
 * @return int
 */
function cmp_prefix($a, $b)
{
    $a = (strpos($a, 'txp_') === 0 && $a !== 'txp_evaluate_functions') ? str_replace('txp_', '', $a) : $a;
    $b = (strpos($b, 'txp_') === 0 && $b !== 'txp_evaluate_functions') ? str_replace('txp_', '', $b) : $b;

    return strcasecmp($a, $b);
}

// Start of script proper.
// --keys can either be a list of group.keys, or the group can be specified as --from=group.
$usage = "usage: {$argv[0]} --keys=list,of,keys,to,move --to=<group to move to> [--from=<group to move from> --dir=<directory>]\n";
$directory = dirname(__FILE__);

// Handle command line options and defaults.
$shortopts = '';
$longopts = array('keys::', 'from::', 'to::', 'dir::');
$defaults = array(
    'dir' => '../lang',
    'to' => '@common', // Skip by default
);

$options = getopt($shortopts, $longopts) + $defaults;
$destdir = $directory.'/'.$options['dir'].'/';

if (!empty($options['keys']) && empty($options['to'])) {
    die($usage);
}

if (!empty($options['to']) && empty($options['keys'])) {
    die($usage);
}

if (!is_writable($destdir)) {
    die("usage: {$argv[0]} Directory $destdir is not read/writable\n");
}

// Find all files ready to iterate over them.
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($destdir));

$it->rewind();
$keys = array();

if (!empty($options['keys'])) {
    $keys = explode(',', $options['keys']);
}

while ($it->valid()) {
    if (!$it->isDot()) {
        $filename = $it->getSubPathName();

        if (pathinfo($filename, PATHINFO_EXTENSION) === 'ini') {
            // Use raw processing to preserve double quotes in strings.
            $contents = parse_ini_file($it->key(), true, INI_SCANNER_RAW);

            // Check the destination area exists and isn't the first special area.
            if (array_key_exists($options['to'], $contents) && $options['to'] !== '@common') {
                foreach ($keys as $fullkey) {
                    $keyparts = explode('.', $fullkey);

                    // If no group prefix in the key name, try the 'from' parameter.
                    if (count($keyparts) === 1) {
                        array_unshift($keyparts, (empty($options['from']) ? '' : $options['from']));
                    }

                    if (array_key_exists($keyparts[0], $contents) && $keyparts[0] !== '@common') {
                        if (array_key_exists($keyparts[1], $contents[$keyparts[0]])) {
                            // Yep, found the source key so hack it out of its existing
                            // group and put it in the destination group.
                            $toCopy = $contents[$keyparts[0]][$keyparts[1]];
                            $contents[$options['to']][$keyparts[1]] = $toCopy;
                            unset($contents[$keyparts[0]][$keyparts[1]]);
                        }
                    }
                }
            }

            // Sort each group by key, using a custom function to tweak the output.
            foreach ($contents as $area => $strings) {
                if ($area !== '@common') {
                    uksort($contents[$area], 'cmp_prefix');
                }
            }

            // Rewrite the file.
            write_ini_file($it->key(), $contents);
        }
    }

    $it->next();
}

