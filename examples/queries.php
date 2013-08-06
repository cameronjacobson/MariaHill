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

$config = parse_ini_file(dirname(__DIR__).'/config/config.ini',true);
$db = new MariaHill($config);

$uuids = array();
for($x=0;$x<10;$x++){
	$test = new Test();
	$test->col1 = md5(uniqid());
	$test->col2 = rand(1,1000);
	$uuids[] = $uuid = $db->store($test);
	$tests[$uuid] = $test;
}

var_dump($db->fetchValueByKey('test.col2',$test->col2));
var_dump($db->fetchIdByKey('test.col1',substr($test->col1,0,25)));

$query = new MysqlQuery($db);
list($result,$count) = $query
	->foundRows()
	->tables(['test'])
	->order(['uuid'])
	->page(1,4)
	->fetchObjects();

var_dump($result);
var_dump($count);

list($result2,$count2) = $query
	->foundRows()
	->tables(['test'])
	->order(['uuid DESC'])
	->page(1,4)
	->where('left(test.uuid,1) = "a"')
	->fetch();

var_dump($result2);
var_dump($count2);

foreach($tests as &$test){
	$db->delete($test);
}
