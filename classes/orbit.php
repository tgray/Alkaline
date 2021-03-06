<?php

/*
// Alkaline
// Copyright (c) 2010-2012 by Budin Ltd. Some rights reserved.
// http://www.alkalineapp.com/
*/

/**
 * @author Budin Ltd. <contact@budinltd.com>
 * @copyright Copyright (c) 2010-2012, Budin Ltd.
 * @version 1.0
 */

class Orbit extends Alkaline{
	public $id;
	public $uid;
	
	public $class;
	public $file;
	public $folder;
	public $hooks;
	public $preferences;
	public $title;
	
	public $extensions;
	
	public $extension_count;
	
	private $db_safe;
	
	/**
	 * Initiate Orbit object
	 *
	 * @param int|array $id Orbit IDs (otherwise all)
	 */
	public function __construct($id=null){
		parent::__construct();
		
		// Start Orbit Engine
		if(!is_subclass_of($this, 'Orbit')){
			if(empty($_SESSION['alkaline']['extensions'])){
				if(empty($id)){
					$query = $this->prepare('SELECT * FROM extensions WHERE extension_status > 0 ORDER BY extension_title ASC;');
				}
				else{
					$id = intval($id);
					$query = $this->prepare('SELECT * FROM extensions WHERE extension_id = ' . $id . ' AND extension_status > 0;');
				}
				$query->execute();
				$extensions = $query->fetchAll();

				$this->extensions = array();

				foreach($extensions as &$extension){
					$extension['extension_uid'] = strval($extension['extension_uid']);
					$extension['extension_file'] = parent::correctWinPath(PATH . EXTENSIONS . $extension['extension_folder'] . '/' . $extension['extension_file'] . '.php');
					$extension['extension_hooks'] = unserialize($extension['extension_hooks']);
				}
			
				$_SESSION['alkaline']['extensions'] = $extensions;
			}
			
			$this->extensions = $_SESSION['alkaline']['extensions'];
			$this->extension_count = count($this->extensions);
		}
		// Prepare Orbit-powered extension
		else{
			if(empty($_SESSION['alkaline']['extensions'])){
				if(empty($id)){
					$query = $this->prepare('SELECT * FROM extensions WHERE extension_class = :extension_class AND extension_status > 0;');
					$query->execute(array(':extension_class' => get_class($this)));
				}
				else{
					$id = intval($id);
					$query = $this->prepare('SELECT * FROM extensions WHERE extension_id = ' . $id . ' AND extension_status > 0;');
					$query->execute();
				}
				$extensions = $query->fetchAll();
			
				if(count($extensions) != 1){
					return false;
				}
				
				$extension = $extensions[0];
			}
			else{
				$extensions = $_SESSION['alkaline']['extensions'];
				
				if(!empty($id)){
					$extension_ids = array();
					foreach($extensions as $extension){
						$extension_ids[] = $extension['extension_id'];
					}
					$extension_key = array_search($id, $extension_ids);
				}
				else{
					$class = get_class($this);
					
					$extension_classes = array();
					foreach($extensions as $extension){
						$extension_classes[] = $extension['extension_class'];
					}
					$extension_key = array_search($class, $extension_classes);
				}
				
				if($extension_key === false){
					return false;
				}
				
				$extension = $extensions[$extension_key];
			}
			
			foreach($extension as $key => $value){
				$key = preg_replace('#^extension\_#si', '', $key, 1);
				$this->$key = $value;
			}
			
			$this->uid = strval($this->uid);
			$this->file = parent::correctWinPath(PATH . EXTENSIONS . $this->folder . '/' . $this->file . '.php');
			if(!is_array($this->hooks)){
				$this->hooks = unserialize($this->hooks);
			}
			$this->preferences = unserialize($this->preferences);
		}
		return true;
	}
	
	/**
	 * Terminates object, saves extension data
	 *
	 * @return void
	 */
	public function __destruct(){
		// Save extension data
		$_SESSION['alkaline']['extensions'] = $this->extensions;
		
		parent::__destruct();
	}
	
	/**
	 * Set extension preference
	 *
	 * @param string $name 
	 * @param string $value 
	 * @return void
	 */
	public function setPref($name, $value){
		return $this->preferences[$name] = $value;
	}
	
	/**
	 * Read preference key and return value
	 *
	 * @param string $name 
	 * @param string $default 
	 * @return void
	 */
	public function returnPref($name, $default=null){
		return parent::returnForm($this->preferences, $name, $default);
	}
	
	/**
	 * Read preference key, return form data
	 *
	 * @param string $name 
	 * @param string $check 
	 * @return void
	 */
	public function readPref($name, $check=true){
		return parent::readForm($this->preferences, $name, $check);
	}
	
	/**
	 * Save preferences to database
	 *
	 * @return void
	 */
	public function savePref(){
		$query = $this->prepare('UPDATE extensions SET extension_preferences = :extension_preferences WHERE extension_uid = :extension_uid;');
		return $query->execute(array(':extension_preferences' => serialize($this->preferences), ':extension_uid' => $this->uid));
	}
	
