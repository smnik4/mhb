<?php
		
	require "lbr/!init.php";
	require "lbr/global.php";
	
	$GLOBAL = new CFG("cnf");
	$GLOBAL->set_value("www_dir",$_SERVER['DOCUMENT_ROOT']."/",TRUE);
	
	require_dir("cls");
	
	$THEME = new THEME();
	if($DB){
		$USER = new USER();
	}
	
	if($THEME){
		$CURRENT_PAGE = filter_input(INPUT_GET,"page");
		if($USER){
			if($USER->auth){
				$THEME->user_auth = TRUE;
				if(!$CURRENT_PAGE){
					$CURRENT_PAGE = 'start';
				}
			}
		}
		
		$PAGE = new PAGE($THEME->user_auth);
		if($PAGE->public_page){
			$THEME->public_page = TRUE;
		}
		if($PAGE->notitle){
			$THEME->notitle = TRUE;
		}
		$THEME->set_input("title",$PAGE->title);
		$THEME->set_input("content",$PAGE->content);
		$THEME->set_input("main_menu",$PAGE->menu);
		
		$ERRORS = array_merge($ERRORS,$GLOBAL->errors);
		$ERRORS = array_merge($ERRORS,$GLOBAL->get_value("errors"));
		$ERRORS = array_merge($ERRORS,$THEME->errors);
		$ERRORS = array_merge($ERRORS,$PAGE->errors);
		
		if($USER){
			$ERRORS = array_merge($ERRORS,$USER->errors);
		}
		//debug($PAGE);
		print $THEME->render();
		print $THEME->compress_html();
		if($THEME->errors){
			debug($THEME->errors);
		}
	}
	//debug(session_get_cookie_params());
	//debug($USER);
	//debug($THEME);
	//debug($_SESSION);
	//debug(session_id());
	//debug($_SERVER['REMOTE_ADDR']);
	
	//$GLOBAL->debug();
	//$HTML->debug();