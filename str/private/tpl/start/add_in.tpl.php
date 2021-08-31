<?php

$in_line = filter_input(INPUT_GET,"line");
$sub_action = filter_input(INPUT_GET,"sub_action");
$id = filter_input(INPUT_GET,"record",FILTER_VALIDATE_INT);
$in_summ = $in_comment = $score_id = NULL;
$in_date = date("Y-m-d");
$in_time = date("H:i");

if($id > 0){
	$this->title = 'Редактировать доход';
	$sel = $DB -> prepare("SELECT * FROM `input_data` WHERE `id`=:id AND `user_id`=:user_id");
	$sel -> execute(array(
			'id'=>$id,
			'user_id'=>$USER->id,
		));
	if($sel->rowCount()>0){
		$data = $sel ->fetch();
		$in_line = $data['line_id'];
		$in_summ = $data['cost'];
		$score_id = $data['score_id'];
		$in_comment = $data['comment'];
		$in_date = date("Y-m-d",$data['time']);
		$in_time = date("H:i",$data['time']);
		if($sub_action === "delete"){
			$del = $DB -> prepare("DELETE FROM `input_data` WHERE `id`=:id");
			$del -> execute(array(
					'id'=>$id,
				));
			if($del -> rowCount() > 0){
				header("location: /?page=history");
				exit();
			}else{
				set_error("Не удалось удалить запись");
			}
		}
	}else{
		set_error("Запись не найдена");
		return;
	}
}

if($_POST){
	$in_line = filter_input(INPUT_POST,"in_line");
	$in_summ = filter_input(INPUT_POST,"in_summ");
	$in_comment = filter_input(INPUT_POST,"in_comment");
	$score_id = filter_input(INPUT_POST,"score_id");
	$in_date = filter_input(INPUT_POST,"in_date");
	$in_time = filter_input(INPUT_POST,"in_time");
	$error = FALSE;
	if(empty($in_line)){
		set_error("Выберите направление");
		$error = TRUE;
	}
	if((float)$in_summ <= 0){
		set_error("Введите сумму");
		$error = TRUE;
	}
	if(empty($in_date)){
		set_error("Введите дату");
		$error = TRUE;
	}
	if(empty($in_time)){
		set_error("Введите время");
		$error = TRUE;
	}
	if(!$error){
		if(!$id){
			$ins = $DB -> prepare("INSERT INTO `input_data`(`user_id`, `line_id`, `time`,`cost`, `comment`, `score_id`) VALUES (:user_id,:line_id, :time, :cost, :comment, :score_id)");
			$ins -> execute(array(
					'user_id'=>$USER->id,
					'line_id'=>$in_line,
					'time'=>strtotime($in_date." ".$in_time.":00"),
					'cost'=>$in_summ,
					'comment'=>$in_comment,
					'score_id'=>$score_id,
				));
			if($ins -> rowCount() > 0){
				$in_line = filter_input(INPUT_GET,"line");
				$in_summ = $in_comment = $score_id = NULL;
				$in_date = date("Y-m-d");
				$in_time = date("H:i");
				set_message("Данные добавлены");
			}else{
				set_error("Ошибка добавления данных");
			}
		}else{
			$upd = $DB -> prepare("UPDATE `input_data` SET `time`=:time,`line_id`=:line_id,`cost`=:cost,`comment`=:comment,`score_id`=:score_id WHERE `id`=:id");
			$upd -> execute(array(
					'time'=>strtotime($in_date." ".$in_time.":00"),
					'line_id'=>$in_line,
					'cost'=>$in_summ,
					'comment'=>$in_comment,
					'score_id'=>$score_id,
					'id'=>$id,
				));
			if($upd -> rowCount() > 0){
				set_message("Данные обновлены");
			}else{
				set_error("Ошибка обновления данных");
			}
		}
		
	}
}

$button = '<a href="/?page=start" class="button">Назад</a>';
if($id){
	$button = '<a href="/?page=history" class="button">Назад</a>';
}
printf('<form method="POST">
	<div class="field">
		<label for="in_line">Счет</label>
		%s
	</div>
	<div class="field">
		<label for="in_line">Направление</label>
		%s
	</div>
	<div class="field">
		<label for="in_summ">Сумма</label>
		<input type="number" name="in_summ" id="in_summ" value="%s" placeholder="Сумма" step="0.01" required/>
	</div>
	<div class="field">
		<label for="in_date">Дата, время</label>
		<input type="date" name="in_date" id="in_date" value="%s" required/>
		<input type="time" name="in_time" id="in_time" value="%s" required/>
	</div>
	<div class="field">
		<label for="in_comment">Комментарий</label>
		<input type="text" name="in_comment" id="in_comment" value="%s" placeholder="Комментарий"/>
	</div>
	<div class="field center">
		<input type="submit" value="Сохранить" /> %s
	</div>
	</form>',
	$USER->get_score_select($score_id,'score_id'),
	$USER->get_gain_select($in_line,'in_line'),
	$in_summ,
	$in_date,
	$in_time,
	$in_comment,
	$button
	);
	
	if($id > 0){
		printf('<div class="field center"><a href="/?page=start&action=in&record=%s&sub_action=delete" class="button submit" text="Удалить запись">Удалить</a></div>',$id);
	}