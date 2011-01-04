<?php

/*
// Alkaline
// Copyright (c) 2010-2011 by Budin Ltd. All rights reserved.
// Do not redistribute this code without written permission from Budin Ltd.
// http://www.alkalinenapp.com/
*/

function __autoload($class){
	$file = strtolower($class) . '.php';
	require_once(PATH . CLASSES . $file);
}

class Alkaline{
	const build = 605;
	const copyright = 'Powered by <a href="http://www.alkalineapp.com/">Alkaline</a>. Copyright &copy; 2010-2011 by <a href="http://www.budinltd.com/">Budin Ltd.</a> All rights reserved.';
	const edition = 'standard';
	const licensee = 'Jacob Budin, Budin Ltd.';
	const product = 'Alkaline';
	const version = '1.0';
	
	public $db_type;
	public $db_version;
	public $tables;
	
	protected $db;
	protected $notifications;
	
	public function __construct(){
		@header('Cache-Control: no-cache, must-revalidate');
		@header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		
		// Determine class
		$class = get_class($this);
		
		// Begin a session, if one does not yet exist
		if(session_id() == ''){ session_start(); }
		
		// Debug info
		if(get_class($this) == 'Alkaline'){
			$_SESSION['alkaline']['debug']['start_time'] = microtime(true);
			$_SESSION['alkaline']['debug']['queries'] = 0;
			$_SESSION['alkaline']['config'] = json_decode(@file_get_contents($this->correctWinPath(PATH . 'config.json')), true);
			
			if(empty($_SESSION['alkaline']['config'])){
				$_SESSION['alkaline']['config'] = array();
			}
			
			if($timezone = $this->returnConf('web_timezone')){
				date_default_timezone_set($timezone);
			}
			else{
				date_default_timezone_set('GMT');
			}
		}
		
		// Write tables
		$this->tables = array('photos' => 'photo_id', 'tags' => 'tag_id', 'comments' => 'comment_id', 'piles' => 'pile_id', 'pages' => 'page_id', 'rights' => 'right_id', 'exifs' => 'exif_id', 'extensions' => 'extension_id', 'themes' => 'theme_id', 'sizes' => 'size_id', 'users' => 'user_id', 'guests' => 'guest_id');
		
		// Set back link
		if(!empty($_SERVER['HTTP_REFERER']) and ($_SERVER['HTTP_REFERER'] != LOCATION . $_SERVER['REQUEST_URI'])){
			$_SESSION['alkaline']['back'] = $_SERVER['HTTP_REFERER'];
		} 
		
		// Initiate database connection, if necessary
		$no_db_classes = array('Canvas');
		
		if(!in_array($class, $no_db_classes)){
			if(defined('DB_TYPE') and defined('DB_DSN')){
				// Determine database type
				$this->db_type = DB_TYPE;
			
				if($this->db_type == 'mssql'){
					// $this->db = new PDO(DB_DSN);
				}
				elseif($this->db_type == 'mysql'){
					$this->db = new PDO(DB_DSN, DB_USER, DB_PASS, array(PDO::ATTR_PERSISTENT => true, PDO::FETCH_ASSOC => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT));
				}
				elseif($this->db_type == 'pgsql'){
					$this->db = new PDO(DB_DSN, DB_USER, DB_PASS);
					$this->db->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
				}
				elseif($this->db_type == 'sqlite'){
					$this->db = new PDO(DB_DSN, null, null, array(PDO::ATTR_PERSISTENT => true, PDO::FETCH_ASSOC => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT));
				
					$this->db->sqliteCreateFunction('ACOS', 'acos', 1);
					$this->db->sqliteCreateFunction('COS', 'cos', 1);
					$this->db->sqliteCreateFunction('RADIANS', 'deg2rad', 1);
					$this->db->sqliteCreateFunction('SIN', 'sin', 1);
				}
				
				$this->db_version = $this->db->getAttribute(PDO::ATTR_SERVER_VERSION);
			}
		}
		
		// Delete saved Orbit extension session references
		if($class == 'Alkaline'){
			unset($_SESSION['alkaline']['extensions']);
		}
	}
	
	public function __destruct(){
		// Close database connection
		$this->db = null;
	}
	
	// DATABASE
	public function exec($query){
		if(!$this->db){ $this->error('No database connection.'); }
		
		$this->prequery($query);
		$response = $this->db->exec($query);
		$this->postquery($query);
		
		return $response;
	}
	
	public function prepare($query){
		if(!$this->db){ $this->error('No database connection.'); }
		
		$this->prequery($query);
		$response = $this->db->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		$this->postquery($query);
		
		if(!$response){ $this->error('Invalid query, check database connection.'); }
		
		return $response;
	}
	
	public function prequery(&$query){
		$_SESSION['alkaline']['debug']['queries']++;
		
		if(TABLE_PREFIX != ''){
			// Add table prefix
			$query = preg_replace('#(FROM|JOIN)\s+([\sa-z0-9_\-,]*)\s*(WHERE|GROUP|HAVING|ORDER)?#se', "'\\1 '.Alkaline::appendTablePrefix('\\2').' \\3'", $query);
			$query = preg_replace('#([a-z]+[a-z0-9-\_]*)\.#si', TABLE_PREFIX . '\\1.', $query);
			$query = preg_replace('#(INSERT INTO|UPDATE)\s+(\w+)#si', '\\1 ' . TABLE_PREFIX . '\\2', $query);
		}
		
		if($this->db_type == 'mssql'){
			/*
			preg_match('#GROUP BY (.*) ORDER BY#si', $query, $match);
			$find = @$match[0];
			if(!empty($find)){
				$replace = $find;
				$replace = str_replace('stat_day', 'DAY(stat_date)', $replace);
				$replace = str_replace('stat_month', 'MONTH(stat_date)', $replace);
				$replace = str_replace('stat_year', 'YEAR(stat_date)', $replace);
				$query = str_replace($find, $replace, $query);
			}
			
			if(preg_match('#SELECT (?:.*) LIMIT[[:space:]]+([0-9]+),[[:space:]]*([0-9]+)#si', $query, $match)){
				$query = preg_replace('#LIMIT[[:space:]]+([0-9]+),[[:space:]]*([0-9]+)#si', '', $query);
				$offset = @$match[1];
				$limit = @$match[2];
				preg_match('#FROM (.+?)(?:\s|,)#si', $query, $match);
				$table = @$match[1];
				$query = str_replace('SELECT ', 'SELECT TOP 999999999999999999 ROW_NUMBER() OVER (ORDER BY ' . $this->tables[$table]  . ' ASC) AS row_number,', $query);
				$query = 'SELECT * FROM (' . $query . ') AS temp WHERE temp.row_number > ' . $offset . ' AND temp.row_number <= ' . ($offset + $limit);
			}
			*/
		}
		elseif($this->db_type == 'pgsql'){
			$query = preg_replace('#LIMIT[[:space:]]+([0-9]+),[[:space:]]*([0-9]+)#si', 'LIMIT \2 OFFSET \1', $query);
			$query = str_replace('HOUR(', 'EXTRACT(HOUR FROM ', $query);
			$query = str_replace('DAY(', 'EXTRACT(DAY FROM ', $query);
			$query = str_replace('MONTH(', 'EXTRACT(MONTH FROM ', $query);
			$query = str_replace('YEAR(', 'EXTRACT(YEAR FROM ', $query);
		}
		elseif($this->db_type == 'sqlite'){
			$query = str_replace('HOUR(', 'strftime("%H",', $query);
			$query = str_replace('DAY(', 'strftime("%d",', $query);
			$query = str_replace('MONTH(', 'strftime("%m",', $query);
			$query = str_replace('YEAR(', 'strftime("%Y",', $query);
		}
		
		$query = trim($query);
	}
	
