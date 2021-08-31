<?php
//debug($this);
$sub_action = filter_input(INPUT_GET,"sub_action");
$out_line = filter_input(INPUT_GET,"line");
$id = filter_input(INPUT_GET,"record",FILTER_VALIDATE_INT);
$out_summ = $out_comment = $score_id = NULL;
$out_date = date("Y-m-d");
$out_time = date("H:i");

if($id > 0){
	$this->title = 'Редактировать расход';
	$sel = $DB -> prepare("SELECT * FROM `outgo_data` WHERE `id`=:id AND `user_id`=:user_id");
	$sel -> execute(array(
			'id'=>$id,
			'user_id'=>$USER->id,
		));
	if($sel->rowCount()>0){
		$data = $sel ->fetch();
		$out_line = $data['line_id'];
		$out_summ = $data['cost'];
		$out_comment = $data['comment'];
		$score_id = $data['score_id'];
		$out_date = date("Y-m-d",$data['time']);
		$out_time = date("H:i",$data['time']);
		if($sub_action === "delete"){
			$del = $DB -> prepare("DELETE FROM `outgo_data` WHERE `id`=:id");
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
	$out_line = filter_input(INPUT_POST,"out_line");
	$out_summ = 0;
	if(isset($_POST['out_summ'])){
		$out_summ = $_POST['out_summ'];
		if(is_array($out_summ)){
			$ot = 0;
			foreach($out_summ as $v){
				$v = str_replace(",",".",$v);
				$ot += (float)$v;
			}
			$out_summ = $ot;
		}
	}
	$out_comment = filter_input(INPUT_POST,"out_comment");
	$score_id = filter_input(INPUT_POST,"score_id");
	$out_date = filter_input(INPUT_POST,"out_date");
	$out_time = filter_input(INPUT_POST,"out_time");
	$error = FALSE;
	if(empty($out_line)){
		set_error("Выберите направление");
		$error = TRUE;
	}
	if((float)$out_summ <= 0){
		set_error("Введите сумму");
		$error = TRUE;
	}
	if(empty($out_date)){
		set_error("Введите дату");
		$error = TRUE;
	}
	if(empty($out_date)){
		set_error("Введите время");
		$error = TRUE;
	}
	if(!$error){
		if(!$id){
			$ins = $DB -> prepare("INSERT INTO `outgo_data`(`user_id`, `line_id`, `time`,`cost`, `comment`, `score_id`) VALUES (:user_id,:line_id, :time, :cost, :comment, :score_id)");
			$ins -> execute(array(
					'user_id'=>$USER->id,
					'line_id'=>$out_line,
					'time'=>strtotime($out_date." ".$out_time.":00"),
					'cost'=>$out_summ,
					'comment'=>$out_comment,
					'score_id'=>$score_id,
				));
			if($ins -> rowCount() > 0){
				$out_line = filter_input(INPUT_GET,"line");
				$out_summ = $out_comment = $score_id = NULL;
				$out_date = date("Y-m-d");
				$out_time = date("H:i");
				set_message("Данные добавлены");
			}else{
				set_error("Ошибка добавления данных");
			}
		}else{
			$upd = $DB -> prepare("UPDATE `outgo_data` SET `time`=:time,`line_id`=:line_id,`cost`=:cost,`comment`=:comment,`score_id`=:score_id WHERE `id`=:id");
			$upd -> execute(array(
					'time'=>strtotime($out_date." ".$out_time.":00"),
					'line_id'=>$out_line,
					'cost'=>$out_summ,
					'comment'=>$out_comment,
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
		<label for="out_line">Направление</label>
		%s
	</div>
	<div class="field number">
		<label for="out_summ">Сумма</label>
		<div class="add">+</div>
		<div class="data">
			<input type="number" name="out_summ[]" id="out_summ" value="%s" placeholder="Сумма" step="0.01" required/>
		</div>
	</div>
	<div class="field">
		<label for="out_date">Дата</label>
		<input type="date" name="out_date" id="out_date" value="%s"  required/>
		<input type="time" name="out_time" id="in_time" value="%s" required/>
	</div>
	<div class="field">
		<label for="out_comment">Комментарий</label>
		<input type="text" name="out_comment" id="out_comment" value="%s" placeholder="Комментарий"/>
	</div>
	<div class="field center">
		<input type="submit" value="Сохранить" /> %s
	</div>
	</form>',
	$USER->get_score_select($score_id,'score_id'),
	$USER->get_outgo_select($out_line,'out_line'),
	$out_summ,
	$out_date,
	$out_time,
	$out_comment,
	$button
	);
	if($id > 0){
		printf('<div class="field center"><a href="/?page=start&action=out&record=%s&sub_action=delete" class="button submit" text="Удалить запись">Удалить</a></div>',$id);
	}