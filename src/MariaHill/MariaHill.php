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

	public function __construct($ini = null){
		if(is_array($ini)){
			$this->config = $ini['mariahill'];
			$this->classmap = $ini['mariahill_classmap'];
		}
		else if(is_string($ini) && file_exists($ini) && ($config = parse_ini_file($ini,true))){
			$this->config = $config['mariahill'];
			$this->classmap = $config['mariahill_classmap'];
			$this->tablemap = array_flip($this->classmap);
		}
		else{
			throw new MariaHillException('file does not exist: '.$ini);
		}
		self::setDsnComponent($this->config['dsn'], 'charset', 'ascii');
		$this->db = new PDO($this->config['dsn'], $this->config['username'], $this->config['password']);
		$this->database = $this->database(self::getDsnComponent($this->config['dsn'], 'dbname'));
		$this->loadMeta();
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

	public function store($object){
		$objProperties = $object->getProperties();
		$data = array();
		$table = $this->tablemap[get_class($object)];
		foreach($objProperties as $property){
			$property->setAccessible(true);
			if(isset($this->meta[$this->database][$table][$property->getName()])){
				$data[':'.$property->getName()] = $property->getValue();
			}
		}
		if(empty($data['uuid'])){
			$this->insert($table, $data);
		}
		else{
			$this->update($table, $data);
		}
		return $data['uuid'];
	}

	private function insert($table, array $data){
		if(empty($data)){
			throw new MariaHillException(__LINE__.' nothing to insert');
		}
		if(isset($data[':uuid'])){
			throw new MariaHillException(__LINE__.' uuid '.$data[':uuid'].' already set.');
		}
		$data[':uuid'] = self::getUUID();
		$this->prepare("INSERT INTO `".$this->database."`.`".$this->table($table)."` SET ".$this->array2sql($data));
		return $this->execute($data);
	}

	private function update($table, array $data){
		if(empty($data)){
			throw new MariaHillException(__LINE__.' nothing to update');
		}
		if(empty($data[':uuid'])){
			throw new MariaHillException(__LINE__.' uuid not found.');
		}
		$this->prepare("UPDATE `".$this->database."`.`".$this->table($table)."` SET ".$this->array2sql($data)." WHERE uuid=?");
		return $this->execute($data);
	}

	private function database($dbname){
		if(empty($this->meta[$dbname])){
			throw new MariaHillException('database '.$dbname.' not found');
		}
		return $dbname;
	}

	private function table($tablename){
		if(empty($this->meta[$this->database][$tablename])){
			throw new MariaHillException('table '.$this->database.'.'.$tablename.' not found');
		}
		return $tablename;
	}

	private function column($tablename, $colname){
		if(empty($this->meta[$this->database][$tablename][$colname])){
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
		$stmt = $this->db->prepare("DELETE FROM `".$this->database."`.`".$this->table($table)."` WHERE uuid=?");
		$stmt->execute(array($obj->uuid));
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
    }

	public static function getPkgRoot(){
		return self::$pkgroot = empty(self::$pkgroot)
			? dirname(dirname(__DIR__))
			: self::$pkgroot;
	}

	public function loadMeta(){
		switch($this->config['meta_strategy']){
			case 'all':
				break;
			case 'db':
				break;
			default:
				throw new MariaHillException('Invalid meta_strategy: "'.$this->config['meta_strategy'].'"');
				break;
		}
	}
}