	protected function appendTablePrefix($tables){
		if(strpos($tables, ',') === false){
			$tables = trim($tables);
			$tables = TABLE_PREFIX . $tables;
		}
		else{
			$tables = explode(',', $tables);
			$tables = array_map('trim', $tables);
			foreach($tables as &$table){
				$table = TABLE_PREFIX . $table;
			}
			$tables = implode(', ', $tables);
		}
		return $tables;
	}
	
	public function postquery(&$query, $db=null){
		if(empty($db)){ $db = $this->db; }
		
		$error = $db->errorInfo();
		
		if(isset($error[2])){
			$code = $error[0];
			$message = $query . ' ' . ucfirst(preg_replace('#^Error\:[[:space:]]+#si', '', $error[2])) . ' (' . $code . ').';
			
			if(substr($code, 0, 2) == '00'){
				// $this->report($message, $code);
			}
			elseif($code == '23000'){
				$this->report($message, $code);
			}
			else{
				$this->report($message, $code);
			}
		}
	}
	
	// REMOVE NULL FROM JSON
	public function removeNull($input){
		return str_replace(':null', ':""', $input);
	}
	
	// BOOMERANG
	// Receive updates from alkalineapp.com
	public function boomerang($request){
		$reply = self::removeNull(json_decode(file_get_contents('http://www.alkalineapp.com/boomerang/' . $request . '/'), true));
		return $reply;
	}	
	
	// GUESTS
	// Authenticate guest
	public function access($key=null){
		// Error checking
		if(empty($key)){ return false; }
		
		$key = strip_tags($key);
		
		$query = $this->prepare('SELECT * FROM guests WHERE guest_key = :guest_key LIMIT 0, 1;');
		$query->execute(array(':guest_key' => $key));
		$guests = $query->fetchAll();
		$guest = $guests[0];
		
		if(!$guest){
			$this->error('You are not authorized.');
		}
		
		$_SESSION['alkaline']['guest'] = $guest;
		
		return true;
	}
	
	// NOTIFICATIONS
	// Add notification
	public function addNotification($message, $type=null){
		$_SESSION['alkaline']['notifications'][] = array('type' => $type, 'message' => $message);
		return true;
	}
	
	// Check notifications
	public function isNotification($type=null){
		if(!empty($type)){
			$notifications = @$_SESSION['alkaline']['notifications'];
			$count = @count($notifications);
			if($count > 0){
				$count = 0;
				foreach($notifications as $notification){
					if($notification['type'] == $type){
						$count++;
					}
				}
				if($count > 0){
					return $count;
				}
			}			
		}
		else{
			$count = @count($_SESSION['alkaline']['notifications']);
			if($count > 0){
				return $count;
			}
		}
		return false;
	}
	
	// View notification
	public function viewNotification($type=null){
		$count = @count($_SESSION['alkaline']['notifications']);
		
		if($count > 0){
			// Determine unique types
			$types = array();
			foreach($_SESSION['alkaline']['notifications'] as $notifications){
				$types[] = $notifications['type'];
			}
			$types = array_unique($types);
			
			// Produce HTML for display
			foreach($types as $type){
				echo '<p class="' . $type . '">';
				$messages = array();
				foreach($_SESSION['alkaline']['notifications'] as $notification){
					if($notification['type'] == $type){
						$messages[] = $notification['message'];
					}
				}
				echo implode(' ', $messages) . '</p>';
			}
			
			echo '<br />';

			// Dispose of messages
			unset($_SESSION['alkaline']['notifications']);
			
			return $count;
		}
		else{
			return false;
		}
	}
	
	// FILE HANDLING
	// Seek directory
	public function seekDirectory($dir=null, $ext=IMG_EXT){
		// Error checking
		if(empty($dir)){
			return false;
		}
		
		// Windows-friendly
		$dir = $this->correctWinPath($dir);
		
		$files = array();
		$ignore = array('.', '..');
		
		// Open listing
		$handle = opendir($dir);
		
		// Seek directory
		while($filename = readdir($handle)){
			if(!in_array($filename, $ignore)){ 
				// Recusively check directories
				/*
				if(is_dir($dir . '/' . $filename)){
					self::seekDirectory($dir . $filename . '/', $files);
				}
				*/
				
				if(!empty($ext)){
					// Find files with proper extensions
					if(preg_match('#([a-zA-Z0-9\-\_]+\.(' . $ext . '){1,1})#si', $filename)){
						$files[] = $dir . $filename;
					}
				}
				else{
					$files[] = $dir . $filename;
				}
			}
	    }
	
		// Close listing
		closedir($handle);
		
		return $files;
	}
	
	// Count compatible photos in shoebox
	public function countDirectory($dir=null){
		// Error checking
		if(empty($dir)){
			return false;
		}
		
		$files = self::seekDirectory($dir);
		$count = count($files);
		
		return $count;
	}
	
	// Get filename
	public function getFilename($file){
		$matches = array();
		
		// Windows cheat
		$file = str_replace('\\', '/', $file);
		
		preg_match('#^(.*/)?(?:$|(.+?)(?:(\.[^.]*$)|$))#si', $file, $matches);
		if(count($matches) < 1){
			return false;
		}
		$filename = $matches[2] . @$matches[3];
		return $filename;
	}
	
	// Empty directory
	public function emptyDirectory($dir=null){
		// Error checking
		if(empty($dir)){
			return false;
		}
		
		$ignore = array('.', '..');
		
		// Open listing
		$handle = opendir($dir);
		
		// Seek directory
		while($filename = readdir($handle)){
			if(!in_array($filename, $ignore)){
				// Delete directories
				if(is_dir($dir . '/' . $filename)){
					self::emptyDirectory($dir . $filename . '/');
					@rmdir($dir . $filename . '/');
				}
				// Delete files
				else{
					chmod($dir . $filename, 0777);
					unlink($dir . $filename);
				}
			}
	    }
	
		// Close listing
		closedir($handle);
		
		return true;
	}
	
	// Check permissions
	public function checkPerm($file){
		return substr(sprintf('%o', @fileperms($file)), -4);
	}
	
	// Replace variable
	public function replaceVar($var, $replacement, $subject){
		return preg_replace('#^\s*' . str_replace('$', '\$', $var) . '\s*=(.*)$#mi', $replacement, $subject);
	}
	
