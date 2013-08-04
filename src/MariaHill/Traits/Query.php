<?php

namespace MariaHill\Traits;

trait Query
{
	public function selectArrays($view, $keys){
		$view_type = $this->parseView($view);
	}

	public function selectObjects($view, $keys){
		$view_type = $this->parseView($view);
	}

	private function parseView($view){
		$components = explode(':',$view);
		
	}
}
