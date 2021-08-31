<?php

$data = $USER->get_gain_array();
if(count($data) > 0){
	echo '<ul>';
	foreach($data as $row){
		printf('<li>%s</li>',$row);
	}
	echo '</ul>';
}else{
	set_error("Источники доходов не найдены");
}
//<input type="button" value="Добавить свой" />
?>
<div class="field center">
	<a href="/?page=gain&action=add" class="button">Добавить свой</a>
</div>