	// CONVERT TO ARRAY
	// Convert a possible string or integer into an array
	public function convertToArray(&$input){
		if(is_string($input)){
			$find = strpos($input, ',');
			if($find === false){
				$input = array($input);
			}
			else{
				$input = explode(',', $input);
				$input = array_map('trim', $input);
			}
		}
		elseif(is_int($input)){
			$input = array($input);
		}
		return $input;
	}
	
	// CONVERT TO INTEGER ARRAY
	// Convert a possible string or integer into an array of integers
	public function convertToIntegerArray(&$input){
		if(is_int($input)){
			$input = array($input);
		}
		elseif(is_string($input)){
			$find = strpos($input, ',');
			if($find === false){
				$input = array(intval($input));
			}
			else{
				$input = explode(',', $input);
				$input = array_map('trim', $input);
			}
		}
		return $input;
	}
	
	// CONVERT INTEGER-LIKE STRINGS TO INTEGERS
	// Convert a possible string or integer into an array of integers
	public function makeStringInt(&$input){
		if(!is_string($input)){
			break;
		}
		if(preg_match('#^[0-9]+$#s', $input)){
			$input = intval($input);
		}
		return $input;
	}
	
	public function changeExt($file, $ext){
		$file = preg_replace('#\.([a-z0-9]*)$#si', '.' . $ext, $file);
		return $file;
	}
	
	// FORMAT TIME
	// Make time more human-readable
	public function formatTime($time, $format=null, $empty=false){
		// Error checking
		if(empty($time) or ($time == '0000-00-00 00:00:00')){
			return $empty;
		}
		if(empty($format)){
			$format = DATE_FORMAT;
		}
		
		$time = str_replace('tonight', 'today', $time);
		$time = @strtotime($time);
		$time = date($format, $time);
		
		$ampm = array(' am', ' pm');
		$ampm_correct = array(' a.m.', ' p.m.');
		
		$time = str_replace($ampm, $ampm_correct, $time);
		
		return $time;
	}
	
	// Turn time into relative
	public function formatRelTime($time, $format=null, $empty=false){
		// Error checking
		if(empty($time) or ($time == '0000-00-00 00:00:00')){
			return $empty;
		}
		if(empty($format)){
			$format = DATE_FORMAT;
		}
		
		$time = @strtotime($time);
		$seconds = time() - $time;
		
		switch($seconds){
			case($seconds < 3600):
				$minutes = intval($seconds / 60);
				if($minutes < 2){ $span = 'a minute ago'; }
				else{ $span = $minutes . ' minutes ago'; }
				break;
			case($seconds < 86400):
				$hours = intval($seconds / 3600);
				if($hours < 2){ $span = 'an hour ago'; }
				else{ $span = $hours . ' hours ago'; }
				break;
			case($seconds < 2419200):
				$days = intval($seconds / 86400);
				if($days < 2){ $span = 'yesterday'; }
				else{ $span = $days . ' days ago'; }
				break;
			case($seconds < 29030400):
				$months = intval($seconds / 2419200);
				if($months < 2){ $span = 'a month ago'; }
				else{ $span = $months . ' months ago'; }
				break;
			default:
				$span = date($format, $time);
				break;
		}
		return $span;
	}
	
	public function numberToMonth($num){
		$int = intval($num);
		switch($int){
			case 1:
				return 'January';
				break;
			case 2:
				return 'February';
				break;
			case 3:
				return 'March';
				break;
			case 4:
				return 'April';
				break;
			case 5:
				return 'May';
				break;
			case 6:
				return 'June';
				break;
			case 7:
				return 'July';
				break;
			case 8:
				return 'August';
				break;
			case 9:
				return 'September';
				break;
			case 10:
				return 'October';
				break;
			case 11:
				return 'November';
				break;
			case 12:
				return 'December';
				break;	
		}
	}
	
