<?php
// An example of using php-webdminer.

require_once('../vendor/autoload.php');

$config = new Config();
$config->setConfig("example1.xml");
$port = '4444';   // This is the default port

$mine = new Miner();

echo $mine->run();   // Outputs XML of extracted data
