<?php

namespace MariaHill\Query;

use \MariaHill\MariaHill;
use \MariaHill\MariaHillException;
use \PDO;

class MysqlQuery
{
	private $distinct;
	private $foundrows;
	private $columns;
	private $tables;
	private $groupby;
	private $orderby;
	private $where;
	private $having;
	private $desc;
	private $offset;
	private $limit;
	private $stmt;
	private $owner;

	public function __construct(MariaHill $db){
		$this->db = $db;
	}

	private function prepare(){
		$sql = array('SELECT ');
		$sql[] = empty($this->foundrows) ? '' : ' SQL_CALC_FOUND_ROWS ';
		$sql[] = empty($this->columns) ? '*' : implode(',',$this->columns);
		$sql[] = $this->from();
		$sql[] = $this->whereclause();
		$sql[] = $this->orderby();
		$sql[] = $this->groupby();
		$sql[] = $this->limit();
		$query = implode(' ',$sql);
		$this->stmt = $this->db->db->prepare($query);
		return $this;
	}

	private function whereclause(){
		return empty($this->where) ? '' : 'WHERE '.$this->where;
	}

	private function execute(){
		$this->stmt->execute();
		return $this;
	}

	public function fetchObjects($tablename = null){
		$this->prepare();
		$this->execute();
		if(empty($tablename) && count($this->tables) > 1){
			throw new MariaHillException('fetching multiple tables, cannot decide which one to cast as');
		}
		$result = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach($result as &$data){
			$data = $this->db->castTableData($data, count($this->tables) > 1 ? $tablename : reset($this->tables));
		}
		$foundrows = null;
		if(!empty($this->foundrows)){
			$foundrows = $this->getFoundRows();
		}
		return array($result, $foundrows);
	}

	public function fetch(){
		$this->prepare();
		$this->execute();
		$result = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
		$foundrows = null;
		if(!empty($this->foundrows)){
			$foundrows = $this->getFoundRows();
		}
		return array($result, $foundrows);
	}

	private function getFoundRows(){
		$stmt = $this->db->db->prepare('SELECT FOUND_ROWS() as foundrows');
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		return $result['foundrows'];
	}

	public function foundRows(){
		$this->foundrows = true;
		return $this;
	}

	public function where($where){
		$this->where = $where;
		return $this;
	}

	public function order($cols){
		$this->orderby = 'ORDER BY '.implode(',',$cols);
		return $this;
	}

	public function desc(){
		$this->desc = true;
		return $this;
	}

	public function group($cols){
		$this->groupby = 'GROUP BY '.implode(',',$cols);
		return $this;
	}

	public function page($pagenum, $limit){
		$this->page = $pagenum;
		$this->limit = $limit;
		return $this;
	}

	public static function _and_($constraints){
		return ' ('.implode(' AND ',$constraints).') ';
	}

	public static function _or_($constraints){
		return ' ('.implode(' OR ',$constraints).') ';
	}

	public function owner($uuid){
		$this->owner = $uuid;
	}

	public function tables(array $tablenames){
		if(!is_array($tablenames)){
			throw new MariaHillException('Invalid argument $tablenames in '.__FILE__.' '.__LINE__);
		}
		foreach($tablenames as $table){
			$this->db->table($table);
		}
		$this->tables = empty($this->tables) ? $tablenames : array_merge($this->tables,$tablenames);
		return $this;
	}

	public function columns($colnames){
		if(!is_string($colnames)){
			throw new MariaHillException('Invalid argument $colnames in '.__FILE__.' '.__LINE__);
		}
		$colnames = explode(',',$colnames);
		foreach($colnames as $col){
			$col = trim($col);
			if((strpos($col,'.')!== false) || count($this->tables) > 1){
				list($table,$col) = explode('.',$col,2);
			}
			else{
				$table = reset($this->tables);
			}
			$col === '*' ?: $this->db->column($table, $col);
		}
		$this->columns = empty($this->columns)
			? $colnames
			: array_merge($this->columns, $colnames);
		return $this;
	}

	private function from(){
		$tables = $this->tables;
		$prev = array_shift($tables);
		$from = array('FROM '.$prev);
		foreach($tables as $jointo=>$table){
			$uniqid = 'tbl_'.uniqid();

			if(is_numeric($jointo)){
				$from[] = 'LEFT JOIN `relationships` AS `'.$uniqid.'` ON `'.$prev.'`.`uuid`=`'.$uniqid.'`.`uuid1` AND `'.$uniqid.'`.`table1`="'.$prev.'"';
				$from[] = 'LEFT JOIN `'.$table.'` ON `'.$table.'`.`uuid`=`'.$uniqid.'`.`uuid2` AND `'.$table.'`.`uuid` IS NOT NULL';
			}
			else{
				$from[] = 'LEFT JOIN `relationships` AS `'.$uniqid.'` ON `'.$jointo.'`.`uuid`=`'.$uniqid.'`.`uuid1` AND `'.$uniqid.'`.`table1`="'.$prev.'"';
				$from[] = 'LEFT JOIN `'.$table.'` ON `'.$table.'`.`uuid`=`'.$uniqid.'`.`uuid2` AND `'.$table.'`.`uuid` IS NOT NULL';
			}
			$prev = $table;
		}
		return implode(' ',$from);
	}

	private function groupby(){
		return empty($this->groupby)
			? ''
			: $this->groupby;
	}

	private function orderby(){
		return empty($this->orderby)
			? ''
			: $this->orderby;
	}

	private function limit(){
		return empty($this->limit)
			? ''
			: 'LIMIT '.(($this->page - 1)*$this->limit).','.$this->limit;
	}
}