	// PEAR Numbers_Words
	public function numberToWords($num, $power = 0, $powsuffix = ''){
		$_minus = 'minus'; // minus sign

	    /**
	     * The sufixes for exponents (singular and plural)
	     * Names partly based on:
	     * http://home.earthlink.net/~mrob/pub/math/largenum.html
	     * http://mathforum.org/dr.math/faq/faq.large.numbers.html
	     * http://www.mazes.com/AmericanNumberingSystem.html
	     * @array
	     * @access private
	     */
	    $_exponent = array(
	        0 => array(''),
	        3 => array('thousand'),
	        6 => array('million'),
	        9 => array('billion'),
	       12 => array('trillion'),
	       15 => array('quadrillion'),
	       18 => array('quintillion'),
	       21 => array('sextillion'),
	       24 => array('septillion'),
	       27 => array('octillion'),
	       30 => array('nonillion'),
	       33 => array('decillion'),
	       36 => array('undecillion'),
	       39 => array('duodecillion'),
	       42 => array('tredecillion'),
	       45 => array('quattuordecillion'),
	       48 => array('quindecillion'),
	       51 => array('sexdecillion'),
	       54 => array('septendecillion'),
	       57 => array('octodecillion'),
	       60 => array('novemdecillion'),
	       63 => array('vigintillion'),
	       66 => array('unvigintillion'),
	       69 => array('duovigintillion'),
	       72 => array('trevigintillion'),
	       75 => array('quattuorvigintillion'),
	       78 => array('quinvigintillion'),
	       81 => array('sexvigintillion'),
	       84 => array('septenvigintillion'),
	       87 => array('octovigintillion'),
	       90 => array('novemvigintillion'),
	       93 => array('trigintillion'),
	       96 => array('untrigintillion'),
	       99 => array('duotrigintillion'),
	       // 100 => array('googol') - not latin name
	       // 10^googol = 1 googolplex
	      102 => array('trestrigintillion'),
	      105 => array('quattuortrigintillion'),
	      108 => array('quintrigintillion'),
	      111 => array('sextrigintillion'),
	      114 => array('septentrigintillion'),
	      117 => array('octotrigintillion'),
	      120 => array('novemtrigintillion'),
	      123 => array('quadragintillion'),
	      126 => array('unquadragintillion'),
	      129 => array('duoquadragintillion'),
	      132 => array('trequadragintillion'),
	      135 => array('quattuorquadragintillion'),
	      138 => array('quinquadragintillion'),
	      141 => array('sexquadragintillion'),
	      144 => array('septenquadragintillion'),
	      147 => array('octoquadragintillion'),
	      150 => array('novemquadragintillion'),
	      153 => array('quinquagintillion'),
	      156 => array('unquinquagintillion'),
	      159 => array('duoquinquagintillion'),
	      162 => array('trequinquagintillion'),
	      165 => array('quattuorquinquagintillion'),
	      168 => array('quinquinquagintillion'),
	      171 => array('sexquinquagintillion'),
	      174 => array('septenquinquagintillion'),
	      177 => array('octoquinquagintillion'),
	      180 => array('novemquinquagintillion'),
	      183 => array('sexagintillion'),
	      186 => array('unsexagintillion'),
	      189 => array('duosexagintillion'),
	      192 => array('tresexagintillion'),
	      195 => array('quattuorsexagintillion'),
	      198 => array('quinsexagintillion'),
	      201 => array('sexsexagintillion'),
	      204 => array('septensexagintillion'),
	      207 => array('octosexagintillion'),
	      210 => array('novemsexagintillion'),
	      213 => array('septuagintillion'),
	      216 => array('unseptuagintillion'),
	      219 => array('duoseptuagintillion'),
	      222 => array('treseptuagintillion'),
	      225 => array('quattuorseptuagintillion'),
	      228 => array('quinseptuagintillion'),
	      231 => array('sexseptuagintillion'),
	      234 => array('septenseptuagintillion'),
	      237 => array('octoseptuagintillion'),
	      240 => array('novemseptuagintillion'),
	      243 => array('octogintillion'),
	      246 => array('unoctogintillion'),
	      249 => array('duooctogintillion'),
	      252 => array('treoctogintillion'),
	      255 => array('quattuoroctogintillion'),
	      258 => array('quinoctogintillion'),
	      261 => array('sexoctogintillion'),
	      264 => array('septoctogintillion'),
	      267 => array('octooctogintillion'),
	      270 => array('novemoctogintillion'),
	      273 => array('nonagintillion'),
	      276 => array('unnonagintillion'),
	      279 => array('duononagintillion'),
	      282 => array('trenonagintillion'),
	      285 => array('quattuornonagintillion'),
	      288 => array('quinnonagintillion'),
	      291 => array('sexnonagintillion'),
	      294 => array('septennonagintillion'),
	      297 => array('octononagintillion'),
	      300 => array('novemnonagintillion'),
	      303 => array('centillion'),
	      309 => array('duocentillion'),
	      312 => array('trecentillion'),
	      366 => array('primo-vigesimo-centillion'),
	      402 => array('trestrigintacentillion'),
	      603 => array('ducentillion'),
	      624 => array('septenducentillion'),
	     // bug on a earthlink page: 903 => array('trecentillion'),
	     2421 => array('sexoctingentillion'),
	     3003 => array('millillion'),
	     3000003 => array('milli-millillion')
	        );

	    /**
	     * The array containing the digits (indexed by the digits themselves).
	     * @array
	     * @access private
	     */
	    $_digits = array(
	        0 => 'zero', 'one', 'two', 'three', 'four',
	        'five', 'six', 'seven', 'eight', 'nine'
	    );

	    /**
	     * The word separator
	     * @string
	     * @access private
	     */
	    $_sep = ' ';
	
        $ret = '';

        // add a minus sign
        if (substr($num, 0, 1) == '-') {
            $ret = $_sep . $_minus;
            $num = substr($num, 1);
        }

        // strip excessive zero signs and spaces
        $num = trim($num);
        $num = preg_replace('/^0+/', '', $num);

        if (strlen($num) > 3) {
            $maxp = strlen($num)-1;
            $curp = $maxp;
            for ($p = $maxp; $p > 0; --$p) { // power

                // check for highest power
                if (isset($_exponent[$p])) {
                    // send substr from $curp to $p
                    $snum = substr($num, $maxp - $curp, $curp - $p + 1);
                    $snum = preg_replace('/^0+/', '', $snum);
                    if ($snum !== '') {
                        $cursuffix = $_exponent[$power][count($_exponent[$power])-1];
                        if ($powsuffix != '') {
                            $cursuffix .= $_sep . $powsuffix;
                        }

                        $ret .= $this->toWords($snum, $p, $cursuffix);
                    }
                    $curp = $p - 1;
                    continue;
                }
            }
            $num = substr($num, $maxp - $curp, $curp - $p + 1);
            if ($num == 0) {
                return $ret;
            }
        } elseif ($num == 0 || $num == '') {
            return $_sep . $_digits[0];
        }

        $h = $t = $d = 0;

        switch(strlen($num)) {
        case 3:
            $h = (int)substr($num, -3, 1);

        case 2:
            $t = (int)substr($num, -2, 1);

        case 1:
            $d = (int)substr($num, -1, 1);
            break;

        case 0:
            return;
            break;
        }

        if ($h) {
            $ret .= $_sep . $_digits[$h] . $_sep . 'hundred';

            // in English only - add ' and' for [1-9]01..[1-9]99
            // (also for 1001..1099, 10001..10099 but it is harder)
            // for now it is switched off, maybe some language purists
            // can force me to enable it, or to remove it completely
            // if (($t + $d) > 0)
            //   $ret .= $_sep . 'and';
        }

        // ten, twenty etc.
        switch ($t) {
        case 9:
        case 7:
        case 6:
            $ret .= $_sep . $_digits[$t] . 'ty';
            break;

        case 8:
            $ret .= $_sep . 'eighty';
            break;

        case 5:
            $ret .= $_sep . 'fifty';
            break;

        case 4:
            $ret .= $_sep . 'forty';
            break;

        case 3:
            $ret .= $_sep . 'thirty';
            break;

        case 2:
            $ret .= $_sep . 'twenty';
            break;

        case 1:
            switch ($d) {
            case 0:
                $ret .= $_sep . 'ten';
                break;

            case 1:
                $ret .= $_sep . 'eleven';
                break;

            case 2:
                $ret .= $_sep . 'twelve';
                break;

            case 3:
                $ret .= $_sep . 'thirteen';
                break;

            case 4:
            case 6:
            case 7:
            case 9:
                $ret .= $_sep . $_digits[$d] . 'teen';
                break;

            case 5:
                $ret .= $_sep . 'fifteen';
                break;

            case 8:
                $ret .= $_sep . 'eighteen';
                break;
            }
            break;
        }

        if ($t != 1 && $d > 0) { // add digits only in <0>,<1,9> and <21,inf>
            // add minus sign between [2-9] and digit
            if ($t > 1) {
                $ret .= '-' . $_digits[$d];
            } else {
                $ret .= $_sep . $_digits[$d];
            }
        }

        if ($power > 0) {
            if (isset($_exponent[$power])) {
                $lev = $_exponent[$power];
            }

            if (!isset($lev) || !is_array($lev)) {
                return null;
            }

            $ret .= $_sep . $lev[0];
        }

        if ($powsuffix != '') {
            $ret .= $_sep . $powsuffix;
        }

        return $ret;
    }
	
	// FORMAT STRINGS
	// Convert to Unicode (UTF-8)
	public function makeUnicode($string){
		return mb_detect_encoding($string, 'UTF-8') == 'UTF-8' ? $string : utf8_encode($string);
	}
	
	// Sanitize table, column names, other data
	public function sanitize($string){
		return preg_replace('#(?:(?![a-z0-9_\.-\s]).)*#si', '', $string);
	}
	
	// Make HTML-safe quotations
	public function makeHTMLSafe($input){
		if(is_string($input)){
			$input = self::makeHTMLSafeHelper($input);
		}
		if(is_array($input)){
			foreach($input as &$value){
				$value = self::makeHTMLSafe($value);
			}
		}
		
		return $input;
	}
	
