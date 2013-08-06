<?php

/**
 *  In order to run this:
 *   - configure your config/config.ini file to use the correct
 *       server / database and point to correct "meta" directory
 *   - run:  bin/load_meta 
 *   - mysqldump your examples/mariahillsample.sql into your database server
 */

require_once(dirname(__DIR__).'/vendor/autoload.php');

use MariaHill\MariaHill;
use MariaHillSample\Test;

$config = parse_ini_file(dirname(__DIR__).'/config/config.ini',true);
$db = new MariaHill($config);

$test = new Test();
$test->col1 = 'abc';
$test->col2 = 123;


$start = microtime(true);


var_dump($db->isDuplicate('test.col1','abc'));
$uuid = $db->store($test);
var_dump($uuid);
var_dump($db->exists('test.col1','abc'));


$test->col1 = 'def';
$db->store($test);

$test2 = $db->fetch('test',$uuid);

var_dump($test2);

$db->delete($test2);

var_dump($test2);


echo 'FINISHED IN: '.(microtime(true)-$start).' SECONDS'.PHP_EOL;
