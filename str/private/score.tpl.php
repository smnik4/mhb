<?php
$required_file = FALSE;

switch($this->action){
	case "switch":
		$this->title = 'Перевод';
		$required_file = 'switch';
		break;
	case "add_cart":
		$this->title = 'Добавить счет';
		$required_file = 'add_cart';
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