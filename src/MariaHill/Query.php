<?php

namespace MariaHill;

use MariaHill;

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
	private $tables;

	public function __construct(MariaHill $db){
		$this->db = $db;
	}

	public function prepare(){
		$sql = array('SELECT '.implode(',',$this->columns));
		$sql[] = $this->from();
		$sql[] = empty($this->where) ? '' : $this->where;
		$sql[] = $this->orderby();
		$sql[] = $this->groupby();
		$sql[] = $this->limit();
		return implode(' ',$sql);
	}

	public function where($string){
		$this->where = 'WHERE '.$string;
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

	public static function and($constraints){
		return ' ('.implode(' AND ',$constraints).') ';
	}

	public static function or($constraints){
		return ' ('.implode(' OR ',$constraints).') ';
	}


	public function tables(array $tablenames){
		foreach($tablenames as $table){
			$this->db->table($table);
		}
		$this->tables = $tablenames;
	}

	public function columns(array $colnames){
		foreach($colnames as $col){
			list($table,$col) = explode('.',$col,2);
			$this->db->column($table, $col);
		}
		$this->columns = empty($this->columns)
			? $colnames
			: array_merge($this->columns, $colnames);
		return $this;
	}



	private function from(){
		$prev = array_shift($this->tables);
		$from = array('FROM '.$prev);
		foreach($tables as $jointo=>$table){
			$uniqid = 'tbl_'.uniqid();
			if(is_numeric($jointo)){
				$from[] = 'LEFT JOIN `relationships` AS `'.$uniqid.'` ON `'.$prev.'`.`uuid`=`'.$uniqid.'`.`uuid` AND `'.$uniqid.'`.`table`="'.$table.'"';
				$from[] = 'LEFT JOIN `'.$table.'` ON `'.$table.'`.`uuid`=`'.$uniqid.'`.`uuid2`';
			}
			else{
				$from[] = 'LEFT JOIN `relationships` AS `'.$uniqid.'` ON `'.$jointo.'`.`uuid`=`'.$uniqid.'`.`uuid` AND `'.$uniqid.'`.`table`="'.$table.'"';
				$from[] = 'LEFT JOIN `'.$table.'` ON `'.$table.'`.`uuid`=`'.$uniqid.'`.`uuid2`';
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
