<?php
// An example of using php-webdminer.

require_once('vendor/autoload.php');

$config = new Config();
$config->setXML("jobs/yahoo_trending.xml");
$port = '4444';   // This is the default port

$mine = new Miner($port,"test");

echo $mine->run();   // Outputs XML of extracted data
