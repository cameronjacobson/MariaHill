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

	public function setUsername($user){
		$this->db->user = $user;
	}

	public function setPassword($pass){
		$this->db->pass = $pass;
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

	public function changeUsername($username){
		$this->config['username'] = $username;
	}

	public function changePassword($password){
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
$this->db = null;
        $this->db = new PDO($this->config['dsn'], $this->config['username'], $this->config['password']);
    }

	public static function getMeta(){
		self::$config = self::getConfig();
		return self::$meta = empty(self::$meta)
			? json_decode(file_get_contents(self::getPkgRoot().'/'.self::$config['mariahill']['meta']))
			: self::$meta;
	}

	public static function getPkgRoot(){
		return self::$pkgroot = empty(self::$pkgroot)
			? dirname(dirname(__DIR__))
			: self::$pkgroot;
	}
}
