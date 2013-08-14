<?php

error_reporting(E_ALL);

include dirname(__DIR__) . '/vendor/autoload.php';

$loader = new \Composer\Autoload\ClassLoader();
$loader->add('Textpattern\Textpack\Test', __DIR__);
$loader->register();