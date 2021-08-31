<?php

class CFG{
	public $name = NULL;
	public $errors = array();
	public $VARS = NULL;
	
	public function __construct($config_path = FALSE){
		if(!empty($config_path)){
			$this->name = $config_path;
			$path = $_SERVER['DOCUMENT_ROOT']."/".$config_path."/config.ini";
			if(file_exists($path)){
				$VARS = parse_ini_file($path,TRUE);
				if($VARS === FALSE){
					$this->errors[] = 'Config load error';
				}else{
					$this->VARS = $VARS;
				}
			}else{
				$this->errors[] = 'Config data not found';
			}
		}else{
			$this->errors[] = 'Empty config line';
		}
	}
	
	public function get_value($name){
		if(isset($this->VARS[$name])){
			return $this->VARS[$name];
		}else{
			$this->errors[] = 'Config value "'.$name.'" not found';
			return FALSE;
		}
	}
	
	public function get_vars(){
		return $this->VARS;
	}
	
	public function set_value($name,$value,$add_to_array = TRUE){
		if(isset($this->VARS[$name])){
			if(is_array($this->VARS[$name]) AND $add_to_array){
				$this->VARS[$name][] = $value;
			}else{
				$this->VARS[$name] = $value;
			}
			return TRUE;
		}else{
			if(isset($this->VARS[$name])){
				$obj = $this->VARS[$name];
				if(is_array($obj) AND $add_to_array){
					$obj[] = $value;
				}else{
					$obj = $value;
				}
				$this->VARS[$name] = $obj;
			}else{
				$this->VARS[$name] = $value;
			}
			
		}
	}
	
	public function debug(){
		echo '<pre>';
		$debug_info = debug_backtrace()[0];
		printf("DEBUG ON: %s:%s\n",$debug_info['file'],$debug_info['line']);
		print_r($this);
		echo "</pre>\n";
	}
}

function require_dir($dirname = FALSE){
	global $ERRORS,$GLOBAL,$DB;
	if(!empty($dirname)){
		$path = $_SERVER['DOCUMENT_ROOT']."/".$dirname;
		if(file_exists($path)){
			if($dir = scandir($path)){
				foreach($dir as $key=>$file){
					if(!preg_match("/^\./",$file)){
						$file = $path."/".$file;
						require $file;
					}
					$dir[$key] = $file;
				}
			}else{
				$ERRORS[] = 'Load dir "'.$dirname.'" not permission read';
			}
		}else{
			$ERRORS[] = 'Load dir "'.$dirname.'" not found';
		}
	}else{
		$ERRORS[] = 'Empty load dir: '.$dirname;
	}
}

function set_error($message = FALSE){
	global $ERRORS;
	if($message){
		$ERRORS[] = $message;
	}
}

function set_message($message = FALSE){
	global $MESSAGES;
	if($message){
		$MESSAGES[] = $message;
	}
}

function set_content($html = FALSE,$rewrite = FALSE){
	global $CONTENT;
	if($html){
		if($rewrite){
			$CONTENT[] = $html;
		}else{
			$CONTENT[] .= $html;
		}
	}
	return $html;
}

function kdsort($a,$b){
	if ($a['time'] == $b['time']) {
        return 0;
    }
    return ($a['time'] > $b['time']) ? -1 : 1;
}
function kdsortd($a,$b){
	if ($a['time'] == $b['time']) {
		if(isset($a['id'])){
			return ($a['id'] > $b['id']) ? -1 : 1;
		}else{
			return 0;
		}
    }
    return ($a['time'] < $b['time']) ? -1 : 1;
}