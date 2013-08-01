<?php

function get_databases(PDO $db){
	$stmt = $db->prepare("SHOW DATABASES");
	$stmt->execute(array());
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$return = array();
	foreach($result as $row){
		$return[] = $row['Database'];
	}
	return $return;
}

function get_tables(PDO $db, $database){
	$stmt = $db->prepare("SHOW TABLES");
	$stmt->execute(array());
	$return = array();
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach($result as $row){
		$return[] = $row['Tables_in_'.$database];
	}
	return $return;
}

function get_table_description(PDO $db, $database, $table){
	$tables = get_tables($db, $database);
	if(!in_array($table, $tables)){
		throw new Exception('Table '.$table.' does not exist');
	}
	$stmt = $db->prepare("DESCRIBE ".$table);
	$stmt->execute(array($table));
	$return = array();
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach($result as $row){
		$return[] = implode(' | ', $row);
	}
	return $return;
}

function set_database($dsn, $database){
	if(empty($database)){
		throw new Exception('No database selected');
	}
	list($scheme, $dsn) = explode(':', $dsn, 2);
	$dsn_parts = explode(';',$dsn);
	$changed = false;
	foreach($dsn_parts as &$part){
		if(strpos($part, 'dbname=') === 0){
			$part = 'dbname='.$database;
			$changed = true;
		}
	}
	return $scheme.':'.implode(';',$dsn_parts).(empty($changed) ? ';dbname='.$database : '');
}
