<?php

$directory = dirname(__FILE__);
$compareWith = 'textpattern/lang/en-gb.txt';

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

$it->rewind();
$used = array();
$myself = basename(__FILE__);

while ($it->valid()) {
    if (!$it->isDot()) {
        $filename = $it->getSubPathName();

        if (pathinfo($filename, PATHINFO_BASENAME) == $myself) {
            $it->next();
        }

        if (pathinfo($filename, PATHINFO_EXTENSION) === 'php') {
            $contents = file($it->key());
            $lineNum = 1;

            foreach ($contents as $line) {
                preg_match_all("/gTxt\(\'(.+)\'\)/iU", $line, $matches);

                if ($matches[0]) {
                    $parts = explode(',', $matches[0][0], 2);
                    $key = str_replace(array("gTxt('", "')", "'"), array('', '', ''), $parts[0]);
                    $argsList = array();

                    if (isset($parts[1])) {
                        preg_match_all("/\{(.+)\}/iU", $parts[1], $args);

                        if (isset($args[1])) {
                            $argsList = $args[1];
                        }
                    }

                    $used[$key] = array(
                        'args' => $argsList,
                        'file' => $filename,
                        'line' => $lineNum,
                        'when' => trim($line),
                    );
                }

                $lineNum++;
            }
        }
    }

    $it->next();
}

// $used now contains all (well, most) used gTxt() strings as keys.
// Cross-reference those against en-gb.txt to check we have them all defined.

$strings = file($compareWith);
$defined = array();

foreach ($strings as $string) {
    if (strpos($string, '=>') !== false) {
        $parts = explode('=>', $string);
        $key = trim($parts[0]);
        preg_match_all("/\{(.+)\}/iU", $parts[1], $args);
        $defined[$key] = $args[1];
    }
}

ksort($used);
ksort($defined);

$undefined = array();

foreach ($used as $key => $args) {
    if (!array_key_exists($key, $defined)) {
        $undefined[$key] = $args;
    }
}

// Format everything
foreach ($undefined as $key => $opts) {
    echo "\n".$key."\n";

    if ($args) {
        echo '--> args: ' . implode(', ', $opts['args'])."\n";
    }

    echo '--> file: ' . $opts['file']."\n";
    echo '--> line: ' . $opts['line']."\n";
    echo '--> when: ' . $opts['when']."\n";
}