	private function makeHTMLSafeHelper($string){
		$string = preg_replace('#\'#s', '&#0039;', $string);	
		$string = preg_replace('#\"#s', '&#0034;', $string);
		return $string;
	}
	
	// Reverse HTML-safe quotations
	public function reverseHTMLSafe($input){
		if(is_string($input)){
			$input = self::reverseHTMLSafeHelper($input);
		}
		if(is_array($input)){
			foreach($input as &$value){
				$value = self::reverseHTMLSafe($value);
			}
		}
		
		return $input;
	}
	
	private function reverseHTMLSafeHelper($string){
		$string = preg_replace('#\&\#0039\;#s', '\'', $string);	
		$string = preg_replace('#\&\#0034\;#s', '"', $string);
		return $string;
	}
	
	public function stripTags($var){
		if(is_string($var)){
			$var = strip_tags($var);
		}
		elseif(is_array($var)){
			foreach($var as $key => $value){
				$var[$key] = self::stripTags($value);
			}
		}
		return $var;
	}
	
	public function countWords($str){
		$str = strip_tags($str);
		preg_match_all("/\S+/", $str, $matches); 
	    return count($matches[0]);
	}
	
	// SHOW TAGS
	// Display all tags
	public function getTags(){
		if($this->returnConf('tag_alpha')){
			$query = $this->prepare('SELECT tags.tag_name, tags.tag_id, photos.photo_id FROM tags, links, photos WHERE tags.tag_id = links.tag_id AND links.photo_id = photos.photo_id ORDER BY tags.tag_name;');
		}
		else{
			$query = $this->prepare('SELECT tags.tag_name, tags.tag_id, photos.photo_id FROM tags, links, photos WHERE tags.tag_id = links.tag_id AND links.photo_id = photos.photo_id ORDER BY tags.tag_id ASC;');
		}
		$query->execute();
		$tags = $query->fetchAll();
		
		$tag_ids = array();
		$tag_names = array();
		$tag_counts = array();
		$tag_uniques = array();
		
		foreach($tags as $tag){
			$tag_names[] = $tag['tag_name'];
			$tag_ids[$tag['tag_name']] = $tag['tag_id'];
		}
		
		$tag_counts = array_count_values($tag_names);
		$tag_count_values = array_values($tag_counts);
		$tag_count_high = 0;
		
		foreach($tag_count_values as $value){
			if($value > $tag_count_high){
				$tag_count_high = $value;
			}
		}
		
		$tag_uniques = array_unique($tag_names);
		$tags = array();
		
		foreach($tag_uniques as $tag){
			$tags[] = array('id' => $tag_ids[$tag],
				'size' => round(((($tag_counts[$tag] - 1) * 3) / $tag_count_high) + 1, 2),
				'name' => $tag,
				'count' => $tag_counts[$tag]);
		}
		
		return $tags;
	}
	
	// Gather all includes
	public function getIncludes(){
		$includes = self::seekDirectory(PATH . INCLUDES, '.*');
		
		foreach($includes as &$include){
			$include = self::getFilename($include);
		}
		
		return $includes;
	}
	
	// PROCESS COMMENTS
	public function addComments(){
		// Configuration: comm_enabled
		if(!$this->returnConf('comm_enabled')){
			return false;
		}
		
		if(empty($_POST['comment_id'])){
			return false;
		}
		
		$id = self::findID($_POST['comment_id']);
		
		// Configuration: comm_mod
		if($this->returnConf('comm_mod')){
			$comment_status = 0;
		}
		else{
			$comment_status = 1;
		}
		
		$comment_text_raw = $this->makeUnicode(strip_tags($_POST['comment_' . $id .'_text']));
		
		$orbit = new Orbit;
		
		// Configuration: comm_markup
		if($this->returnConf('comm_markup')){
			$comm_markup_ext = $this->returnConf('comm_markup_ext');
			$comment_text = $orbit->hook('markup_' . $comm_markup_ext, $comment_text_raw, null);
		}
		
		if(!isset($comment_text)){
			$comm_markup_ext = '';
			$comment_text = nl2br($comment_text_raw);
		}
		
		$fields = array('photo_id' => $id,
			'comment_status' => $comment_status,
			'comment_text' => $comment_text,
			'comment_text_raw' => $comment_text_raw,
			'comment_markup' => $comm_markup_ext,
			'comment_author_name' => $comment_text,
			'comment_author_uri' => strip_tags($_POST['comment_' . $id .'_author_uri']),
			'comment_author_email' => strip_tags($_POST['comment_' . $id .'_author_email']),
			'comment_author_ip' => $_SERVER['REMOTE_ADDR']);
		
		$fields = $orbit->hook('comment_add', $fields, $fields);
		
		if(!$this->addRow($fields, 'comments')){
			return false;
		}
		
		if($this->returnConf('comm_email')){
			$this->email(0, 'New comment', 'A new comment has been submitted:' . "\r\n\n" . $comment_text);
		}
		
		$this->updateCount('comments', 'photos', 'photo_comment_count', $id);
		
		return true;
	}
	
	public function updateCount($count_table, $result_table, $result_field, $result_id){
		$result_id = intval($result_id);
		
		$count_table = $this->sanitize($count_table);
		$result_table = $this->sanitize($result_table);
		
		$count_id_field = $this->tables[$count_table];
		$result_id_field = $this->tables[$result_table];
		
		// Get count
		$query = $this->prepare('SELECT COUNT(' . $count_id_field . ') AS count FROM ' . $count_table . ' WHERE ' . $result_id_field  . ' = :result_id;');
		
		if(!$query->execute(array(':result_id' => $result_id))){
			return false;
		}
		
		$counts = $query->fetchAll();
		$count = $counts[0]['count'];
		
		// Update row
		$query = $this->prepare('UPDATE ' . $result_table . ' SET ' . $result_field . ' = :count WHERE ' . $result_id_field . ' = :result_id;');
		
		if(!$query->execute(array(':count' => $count, ':result_id' => $result_id))){
			return false;
		}
		
		return true;
	}
	
	public function updateCounts($count_table, $result_table, $result_field){
		$count_table = $this->sanitize($count_table);
		$result_table = $this->sanitize($result_table);
		
		$count_id_field = $this->tables[$count_table];
		$result_id_field = $this->tables[$result_table];
		
		$results = $this->getTable($result_table);
		
		// Get count
		$select = $this->prepare('SELECT COUNT(' . $count_id_field . ') AS count FROM ' . $count_table . ' WHERE ' . $result_id_field  . ' = :result_id;');
		
		// Update row
		$update = $this->prepare('UPDATE ' . $result_table . ' SET ' . $result_field . ' = :count WHERE ' . $result_id_field . ' = :result_id;');
		
		foreach($results as $result){
			$result_id = $result[$result_id_field];
			if(!$select->execute(array(':result_id' => $result_id))){
				return false;
			}
		
			$counts = $select->fetchAll();
			$count = $counts[0]['count'];
		
			if(!$update->execute(array(':count' => $count, ':result_id' => $result_id))){
				return false;
			}
		}
		
		return true;
	}
	
