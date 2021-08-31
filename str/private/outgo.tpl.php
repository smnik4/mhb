<?php
/*направления расходов*/

//debug($this->action);
//debug($this->page);

$required_file = FALSE;

switch($this->action){
	case "add":
		$this->title = 'Добавить направление';
		$required_file = 'add_outgo';
		break;
	case "delete":
		$this->title = 'Удалить направление';
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