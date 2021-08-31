<?php
/*Источники доходов*/

//debug($this->action);
//debug($this->page);

$required_file = FALSE;

switch($this->action){
	case "add":
		$this->title = 'Добавить источник доходов';
		$required_file = 'add_gain';
		break;
	case "delete":
		$this->title = 'Удалить источник доходов';
		$required_file = 'delete_gain';
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