	// SHOW RIGHTS
	public function showRights($name, $right_id=null){
		if(empty($name)){
			return false;
		}
		
		$query = $this->prepare('SELECT right_id, right_title FROM rights;');
		$query->execute();
		$rights = $query->fetchAll();
		
		$html = '<select name="' . $name . '" id="' . $name . '"><option value=""></option>';
		
		foreach($rights as $right){
			$html .= '<option value="' . $right['right_id'] . '"';
			if($right['right_id'] == $right_id){
				$html .= ' selected="selected"';
			}
			$html .= '>' . $right['right_title'] . '</option>';
		}
		
		$html .= '</select>';
		
		return $html;
	}
	
	// SHOW PRIVACY
	public function showPrivacy($name, $privacy_id=1){
		if(empty($name)){
			return false;
		}
		
		$privacy_levels = array(1 => 'Public', 2 => 'Protected', 3 => 'Private');
		
		$html = '<select name="' . $name . '" id="' . $name . '">';
		
		foreach($privacy_levels as $privacy_level => $privacy_label){
			$html .= '<option value="' . $privacy_level . '"';
			if($privacy_level == $privacy_id){
				$html .= ' selected="selected"';
			}
			$html .= '>' . $privacy_label . '</option>';
		}
		
		$html .= '</select>';
		
		return $html;
	}
	
	// SHOW PILES
	public function showPiles($name, $pile_id=null, $static_only=false){
		if(empty($name)){
			return false;
		}
		
		if($static_only === true){	
			$query = $this->prepare('SELECT pile_id, pile_title FROM piles WHERE pile_type = :pile_type;');
			$query->execute(array(':pile_type', 'static'));
		}
		else{
			$query = $this->prepare('SELECT pile_id, pile_title FROM piles;');
			$query->execute();
		}
		$piles = $query->fetchAll();
		
		$html = '<select name="' . $name . '" id="' . $name . '">';
		
		foreach($piles as $pile){
			$html .= '<option value="' . $pile['pile_id'] . '"';
			if($pile['pile_id'] == $pile_id){
				$html .= ' selected="selected"';
			}
			$html .= '>' . $pile['pile_title'] . '</option>';
		}
		
		$html .= '</select>';
		
		return $html;
	}
	
	// SHOW THEMES
	public function showThemes($name, $theme_id=null){
		if(empty($name)){
			return false;
		}
		
		$query = $this->prepare('SELECT theme_id, theme_title FROM themes;');
		$query->execute();
		$themes = $query->fetchAll();
		
		$html = '<select name="' . $name . '" id="' . $name . '">';
		
		foreach($themes as $theme){
			$html .= '<option value="' . $theme['theme_id'] . '"';
			if($theme['theme_id'] == $theme_id){
				$html .= ' selected="selected"';
			}
			$html .= '>' . $theme['theme_title'] . '</option>';
		}
		
		$html .= '</select>';
		
		return $html;
	}
	
	// SHOW EXIF NAMES
	public function showEXIFNames($name, $exif_name=null){
		if(empty($name)){
			return false;
		}
		
		$query = $this->prepare('SELECT DISTINCT exif_name FROM exifs ORDER BY exif_name ASC;');
		$query->execute();
		$exifs = $query->fetchAll();
		
		$html = '<select name="' . $name . '" id="' . $name . '"><option value=""></option>';
		
		foreach($exifs as $exif){
			$html .= '<option value="' . $exif['exif_name'] . '"';
			if($exif['exif_name'] == $exif_name){
				$html .= ' selected="selected"';
			}
			$html .= '>' . $exif['exif_name'] . '</option>';
		}
		
		$html .= '</select>';
		
		return $html;
	}
	
	
	// TABLE AND ROW FUNCTIONS
	public function getTable($table, $ids=null, $limit=null, $page=1, $order_by=null){
		if(empty($table)){
			return false;
		}
		if(!is_int($page) or ($page < 1)){
			$page = 1;
		}
		
		$table = $this->sanitize($table);
		
		$sql_params = array();
		
		$order_by_sql = '';
		$limit_sql = '';
		
		if(!empty($order_by)){
			if(is_string($order_by)){
				$order_by = $this->sanitize($order_by);
				$order_by_sql = ' ORDER BY ' . $order_by;
			}
			elseif(is_array($order_by)){
				foreach($order_by as &$by){
					$by = $this->sanitize($by);
				}
				$order_by_sql = ' ORDER BY ' . implode(', ', $order_by);
			}
		}
		
		if(!empty($limit)){
			$limit = intval($limit);
			$page = intval($page);
			$limit_sql = ' LIMIT ' . ($limit * ($page - 1)) . ', ' . $limit;
		}
		
		if(empty($ids)){
			$query = $this->prepare('SELECT * FROM ' . $table . $order_by_sql . $limit_sql . ';');
		}
		else{
			$ids = self::convertToIntegerArray($ids);
			$field = $this->tables[$table];
			
			$query = $this->prepare('SELECT * FROM ' . $table . ' WHERE ' . $field . ' = ' . implode(' OR ' . $field . ' = ', $ids) . $order_by_sql . $limit_sql . ';');
		}
		
		$query->execute($sql_params);
		$table = $query->fetchAll();
		return $table;
	}
	
	public function getRow($table, $id){
		// Error checking
		if(empty($id)){ return false; }
		if(!($id = intval($id))){ return false; }
		
		$table = $this->getTable($table, $id);
		if(count($table) != 1){ return false; }
		return $table[0];
	}
	
	public function addRow($fields=null, $table){
		// Error checking
		if(empty($table) or (!is_array($fields) and isset($fields))){
			return false;
		}
		
		if(empty($fields)){
			$fields = array();
		}
		
		$table = $this->sanitize($table);
		
		// Add default fields
		switch($table){
			case 'comments':
				$fields['comment_created'] = date('Y-m-d H:i:s');
				break;
			case 'guests':
				$fields['guest_views'] = 0;
				$fields['guest_created'] = date('Y-m-d H:i:s');
				break;
			case 'rights':
				$fields['right_modified'] = date('Y-m-d H:i:s');
				break;
			case 'pages':
				$fields['page_views'] = 0;
				$fields['page_created'] = date('Y-m-d H:i:s');
				$fields['page_modified'] = date('Y-m-d H:i:s');
				break;
			case 'piles':
				$fields['pile_views'] = 0;
				$fields['pile_created'] = date('Y-m-d H:i:s');
				$fields['pile_modified'] = date('Y-m-d H:i:s');
				break;
			case 'sizes':
				if(!isset($fields['size_title'])){ $fields['size_title'] = ''; }
				break;
			case 'users':
				$fields['user_created'] = date('Y-m-d H:i:s');
				break;
			default:
				break;
		}
		
		$field = $this->tables[$table];
		unset($fields[$field]);
		
		if(count($fields) > 0){
			$columns = array_keys($fields);
			$values = array_values($fields);
		
			$value_slots = array_fill(0, count($values), '?');
		
			// Add row to database
			$query = $this->prepare('INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $value_slots) . ');');
		}
		else{
			$values = array();
			$query = $this->prepare('INSERT INTO ' . $table . ' (' . $this->tables[$table] . ') VALUES (?);');
			$values = array(PDO::PARAM_NULL);
		}
		
		if(!$query->execute($values)){
			return false;
		}
		
		// Return ID
		$id = intval($this->db->lastInsertId(TABLE_PREFIX . $table . '_' . $field . '_seq'));
		
		if($id == 0){
			return false;
		}
		
		return $id;
	}
	