	/**
	 * Reset preferences
	 *
	 * @return PDOStatement
	 */
	public function reset(){
		$query = $this->prepare('UPDATE extensions SET extension_preferences = "" WHERE extension_uid = :extension_uid;');
		return $this->execute(array(':extension_uid' => $this->uid));
	}
	
	/**
	 * Store task for consecutive execution
	 *
	 * @param callback $callback 
	 * @return void
	 */
	public function storeTask($callback){
		if(is_array($callback)){
			list($class, $method) = $callback;
			if(is_object($class)){
				$class = get_class($class);
				$callback = array($class, $method);
			}
		}
		else{
			return false;
		}
		
		// Find arguments
		$arguments = func_get_args();
		$arguments = array_slice($arguments, 1);
		
		if(!isset($_SESSION['alkaline']['tasks'])){
			$_SESSION['alkaline']['tasks'] = 1;
		}
		
		++$_SESSION['alkaline']['tasks'];
		
		if(!file_exists(PATH . CACHE . 'tasks/')){
			@mkdir(PATH . CACHE . 'tasks/', 0777, true);
		}
		
		$contents = array($callback, $arguments);
		
		file_put_contents(PATH . CACHE . 'tasks/' . md5(DB_DSN . PATH . $_SESSION['alkaline']['tasks']), serialize($contents));
	}
	
	/**
	 * Execute stored task
	 *
	 * @param int $id Task ID
	 * @return bool True if successful
	 */
	public function executeTask($id){
		$path = PATH . CACHE . 'tasks/' . md5(DB_DSN . PATH . $id);
		if(file_exists($path)){
			$contents = file_get_contents($path, false);
			@unlink($path);
		}
		else{
			$contents = false;
		}
		
		if($contents === false){
			if($id == $_SESSION['alkaline']['tasks']){
				unset($_SESSION['alkaline']['tasks']);
			}
			return false;
		}
		
		list($callback, $arguments) = unserialize($contents);
		list($class, $method) = $callback;
		
		if(!empty($this->extensions)){
			foreach($this->extensions as $extension){
				if($extension['extension_class'] == $class){
					require_once($extension['extension_file']);
					if(method_exists($class, $method)){
						$orbit = new $class();
						$return = call_user_func_array(array($orbit, $method), $arguments);
					}
				}
			}
		}
		
		if($return === false){
			file_put_contents($path, $contents);
			return false;
		}
		
		if($id == $_SESSION['alkaline']['tasks']){
			unset($_SESSION['alkaline']['tasks']);
		}
		
		return true;
	}
	
	/**
	 * Prompt JavaScript initiation of tasks
	 *
	 * @return void
	 */
	public static function promptTasks(){
		$tasks = array();
		
		if(empty($_SESSION['alkaline']['tasks'])){ return; }
		
		$count = $_SESSION['alkaline']['tasks'];
		
		for($i=1; $i <= $count; $i++){
			$tasks[] = $i;
		}
		
		return '<div id="alkaline_tasks" class="none">' . json_encode($tasks) . '</div>';
	}
	
	/**
	 * Execute Orbit hook
	 *
	 * @param string $hook Hook name
	 * @return mixed Default value
	 */
	public function hook($hook){
		// Find arguments
		$arguments = func_get_args();
		
		// Find pass-by-default value
		$argument_count = count($arguments);
		if($argument_count > 1){
			$argument_pass_index = $argument_count - 1;
			$argument_pass = $arguments[$argument_pass_index];
		}
		else{
			$argument_pass = false;
		}
		
		// Configuration: maint_disable
		$safe_hooks = array('config', 'config_load', 'config_save');
		if(!in_array($hook, $safe_hooks)){
			if($this->returnConf('maint_disable')){
				return $argument_pass;
			}
		}
		
		// Remove hook name
		$arguments = array_slice($arguments, 1, count($arguments) - 2);
		// Determine variable type
		if(isset($arguments[0])){
			$argument_return_type = $this->getType($arguments[0]);
		}
		
		// Find respective extensions, execute their code
		if(!empty($this->extensions)){
			foreach($this->extensions as $extension){
				if(@in_array($hook, $extension['extension_hooks'])){
					require_once($extension['extension_file']);
					$orbit = new $extension['extension_class']();
					$method = 'orbit_' . $hook;
					if(method_exists($orbit, $method)){
						// Do method
						$return = call_user_func_array(array($orbit, $method), $arguments);
						// If variable type is the same, pass it along to future extensions
						if(!empty($argument_return_type) and ($this->getType($return) == $argument_return_type)){
							$arguments = array_merge(array($return), array_splice($arguments, 1));
						}
					}
				}
			}
		}
		
		if(isset($arguments[0])){
			return $arguments[0];
		}
	}
}

?>
