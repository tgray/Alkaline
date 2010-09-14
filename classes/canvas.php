<?php

class Canvas extends Alkaline{
	public $tables;
	public $template;
	
	public function __construct($template=null){
		parent::__construct();
		
		$this->tables = array('photos', 'comments', 'tags');
		$this->template = (empty($template)) ? '' : $template . "\n";
	}
	
	public function __destruct(){
		parent::__destruct();
	}
	
	public function __toString(){
		self::generate();
		
		// Return unevaluated
		return $this->template;
	}
	
	// APPEND
	// Append a string to the template
	public function append($template){
		 $this->template .= $template . "\n";
	}
	
	// APPEND LOAD
	// Append a file to the template
	public function load($file){
		 $this->template .= file_get_contents(PATH . THEMES . THEME . '/' . $file . TEMP_EXT) . "\n";
	}
	
	// VARIABLES
	public function assign($var, $value){
		// Error checking
		if(empty($value)){
			return false;
		}
		
		// Set variable, scrub to remove conditionals
		$this->template = str_ireplace('<!-- ' . $var . ' -->', $value, $this->template);
		$this->template = self::scrub($var, $this->template);
		return true;
	}
	
	// LOOPS
	// Set photo array to loop
	public function loop($array){
		$loops = array();
		
		$table_regex = implode('|', $this->tables);
		$table_regex = strtoupper($table_regex);
		
		$matches = array();
		
		preg_match_all('/\<!-- LOOP\((' . $table_regex . ')\) --\>(.*?)\<!-- ENDLOOP\(\1\) --\>/s', $this->template, $matches, PREG_SET_ORDER);
		
		if(count($matches) > 0){
			$loops = array();
			
			foreach($matches as $match){
				$match[1] = strtolower($match[1]);
				
				// Wrap in <form> for commenting
				if($match[1] == 'photos'){
					$match[2] = '<form action="" id="photo_<!-- PHOTO_ID -->" class="photo" method="post">' . $match[2] . '</form>';
				}
				$loops[] = array('replace' => $match[0], 'reel' => $match[1], 'template' => $match[2], 'replacement' => '');
			}
		}
		else{
			return false;
		}
		
		for($j = 0; $j < count($loops); ++$j){
			$replacement = '';
			$reel = $array->$loops[$j]['reel'];
			
			for($i = 0; $i < count($reel); ++$i){
				$loop_template = $loops[$j]['template'];
				
				foreach($reel[$i] as $key => $value){
					if(is_array($value)){
						$value = var_export($value, true);
					}
					$loop_template = str_ireplace('<!-- ' . $key . ' -->', $value, $loop_template);
					if(!empty($value)){
						$loop_template = self::scrub($key, $loop_template);
					}
				}
				
				$loop_template = self::loopSub($array, $loop_template, $reel[$i]['photo_id']);
				
				$replacement .= $loop_template;
			}
			
			$loops[$j]['replacement'] = $replacement;
		}
		
		foreach($loops as $loop){
			$this->template = str_replace($loop['replace'], $loop['replacement'], $this->template);
			$this->template = self::scrub($loop['reel'], $this->template);
		}
		
		return true;
	}
	
	// Set subarrays to loop
	protected function loopSub($array, $template, $photo_id){
		$loops = array();
		
		$table_regex = implode('|', $this->tables);
		$table_regex = strtoupper($table_regex);
		
		$matches = array();
		
		preg_match_all('/\<!-- LOOP\((' . $table_regex . ')\) --\>(.*?)\<!-- ENDLOOP\(\1\) --\>/s', $template, $matches, PREG_SET_ORDER);
		
		if(count($matches) > 0){
			$loops = array();
			
			foreach($matches as $match){
				$match[1] = strtolower($match[1]);
				$loops[] = array('replace' => $match[0], 'reel' => $match[1], 'template' => $match[2], 'replacement' => '');
			}
		}
		else{
			return $template;
		}
		
		for($j = 0; $j < count($loops); ++$j){
			$replacement = '';
			$reel = $array->$loops[$j]['reel'];
			
			for($i = 0; $i < count($reel); ++$i){
				$loop_template = '';
				
				if($reel[$i]['photo_id'] == $photo_id){
					if(empty($loop_template)){
						$loop_template = $loops[$j]['template'];
					}
					foreach($reel[$i] as $key => $value){
						if(is_array($value)){
							$value = var_export($value, true);
						}
						$loop_template = str_ireplace('<!-- ' . $key . ' -->', $value, $loop_template);
						if(!empty($value)){
							$loop_template = self::scrub($key, $loop_template);
						}
					}
				}
				
				$replacement .= $loop_template;
			}
			
			$loops[$j]['replacement'] = $replacement;
		}
		
		foreach($loops as $loop){
			if(!empty($loop['replacement'])){
				$template = str_replace($loop['replace'], $loop['replacement'], $template);
				$template = self::scrub($loop['reel'], $template);
			}
		}
		
		return $template;
	}
	
