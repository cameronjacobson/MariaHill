<?php

namespace MariaHill;

use \PDO;

class MariaHill
{
	use Util;

	public $db = null;
	public static $config = null;
	public static $meta = null;
	public static $pkgroot;

	public function __construct($ini = null){
		if(is_array($ini)){
			$this->config = $ini;
		}
		else if(is_string($ini) && file_exists($ini) && ($config = parse_ini_file($ini,true))){
			$this->config = $config['mariahill'];
		}
		else{
			throw new MariaHillException('file does not exist: '.$ini);
		}
		$this->db = new PDO($this->config['dsn'], $this->config['username'], $this->config['password']);
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
		return $this->db->store($object);
	}

	public function fetch($id){
		return $this->db->fetch($id);
	}

	public function delete(&$obj){
		$obj->_delete = true;
		$this->db->store($obj);
		$obj = null;
	}

	public function setUsername($username){
		$this->config['username'] = $username;
	}

	public function setPassword($password){
		$this->config['password'] = $password;
	}

    public function changeDatabase($dbname){
        if(empty($dbname)){
            throw new MariaHillException('No database selected');
        }
        if(empty($this->db)){
            throw new MariaHillException('Cant change nonexistant database handle');
        }
        self::setDsnComponent($this->config['dsn'], 'dbname', $dbname);
        $this->db = new PDO($this->config['dsn'], $this->config['username'], $this->config['password']);
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
