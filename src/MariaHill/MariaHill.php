<?php

namespace MariaHill;

use \PDO;
use \ReflectionClass;

class MariaHill
{
	use Traits\Common;
	use Traits\Util;

	public $db = null;
	public static $config = null;
	public static $meta = null;
	public static $pkgroot;
	private $database;
	private $options;

	private $ADMIN_MODE = false;

	public function __construct($ini = null){
		if(is_array($ini)){
			$this->config = $ini['mariahill'];
			$this->classmap = $ini['mariahill_classmap'];
		}
		else if(is_string($ini) && file_exists($ini) && ($config = parse_ini_file($ini,true))){
			$this->config = $config['mariahill'];
			$this->classmap = $config['mariahill_classmap'];
		}
		else{
			throw new MariaHillException('file does not exist: '.$ini);
		}
		$this->tablemap = array_flip($this->classmap);
		$this->ADMIN_MODE = empty($this->config['admin']) ? false : true;
		self::setDsnComponent($this->config['dsn'], 'charset', 'ascii');
		$this->db = new PDO($this->config['dsn'], $this->config['username'], $this->config['password']);
		$this->database = self::getDsnComponent($this->config['dsn'], 'dbname');
		$this->loadMeta();
		$this->database($this->database);
	}

	public function setScheme($scheme){
		$this->db->scheme = $scheme;
	}

	public function setHost($host){
		$this->db->host = $host;
	}

	public function setPort($port){
		$this->db->port = $port;
	}

	public function store(&$object){
		$className = get_class($object);
		$newClass = new ReflectionClass($className);
		$obj = $newClass->newInstanceWithoutConstructor();
		$objProperties = $newClass->getProperties();
		$data = array();
		$table = $this->tablemap[$className];
		foreach($objProperties as $property){
			$property->setAccessible(true);
			if(isset($this->meta[$this->database][$table][$property->getName()])){
				$data[':'.$property->getName()] = $property->getValue($object);
			}
		}
		if(empty($data[':uuid'])){
			$data[':uuid'] = $this->insert($table, $data);
		}
		else{
			$data[':uuid'] = $this->update($table, $data);
		}
		$object->uuid = $data[':uuid'];
		return $data[':uuid'];
	}

	private function insert($table, array $data){
		if(empty($data)){
			throw new MariaHillException(__LINE__.' nothing to insert');
		}
		if(isset($data[':uuid'])){
			throw new MariaHillException(__LINE__.' uuid '.$data[':uuid'].' already set.');
		}
		$data[':uuid'] = self::getUUID();
		$stmt = $this->db->prepare("INSERT INTO `".$this->database."`.`".$this->table($table)."` SET ".$this->array2sql($table, $data));
		$stmt->execute($data);
		return $data[':uuid'];
	}

	private function update($table, array $data){
		if(empty($data)){
			throw new MariaHillException(__LINE__.' nothing to update');
		}
		if(empty($data[':uuid'])){
			throw new MariaHillException(__LINE__.' uuid not found.');
		}
		$stmt = $this->db->prepare("UPDATE `".$this->database."`.`".$this->table($table)."` SET ".$this->array2sql($table, $data)." WHERE uuid=:uuid2");
		$stmt->execute(array_merge($data,array(':uuid2'=>$data[':uuid'])));
		return $data[':uuid'];
	}

	public function database($dbname){
		if(!$this->ADMIN_MODE && empty($this->meta[$dbname])){
			throw new MariaHillException('database '.$dbname.' not found');
		}
		return $dbname;
	}

	public function table($tablename){
		if(!$this->ADMIN_MODE && empty($this->meta[$this->database][$tablename])){
			throw new MariaHillException('table '.$this->database.'.'.$tablename.' not found');
		}
		return $tablename;
	}

	public function column($tablename, $colname){
		if(!$this->ADMIN_MODE && empty($this->meta[$this->database][$tablename][$colname])){
			throw new MariaHillException('column '.$this->database.'.'.$tablename.'.'.$colname.' not found');
		}
		return $colname;
	}

