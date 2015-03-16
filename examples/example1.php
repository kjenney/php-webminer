<?php
// An example of using php-webdminer.

require_once('../vendor/autoload.php');

$config = new Config();
$config->setConfig("example1.xml");

$mine = new Miner("true");	// True for verbose logging to log.log

echo $mine->run();
