<?php

//debug($this->action);
//debug($this->page);

$required_file = FALSE;

switch($this->action){
	case "out":
		$this->title = 'Добавить расход';
		$required_file = 'add_out';
		break;
	case "in":
		$this->title = 'Добавить доход';
		$required_file = 'add_in';
		break;
	default:
		$required_file = 'index';
}

if($required_file){
	$required_file = __DIR__ . "/tpl/" . $this->page . "/" . $required_file . ".tpl.php";
	if(file_exists($required_file)){
		require $required_file;
	}else{
		set_error("Не удалось открыть страницу.");
	}
}else{
	set_error("Не удалось открыть страницу.");
}