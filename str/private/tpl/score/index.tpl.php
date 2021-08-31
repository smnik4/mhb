<?php

$sc = $USER->get_score();
if(count($sc) > 0){
	foreach($sc as $key=>$name){
		$in = $out = 0;
		$sel_in = $DB -> prepare("SELECT SUM(`cost`) as cost FROM `outgo_data` WHERE `score_id`=:score_id");
		$sel_in -> execute(array('score_id'=>$key));
		if($sel_in -> rowCount() > 0){
			$data = $sel_in -> fetch();
			if(isset($data['cost'])){
				$out = (float)$data['cost'];
			}
		}
		$sel_out = $DB -> prepare("SELECT SUM(`cost`) as cost FROM `input_data` WHERE `score_id`=:score_id");
		$sel_out -> execute(array('score_id'=>$key));
		if($sel_out -> rowCount() > 0){
			$data = $sel_out -> fetch();
			if(isset($data['cost'])){
				$in = (float)$data['cost'];
			}
		}
		$balance = $in - $out;
		printf('<div class="histiry_line">
			<a href="/?page=score&action=edit&record=%s">
				<div class="summ">%s</div>
				%s
				<div class="clear"></div>
			</a>
			</div>',
			$key,
			number_format($balance,2),
			$name
			);
	}
	print '<div class="field line row2 center">
			<a href="/?page=score&action=switch" class="button big">Перевод</a>
		</div>
		<div class="field line row2 center">
			<a href="/?page=score&action=add_cart" class="button big">Добавить счет</a>
		</div>';
}else{
	set_error("Счета не найдены");
}