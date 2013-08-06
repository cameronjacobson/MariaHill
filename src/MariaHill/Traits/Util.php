<?php

namespace MariaHill\Traits;

use \PDO;
use \MariaHill\MariaHill;
use \MariaHill\Query\MysqlQuery;

trait Util
{
	public static function showDatabases(MariaHill $maria){
		$db = $maria->db;
		$stmt = $db->prepare("SHOW DATABASES");
		$stmt->execute(array());
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$return = array();
		foreach($result as $row){
			$return[] = $row['Database'];
		}
		return $return;
	}

	public static function showTables(MariaHill $maria){
		$db = $maria->db;
		$database = self::getDsnComponent($maria->config['dsn'], 'dbname');
		$stmt = $db->prepare('SHOW TABLES');
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$return = array();
		foreach($result as $row){
			$return[] = $row['Tables_in_'.$database];
		}
		return $return;
	}

	public static function tableDescription(MariaHill $maria, $table){
		$db = $maria->db;
		$stmt = $db->prepare("DESCRIBE ".$table);
		$stmt->execute(array());
		$return = array();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach($result as $row){
			$return[$row['Field']] = array(
				'type'=>$row['Type'],
				'null'=>$row['Null'],
				'key'=>$row['Key'],
				'default'=>$row['Default'],
				'extra' => $row['Extra']
			);
		}
		return $return;
	}

	public static function setDsnComponent(&$dsn, $key, $value){
		list($scheme, $params) = explode(':', $dsn, 2);
		$dsn_parts = explode(';',$params);
		$changed = false;
		foreach($dsn_parts as $k=>&$part){
			if(empty($part)){
				unset($dsn_parts[$k]);
			}
			if(strpos($part, $key.'=') === 0){
				$part = $key.'='.$value;
				$changed = true;
			}
		}
		if(!$changed){
			$dsn_parts[] = $key.'='.$value;
		}
		$dsn = $scheme.':'.implode(';',$dsn_parts);
	}

	public static function getDsnComponent($dsn, $key){
		list($scheme, $params) = explode(':', $dsn, 2);
		$dsn_parts = explode(';',$params);
		foreach($dsn_parts as $part){
			list($k, $v) = explode('=', $part, 2);
			if($key === $k) {
				return trim($v);
			}
		}
		return null;
	}

	public static function getUUID(){
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}

	private function array2sql($table, array $data){
		$sql = array();
		foreach($data as $key=>$value){
			$sql[] = '`'.$this->column($table, substr($key,1)).'` = '.$key;
		}
		return implode(',',$sql);
	}

	public function isDuplicate($col, $value){
		list($table,$column) = explode('.',$col);
		$this->table($table);
		$this->column($table, $column);
		$stmt = $this->db->prepare('SELECT COUNT(*) as count FROM `'.$this->database.'`.`'.$table.'` WHERE `'.$column.'`=?');
		$stmt->execute(array($value));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row['count'] > 0 ? true : false;
	}

	public function exists($col, $value){
		return $this->isDuplicate($col,$value);
	}

	public function fetchIdByKey($col, $value){
		$result = $this->fetchValueByKey($col, $value);
		foreach($result as $k=>$v){
			$result[$k] = $row['uuid'];
		}
		return $result;
	}

	public function fetchValueByKey($col, $value){
		list($table, $column) = explode('.',$col);
		$this->table($table);
		$this->column($table, $column);
		$query = new MysqlQuery($this);
		list($result,$count) = $query
			->tables([$table])
			->where('`'.$table.'`.`'.$column.'` = "'.mysql_real_escape_string((string)$value).'"')
			->fetch();
		return $result;
	}
}