	public function e($string){
		$string = str_replace('`','',$string);
		return mysql_real_escape_string($string);
	}

	public function fetch($table, $uuid){
		if(isset($this->classmap[$table])){
			$stmt = $this->db->prepare("SELECT * FROM `".$this->database."`.`".$table."` WHERE uuid=?");
			$stmt->execute(array($uuid));
			$data = $stmt->fetch(PDO::FETCH_ASSOC);
			return $this->cast($data, $this->classmap[$table]);
		}
		throw new MariaHillException('Invalid table: '.$table.' not defined in classmap');
	}

	public function castTableData($data, $tablename){
		return $this->cast($data, $this->classmap[$tablename]);
	}

	private function cast($data, $classname){
		$newClass = new ReflectionClass($classname);
		$obj = $newClass->newInstanceWithoutConstructor();
		$objProperties = $newClass->getProperties();
		foreach($objProperties as $property){
			$property->setAccessible(true);
			$obj->{$property->getName()} = $data[$property->getName()];
		}
		return $obj;
	}

	public function delete(&$obj){
		if(empty($obj->uuid)){
			throw new MariaHillException(__LINE__.' uuid not found.');
		}
		$table = $this->tablemap[get_class($obj)];
		$this->db->beginTransaction();
		$stmt = $this->db->prepare("DELETE FROM `".$this->database."`.`".$this->table($table)."` WHERE uuid=?");
		$stmt->execute(array($obj->uuid));
		$stmt = $this->db->prepare("DELETE FROM `".$this->database."`.`relationships` WHERE uuid1=? OR uuid2=?");
		$stmt->execute(array($obj->uuid,$obj->uuid));
		$this->db->commit();
		$obj = null;
	}

	public function setUsername($username){
		$this->config['username'] = $username;
	}

	public function setPassword($password){
		$this->config['password'] = $password;
	}

    public function changeDatabase($dbname){
		$dbname = $this->database($dbname);
        if(empty($dbname)){
            throw new MariaHillException('No database selected');
        }
        if(empty($this->db)){
            throw new MariaHillException('Cant change nonexistant database handle');
        }
        self::setDsnComponent($this->config['dsn'], 'dbname', $dbname);
		self::setDsnComponent($this->config['dsn'], 'charset', 'ascii');
        $this->db = new PDO($this->config['dsn'], $this->config['username'], $this->config['password']);
		$this->database = $dbname;
		$this->loadMeta();
		$this->database($this->database);
    }

	public static function getPkgRoot(){
		return self::$pkgroot = empty(self::$pkgroot)
			? dirname(dirname(__DIR__))
			: self::$pkgroot;
	}

	public function linkIds($uuid1,$uuid2,$table1,$table2,$mutual = true){
		$this->db->beginTransaction();
		$stmt = $this->db->prepare("INSERT IGNORE INTO relationships SET uuid1=?,uuid2=?,table1=?,table2=?");
		$stmt->execute(array($uuid1,$uuid2,$table1,$table2));
		if($mutual){
			$stmt->execute(array($uuid2,$uuid1,$table2,$table1));
		}
		$this->db->commit();
	}

	public function linkObjects($obj1,$obj2,$mutual = true){
		$table1 = $this->tablemap[get_class($obj1)];
		$table2 = $this->tablemap[get_class($obj2)];
		$this->linkIds($obj1->uuid, $obj2->uuid, $table1, $table2, $mutual);
	}

	public function loadMeta(){
		if($this->ADMIN_MODE){
			return;
		}
		switch($this->config['meta_strategy']){
			case 'all':
				$this->meta = json_decode(file_get_contents($this->config['meta_dir'].'/all/meta.json'));
				break;
			case 'db':
				$this->meta = array($this->database => json_decode(file_get_contents($this->config['meta_dir'].'/db/'.$this->database.'.json'),true));
				break;
			default:
				throw new MariaHillException('Invalid meta_strategy: "'.$this->config['meta_strategy'].'"');
				break;
		}
	}
}