	public function updateRow($fields, $table, $ids=null, $default=true){
		// Error checking
		if(empty($fields) or empty($table) or !is_array($fields)){
			return false;
		}
		
		$table = $this->sanitize($table);
		
		$ids = self::convertToIntegerArray($ids);
		$field = $this->tables[$table];
		
		// Add default fields
		if($default === true){
			switch($table){
				case 'photos':
					$fields['photo_modified'] = date('Y-m-d H:i:s');
					break;
				case 'piles':
					$fields['pile_modified'] = date('Y-m-d H:i:s');
					break;
				case 'pages':
					$fields['page_modified'] = date('Y-m-d H:i:s');
					break;
			}
		}
		
		$columns = array_keys($fields);
		$values = array_values($fields);

		// Add row to database
		$query = $this->prepare('UPDATE ' . $table . ' SET ' . implode(' = ?, ', $columns) . ' = ? WHERE ' . $field . ' = ' . implode(' OR ' . $field . ' = ', $ids) . ';');
		if(!$query->execute($values)){
			return false;
		}
		
		return true;
	}
	
	public function deleteRow($table, $ids=null){
		if(empty($table) or empty($ids)){
			return false;
		}
		
		$table = $this->sanitize($table);
		
		$ids = self::convertToIntegerArray($ids);
		$field = $this->tables[$table];
		
		// Delete row
		$query = 'DELETE FROM ' . $table . ' WHERE ' . $field . ' = ' . implode(' OR ' . $field . ' = ', $ids) . ';';
		
		if(!$this->exec($query)){
			return false;
		}
		
		return true;
	}
	
	public function deleteEmptyRow($table, $fields){
		if(empty($table) or empty($fields)){
			return false;
		}
		
		$table = $this->sanitize($table);
		
		$fields = self::convertToArray($fields);
		
		$conditions = array();
		foreach($fields as $field){
			$conditions[] = '(' . $field . ' = ? OR ' . $field . ' IS NULL)';
		}
		
		$sql_params = array_fill(0, count($fields), '');
		
		// Delete empty rows
		$query = $this->prepare('DELETE FROM ' . $table . ' WHERE ' . implode(' OR ', $conditions) . ';');
		
		if(!$query->execute($sql_params)){
			return false;
		}
		
		return true;
	}
	
	// GET LIBRARY INFO
	public function getInfo(){
		$info = array();
		
		// Get tables
		$tables = $this->tables;
		
		// Exclude tables
		unset($tables['rights']);
		unset($tables['exifs']);
		unset($tables['extensions']);
		unset($tables['themes']);
		unset($tables['sizes']);
		unset($tables['rights']);
		
		// Run helper function
		foreach($tables as $table => $selector){
			$info[] = array('table' => $table, 'count' => self::countTable($table));
		}
		
		foreach($info as &$table){
			if($table['count'] == 1){
				$table['display'] = preg_replace('#s$#si', '', $table['table']);
			}
			else{
				$table['display'] = $table['table'];
			}
		}
		
		return $info;
	}
	
	function countTable($table){
		$field = @$this->tables[$table];
		if(empty($field)){ return false; }
		
		$query = $this->prepare('SELECT COUNT(' . $table . '.' . $field . ') AS count FROM ' . $table . ';');
		$query->execute();
		$count = $query->fetch();
		
		$count = intval($count['count']);
		return $count;
	}
	
