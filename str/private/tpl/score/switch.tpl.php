<?php
$out = $input = $summ = 0;
$sc = $USER->get_score();
if($_POST){
	$out = filter_input(INPUT_POST,"out",FILTER_VALIDATE_INT);
	$input = filter_input(INPUT_POST,"input",FILTER_VALIDATE_INT);
	$summ = (float)filter_input(INPUT_POST,"summ");
	$error = FALSE;
	if($out == $input){
		set_error("Выберите различные счета источника и приемника");
		$error = TRUE;
	}
	if($summ <= 0){
		set_error("Введите сумму");
		$error = TRUE;
	}
	
	if(!$error){
		$comment = sprintf('Перевод с %s на %s',$sc[$out],$sc[$input]);
		$ins2 = $DB -> prepare("INSERT INTO `outgo_data`(`user_id`, `line_id`, `time`,`cost`, `score_id`,`comment`) VALUES (:user_id, 5, :time, :cost, :score_id,:comment)");
		$ins2 -> execute(array(
				'user_id'=>$USER->id,
				'time'=>time(),
				'cost'=>$summ,
				'score_id'=>$out,
				'comment'=>$comment,
			));
		$ins1 = $DB -> prepare("INSERT INTO `input_data`(`user_id`, `line_id`, `time`,`cost`, `score_id`,`comment`) VALUES (:user_id, 25, :time, :cost, :score_id,:comment)");
		$ins1 -> execute(array(
				'user_id'=>$USER->id,
				'time'=>time(),
				'cost'=>$summ,
				'score_id'=>$input,
				'comment'=>$comment,
			));
		if($ins1 -> rowCount() > 0 AND $ins2 -> rowCount() > 0){
			$out = $input = $summ = 0;
		}else{
			set_error("Ошибка выполнения перевода");
		}
	}
}
foreach($sc as $key=>$val){
	if(!$out){
		$out = $key;
	}elseif($out AND !$input){
		$input = $key;
	}
}
printf('<form method="POST">
	<div class="field">
		<label for="in_line">C</label>
		%s
	</div>
	<div class="field">
		<label for="in_line">На</label>
		%s
	</div>
	<div class="field">
		<label for="summ">Сумма</label>
		<input type="number" name="summ" id="summ" value="%s" placeholder="Сумма" step="0.01" required/>
	</div>
	<div class="field center">
		<input type="submit" value="Сохранить" /> <a href="/" class="button">Назад</a>
	</div>
	</form>',
	$USER->get_score_select($out,'out'),
	$USER->get_score_select($input,'input'),
	$summ
	);