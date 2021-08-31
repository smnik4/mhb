<?php
$line_input = filter_input(INPUT_POST,"line_input");
$line_input = trim($line_input);
if(!empty($line_input)){
	$sel = $DB -> prepare("SELECT * FROM `outgo_line` WHERE `name`=:name LIMIT 1");
	$sel -> execute(array('name'=>$line_input));
	$line_id = FALSE;
	if($sel -> rowCount() > 0){
		$data = $sel -> fetch();
		if($data['system'] == 1){
			set_error("Направление подключено по умолчанию");
		}else{
			$line_id = $data['id'];
		}
	}else{
		$ins = $DB -> prepare("INSERT INTO `outgo_line`(`name`, `system`) VALUES (:name,0)");
		$ins -> execute(array('name'=>$line_input));
		$line_id = $DB->lastInsertId();
	}
	if($line_id > 0){
		$sel = $DB -> prepare("SELECT * FROM `outgo_assign` WHERE `line_id`=:line_id AND `user_id`=:user_id");
		$sel -> execute(array('line_id'=>$line_id,'user_id'=>$USER->id));
		if($sel->rowCount() == 0){
			$ins = $DB -> prepare("INSERT INTO `outgo_assign`(`line_id`, `user_id`) VALUES (:line_id, :user_id)");
			$ins -> execute(array('line_id'=>$line_id,'user_id'=>$USER->id));
			if($ins -> rowCount() > 0){
				set_message("Направление успешно добавлено");
				$line_input = null;
			}else{
				set_error("Не удалось добавить направление");
			}
		}else{
			set_error("У Вас уже добавлено направление");
		}
	}else{
		set_error("Не удалось добавить направление");
	}
}

printf('<form method="POST" name="add_user_gain">
		<div class="field">
			<label for="line_input">Наименование</label>
			<input type="text" name="line_input" id="line_input" value="%s" placeholder="Наименование" required/>
		</div>
		
		<div class="field center">
			<input type="submit" value="Добавить" /> <a href="/?page=outgo" class="button">Назад</a>
		</div>
		</form>',
		$line_input);
	