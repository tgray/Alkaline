<?php

/*
// Alkaline
// Copyright (c) 2010-2011 by Budin Ltd. All rights reserved.
// Do not redistribute this code without written permission from Budin Ltd.
// http://www.alkalinenapp.com/
*/

/**
 * @author Budin Ltd. <contact@budinltd.com>
 * @copyright Copyright (c) 2010-2011, Budin Ltd.
 * @version 1.0
 */

class Pile extends Alkaline{
	public $pile_id;
	public $pile_count;
	public $piles;
	
	/**
	 * Initiate Pile object
	 *
	 * @param array|int|string $pile Search piles (pile IDs, pile titles)
	 */
	public function __construct($pile=null){
		parent::__construct();
		
		if(!empty($pile)){
			$sql_params = array();
			if(is_int($pile)){
				$pile_id = $pile;
				$query = $this->prepare('SELECT * FROM piles WHERE pile_id = ' . $pile_id . ';');
			}
			elseif(is_string($pile)){
				$query = $this->prepare('SELECT * FROM piles WHERE (LOWER(pile_title_url) LIKE :pile_title_url);');
				$sql_params[':pile_title_url'] = '%' . strtolower($pile) . '%';
			}
			elseif(is_array($pile)){
				$pile_ids = convertToIntegerArray($pile);
				$query = $this->prepare('SELECT * FROM piles WHERE pile_id = ' . implode(' OR pile_id = ', $pile_ids) . ';');
			}
			
			if(!empty($query)){
				$query->execute($sql_params);
				$this->piles = $query->fetchAll();
				
				$this->pile_count = count($this->piles);
			}
		}
	}
	
	public function __destruct(){
		parent::__destruct();
	}
	
	/**
	 * Perform Orbit hook
	 *
	 * @param Orbit $orbit 
	 * @return void
	 */
	public function hook($orbit=null){
		if(!is_object($orbit)){
			$orbit = new Orbit;
		}
		
		$this->piles = $orbit->hook('pile', $this->piles, $this->piles);
	}
	
	public function create(){
		
	}
	
	/**
	 * Fetch all pages
	 *
	 * @return void
	 */
	public function fetchAll(){
		$query = $this->prepare('SELECT * FROM piles;');
		$query->execute();
		$this->piles = $query->fetchAll();
		
		$this->pile_count = count($this->piles);
	}
	
	public function search($search=null){
		
	}
	
	/**
	 * Update pages
	 *
	 * @param array $fields Associate array of columns and fields
	 * @return void
	 */
	public function update($fields){
		$ids = array();
		foreach($this->piles as $pile){
			$ids[] = $pile['pile_id'];
		}
		return parent::updateRow($fields, 'piles', $ids);
	}
}

?>