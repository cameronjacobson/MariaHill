<?php

namespace MariaHill\Traits;

trait Common
{

	// FETCH
	private function fetchOne($uuid){
		
	}

	private function fetchMany(array $uuids){
		$this->db->beginTransaction();
		foreach($uuids as $uuid){
			$this->fetchOne($uuid);
		}
		$this->db->commit();
	}

	public function fetch($uuid){
		if(is_array($uuid)){
			$this->fetchMany($uuid);
		}
		else{
			$this->fetchOne($uuid);
		}
	}


	// STORE
	private function storeOne(&$obj){
		if(empty($obj->uuid)){
			return $this->insert($obj);
		}
		else{
			return $this->update($obj);
		}
	}

	private function storeMany(array &$objs){
		$this->db->beginTransaction();
		foreach($objs as &$obj){
			$obj->uuid = $this->storeOne($obj);
		}
		$this->db->commit();
		return $objs;
	}

	public function store(&$obj){
		if(is_array($obj)){
			$this->storeMany($obj);
		}
		else{
			$this->storeOne($obj);
		}
	}


	// DELETE
	private function deleteOne($obj){
		
	}

	public function deleteMany(array $objs){
		$this->db->beginTransaction();
		foreach($objs as $obj){
			$this->storeOne($obj);
		}
		$this->db->commit();
	}

	public function delete($obj){
		if(is_array($obj)){
			$this->deleteMany($obj);
		}
		else{
			$this->deleteOne($obj);
		}
	}

	public function link($obj1, $obj2){
		
	}
}
