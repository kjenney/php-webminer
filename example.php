<?php
// An example of using php-webdminer.

require_once('vendor/autoload.php');

$xml = file_get_contents('examples/yahoo_trending.xml');
$port = '4444';   // This is the default port

$mine = new Miner($port,$xml,"test");

echo $mine;   // Outputs XML of extracted data
