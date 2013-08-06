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
use MariaHill\Query\MysqlQuery;
use MariaHillSample\Test;
use MariaHillSample\Test2;

$config = parse_ini_file(dirname(__DIR__).'/config/config.ini',true);
$db = new MariaHill($config);

$test = new Test();
$test->col1 = md5(uniqid());
$test->col2 = rand(1,1000);

$test2 = new Test2();
$test2->col1 = md5(uniqid());
$test2->col2 = rand(1,1000);

$db->store($test);
$db->store($test2);

$db->linkObjects($test,$test2);

sleep(20);

$query = new MysqlQuery($db);
list($result,$count) = $query
	->foundRows()
	->columns('test2.*')
	->tables(['test','test2'])
	->page(1,1)
	->fetchObjects('test2');

var_dump($result);
var_dump($count);

$query = new MysqlQuery($db);
list($result2,$count2) = $query
	->foundRows()
	->columns('test.*')
	->tables(['test2','test'])
	->page(1,1)
	->fetch();

var_dump($result2);
var_dump($count2);

$db->delete($test);
$db->delete($test2);