	// RECORD STATISTIC
	// Record a visitor to statistics
	public function recordStat($page_type=null){
		if(!$this->returnConf('stat_enabled')){
			return false;
		}
		
		if(empty($_SESSION['alkaline']['duration_start']) or ((time() - @$_SESSION['alkaline']['duration_recent']) > 3600)){
			$duration = 0;
			$_SESSION['alkaline']['duration_start'] = time();
		}
		else{
			$duration = time() - $_SESSION['alkaline']['duration_start'];
		}
		
		$_SESSION['alkaline']['duration_recent'] = time();
		
		$referrer = (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : null;
		$page = (!empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : null;
		
		if(stripos($referrer, LOCATION . BASE) === false){
			$local = 0;
		}
		else{
			$local = 1;
		}
		
		$query = $this->prepare('INSERT INTO stats (stat_session, stat_date, stat_duration, stat_referrer, stat_page, stat_page_type, stat_local) VALUES (:stat_session, :stat_date, :stat_duration, :stat_referrer, :stat_page, :stat_page_type, :stat_local);');
		
		$query->execute(array(':stat_session' => session_id(), ':stat_date' => date('Y-m-d H:i:s'), ':stat_duration' => $duration, ':stat_referrer' => $referrer, ':stat_page' => $page, ':stat_page_type' => $page_type, ':stat_local' => $local));
	}
	
	// FORM HANDLING
	// Set form option
	public function setForm(&$array, $name, $unset=''){
		@$value = $_POST[$name];
		if(!isset($value)){
			$array[$name] = $unset;
		}
		elseif(empty($value)){
			$array[$name] = '';
		}
		elseif($value == 'true'){
			$array[$name] = true;
		}
		else{
			$array[$name] = $value;
		}
	}
	
	// Retrieve form option (HTML)
	public function readForm($array=null, $name, $check=true){
		if(is_array($array)){
			@$value = $array[$name];
		}
		else{
			$value = $name;
		}
		
		if(!isset($value)){
			return false;
		}
		elseif($check === true){
			if($value === true){
				return 'checked="checked"';
			}
		}
		elseif(!empty($check)){
			if($value == $check){
				return 'selected="selected"';
			}
		}
		else{
			return 'value="' . $value . '"';
		}
	}
	
	// Return form option
	public function returnForm($array, $name, $default=null){
		@$value = $array[$name];
		if(!isset($value)){
			if(isset($default)){
				return $default;
			}
			else{
				return false;
			}
		}
		return $value;
	}
	
	// CONFIGURATION HANDLING
	// Set configuration key
	public function setConf($name, $unset=''){
		return self::setForm($_SESSION['alkaline']['config'], $name, $unset);
	}
	
	// Read configuration key and return value in HTML
	public function readConf($name, $check=true){
		return self::readForm($_SESSION['alkaline']['config'], $name, $check);
	}
	
	// Read configuration key and return value
	public function returnConf($name){
		return self::makeHTMLSafe(self::returnForm($_SESSION['alkaline']['config'], $name));
	}
	
	// Save configuration
	public function saveConf(){
		return file_put_contents($this->correctWinPath(PATH . 'config.json'), json_encode(self::reverseHTMLSafe($_SESSION['alkaline']['config'])));
	}
	
	// URL HANDLING
	// Find ID number from string
	public function findID($string, $numeric_required=false){
		$matches = array();
		if(is_numeric($string)){
			$id = intval($string);
		}
		elseif(preg_match('#^([0-9]+)#s', $string, $matches)){
			$id = intval($matches[1]);
		}
		elseif($numeric_required === true){
			return false;
		}
		else{
			$id = $string;
		}
		return $id;
	}
	
	// Find photo ID references from a string
	public function findIDRef($str){
		preg_match_all('#["\']{1}(?=' . LOCATION . '/|/)[^"\']*([0-9]+)[^/.]*\.(?:' . IMG_EXT . ')#si', $str, $matches, PREG_SET_ORDER);
		
		$photo_ids = array();
		
		foreach($matches as $match){
			$photo_ids[] = intval($match[1]);
		}
		
		$photo_ids = array_unique($photo_ids);
		
		return $photo_ids;
	}
	
	// Make a URL-friendly string
	public function makeURL($string){
		$string = html_entity_decode($string, 1, 'UTF-8');
		$string = strtolower($string);
		$string = preg_replace('#([^a-zA-Z0-9]+)#s', '-', $string);
		$string = preg_replace('#^(\-)+#s', '', $string);
		$string = preg_replace('#(\-)+$#s', '', $string);
		return $string;
	}
	
	// Minimize non-unique elements of a URL
	public function minimizeURL($url){
		$url = preg_replace('#^http\:\/\/www\.#s', '', $url);
		$url = preg_replace('#^http\:\/\/#s', '', $url);
		$url = preg_replace('#^www\.#s', '', $url);
		$url = preg_replace('#\/$#s', '', $url);
		return $url;
	}
	
	// Trim long strings
	public function fitString($string, $length=50){
		$length = intval($length);
		if($length < 3){ return false; }
		
		$string = trim($string);
		if(strlen($string) > $length){
			$string = rtrim(substr($string, 0, $length - 3)) . '&#0133;';
		}
		return $string;
	}
	
	// Trim long strings by word
	public function fitStringByWord($string, $length=50){
		$length = intval($length);
		if($length < 3){ return false; }
		
		$string = trim($string);
		if(strlen($string) > $length){
			$space = strpos($string, ' ', $length);
			if($space !== false){
				$string = substr($string, 0, $space) . '&#0133;';
			}
		}
		return $string;
	}
	
	// Chose between singular and plural nouns
	public function echoCount($count, $singular, $plural=null){
		if(empty($plural)){
			$plural = $singular . 's';
		}
		
		if($count == 1){
			echo $singular;
		}
		else{
			echo $plural;
		}
	}
	
	// Chose between singular and plural nouns
	public function echoFullCount($count, $singular, $plural=null){
		$count =  number_format($count) . ' ' . self::echoCount($count, $singular, $plural);
		return $count;
	}
	
	// Change path to Windows-friendly
	public function correctWinPath($path){
		if(SERVER_TYPE == 'win'){
			$path = str_replace('/', '\\', $path);
		}
		return $path;
	}
	
	// REDIRECT HANDLING
	// Current page for redirects
	public function location(){
		$location = LOCATION;
		$location .= preg_replace('#\?.*$#si', '', $_SERVER['REQUEST_URI']);
		
		// Retain page data
		preg_match('#page=[0-9]+#si', $_SERVER['REQUEST_URI'], $matches);
		if(!empty($matches[0])){
			$location .= '?' . $matches[0];
		}
		
		return $location;
	}
	
	public function locationFull($append=null){
		$location = LOCATION . $_SERVER['REQUEST_URI'];
		if(!empty($append)){
			if(preg_match('#\?.*$#si', $location)){
				$location .= '&' . $append;
			}
			else{
				$location .= '?' . $append;
			}
		}
		
		return $location;
	}
	
	public function setCallback($page=null){
		$_SESSION['alkaline']['callback'] = self::location();
	}
	
	public function callback($url=null){
		if(!empty($_SESSION['alkaline']['callback'])){
			header('Location: ' . $_SESSION['alkaline']['callback']);
		}
		elseif(!empty($url)){
			header('Location: ' . $url);
		}
		else{
			header('Location: ' . LOCATION . BASE . ADMIN . 'dashboard/');
		}
		exit();
	}
	
	// Go back (for cancel links)
	public function back(){
		if(!empty($_SESSION['alkaline']['back'])){
			echo $_SESSION['alkaline']['back'];
		}
		elseif(!empty($_SERVER['HTTP_REFERER'])){
			echo $_SERVER['HTTP_REFERER'];
		}
		else{
			header('Location: ' . LOCATION . BASE . ADMIN . 'dashboard/');
		}
	}
	
	// MAIL	
	protected function email($to=0, $subject, $message){
		if(empty($subject) or empty($message)){ return false; }
		
		if($to == 0){
			$to = $this->returnConf('web_email');
		}
		
		if(is_int($to) or preg_match('#[0-9]+#s', $to)){
			$query = $this->prepare('SELECT user_email FROM users WHERE user_id = ' . $to);
			$query->execute();
			$user = $query->fetch();
			$to = $user['user_email'];
		}
		
		$subject = 'Alkaline: ' . $subject;
		$message = $message . "\r\n\n" . '-- Alkaline';
		$headers = 'From: ' . $this->returnConf('web_email') . "\r\n" .
			'Reply-To: ' . $this->returnConf('web_email') . "\r\n" .
			'X-Mailer: PHP/' . phpversion();
		
		return mail($to, $subject, $message, $headers);
	}
	
	// DEBUG
	public function error($message, $number=null){
		$_SESSION['alkaline']['error']['message'] = $message;
		$_SESSION['alkaline']['error']['number'] = $number;
		require_once(PATH . '/error.php');
		exit();
	}
	
	// Ouput debug info
	public function debug(){
		$_SESSION['alkaline']['debug']['execution_time'] = microtime(true) - $_SESSION['alkaline']['debug']['start_time'];
		return $_SESSION['alkaline']['debug'];
	}
	
	// Add report to log
	public function report($message, $number=null){
		if(@$_SESSION['alkaline']['warning'] == $message){ return false; }
		
		$_SESSION['alkaline']['warning'] = $message;
		
		// Format message
		$message = date('Y-m-d H:i:s') . "\t" . $message;
		if(!empty($number)){ $message .= ' (' . $number . ')'; }
		$message .= "\n";
		
		// Write message
		$handle = fopen($this->correctWinPath(PATH . DB . 'log.txt'), 'a');
		if(@fwrite($handle, $message) === false){
			$this->error('Cannot write to report file.');
		}
		fclose($handle);
		
		return true;
	}
}

?>