<?php
	$NO_SESSION = TRUE;
	$NULL_TIME_ZONE = TRUE;
	require "!init.php";
	
	/*Clear old seccion*/
	if(file_exists($session_path)){
		$files = scandir($session_path);
		if($files){
			foreach($files as $file){
				$full_file_path = $session_path.$file;
				if(preg_match("/^sess_/",$file) AND filemtime($full_file_path) < (time() - 604800)){
					unlink($full_file_path);
				}
			}
		}
	}
	echo 'Cron Ok';