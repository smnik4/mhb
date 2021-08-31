<?php
	$session_path = dirname($_SERVER['DOCUMENT_ROOT'])."/sessions/";
	session_save_path($session_path);
	//ini_set('session.gc_maxlifetime', 604800);
	//ini_set('session.cookie_lifetime', 604800);
	if(!isset($NO_SESSION)){
		session_start();
	}

	function debug($data = FALSE){
		global $GLOBAL;//debug_ip=null
		//if($data){
			$show = TRUE;
			if($GLOBAL){
				$ip = $GLOBAL->get_value("debug_ip");
				if(!empty($ip)){
					if(filter_var($ip,FILTER_VALIDATE_IP)){
						$re_ip = filter_input(INPUT_SERVER,"REMOTE_ADDR",FILTER_VALIDATE_IP);
						if($re_ip !== $ip){
							$show = FALSE;
						}
					}
				}
			}
			if($show){
				echo '<pre>';
				$debug_info = debug_backtrace()[0];
				printf("DEBUG ON: %s:%s\n",$debug_info['file'],$debug_info['line']);
				if(is_string($data)){
					//$data = htmlspecialchars($data);
				}
				print_r($data);
				echo "</pre>\n";
			}
		//}
	}
	
	function getRealIpAddr() {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])){
			$ip=$_SERVER['HTTP_CLIENT_IP'];
		}elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		}else{
			$ip=$_SERVER['REMOTE_ADDR'];
			
		}
		return $ip;
	}
	
	function get_ip_info(){
		$ip = getRealIpAddr();
		$url = sprintf('http://api.sypexgeo.net/json/%s',$ip);
		$ch = curl_init ();
		curl_setopt ($ch, CURLOPT_URL, $url); 
		curl_setopt ($ch, CURLOPT_HEADER, 0); 
		curl_setopt ($ch, CURLOPT_NOBODY, 0); 
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt ($ch, CURLOPT_TIMEOUT, $timeout);
		$result = curl_exec ($ch);
		$info = curl_getinfo ($ch);
		curl_close($ch);
		if($info['http_code'] === 200){
			return $result;
		}
	}

	function get_time_zone(){
		global $NULL_TIME_ZONE;
		$data = get_ip_info();
		if($res = @json_decode($data,TRUE) AND !isset($NULL_TIME_ZONE)){
			//debug($_SERVER['HTTP_ACCEPT_LANGUAGE']);
			if(isset($res['country']['iso'])){
				define("LNG", strtolower($res['country']['iso']));
			}
			if(isset($res['region']['timezone'])){
				return $res['region']['timezone'];
			}
			
		}
		return 'Etc/GMT0';//default
	}
	
	ini_set('date.timezone', get_time_zone());
	ini_set('display_errors',1);
	error_reporting(E_ALL);
	
	ob_start();
	
	$MESSAGES = $ERRORS = array();
	
	$GLOBAL = $HTML = $THEME = $DB = $USER = $PAGES = $CURRENT_PAGE = $CONTENT = FALSE;