	// PREPROCESS
	// Remove conditionals after successful variable, loop placement
	public function scrub($var, $template){
		$template = str_ireplace('<!-- IF(' . $var . ') -->', '', $template);
		if(stripos($template, '<!-- ELSEIF(' . $var . ') -->')){
			$template = preg_replace('/\<\!-- ELSEIF\(' . $var . '\) --\>(.*?)\<\!-- ENDIF\(' . $var . '\) --\>/is', '', $template);
		}
		else{
			$template = str_ireplace('<!-- ENDIF(' . $var . ') -->', '', $template);
		}
		return $template;
	}
	
	// ORBIT
	// Find Orbit hooks and process them
	protected function initOrbit(){
		$orbit = new Orbit();
		
		$matches = array();
		preg_match_all('#\<!-- ORBIT\_([A-Z0-9_]*) --\>#is', $this->template, $matches, PREG_SET_ORDER);
		
		if(count($matches) > 0){
			$hooks = array();
			
			foreach($matches as $match){
				$hook = strtolower($match[1]);
				$hooks[] = array('replace' => $match[0], 'hook' => $hook);
			}
		}
		else{
			return false;
		}
		
		foreach($hooks as $hook){
			ob_start();
			
			// Execute Orbit hook
			$orbit->hook($hook['hook']);
			$content = ob_get_contents();
			
			// Replace contents
			$this->template = str_ireplace($hook['replace'], $content, $this->template);
			ob_end_clean();
		}
	}
	
	// BLOCKS
	// Find Canvas blocks and process them
	protected function initBlocks(){
		$matches = array();
		preg_match_all('#\<!-- CANVAS\_([A-Z0-9_]*) --\>#is', $this->template, $matches, PREG_SET_ORDER);
		
		if(count($matches) > 0){
			$blocks = array();
			
			foreach($matches as $match){
				$block = strtolower($match[1]);
				$blocks[] = array('replace' => $match[0], 'block' => $block);
			}
		}
		else{
			return false;
		}
		
		foreach($blocks as $block){
			$path = PATH . BLOCKS . $block['block'] . '.php';
			
			if(is_file($path)){
				ob_start();

				// Include block
				include($path);
				$content = ob_get_contents();
				
				// Replace contents
				$this->template = str_ireplace($block['replace'], $content, $this->template);
				ob_end_clean();
			}
		}
	}
	
	// PROCESS
	public function generate(){
		// Add copyright information
		$this->assign('COPYRIGHT', parent::copyright);
		
		// Process Orbit and Blocks
		$this->initOrbit();
		$this->initBlocks();
		
		// Remove unused conditionals, replace with ELSEIF as available
		$this->template = preg_replace('/\<!-- IF\(([A-Z0-9_]*)\) --\>(.*?)\<!-- ELSEIF\(\1\) --\>(.*?)\<!-- ENDIF\(\1\) --\>/is', '$3', $this->template);
		$this->template = preg_replace('/\<!-- IF\(([A-Z0-9_]*)\) --\>(.*?)\<!-- ENDIF\(\1\) --\>/is', '', $this->template);
		
		return true;
	}
	
	// DISPLAY
	public function display(){
		self::generate();
		
		// Echo after evaluating
		echo @eval('?>' . $this->template);
	}
}

?>