<?php

$data = $USER->get_outgo_array();
if(count($data) > 0){
	echo '<ul>';
	foreach($data as $row){
		printf('<li>%s</li>',$row);
	}
	echo '</ul>';
}else{
	set_error("Направления расходов не найдены");
}
?>
<div class="field center">
	<a href="/?page=outgo&action=add" class="button">Добавить свой</a>
</div>