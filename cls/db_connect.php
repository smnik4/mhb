<?php
	
	$DBdata = $GLOBAL->get_value("db");

	if( !empty($DBdata["host"]) AND 
		!empty($DBdata["host"]) AND 
		!empty($DBdata["host"]) AND 
		!empty($DBdata["host"])){
		$dsn = sprintf("mysql:host=%s;dbname=%s;charset=UTF8",
			$DBdata["host"],
			$DBdata["base"]
			);
		$opt = array(
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		);
		try {
			$DB = new PDO(
					$dsn,
					$DBdata["user"],
					$DBdata["pass"],
					$opt);
		} catch (Exception $e) {
			$ERRORS[] = 'Error connect to DB';
		}
	}else{
		$ERRORS[] = 'Error DB param';
	}