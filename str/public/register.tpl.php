<div class="center">Регистрация доступна только по приглашению.</div>
<comment class="center">После успешной регистрации Вы будете перенаправлены на страницу входа.</comment>
<?php
$code = trim(filter_input(INPUT_POST,"code"));
$Nocode = trim(filter_input(INPUT_POST,"Nocode"));
$name = trim(filter_input(INPUT_POST,"name"));
$login = trim(filter_input(INPUT_POST,"login"));
$password = filter_input(INPUT_POST,"password");
$email = trim(filter_input(INPUT_POST,"email",FILTER_VALIDATE_EMAIL));
if(isset($_POST['user_action'])){
	$error = FALSE;
	if($Nocode){
		$code = 'QR9K-30TO-T1QX-ZQ6A';
	}
	if(empty($code)){
		set_error("Код приглашения обязателен");
		$error = TRUE;
	}
	if(empty($name)){
		set_error("Имя не может быть пустым");
		$error = TRUE;
	}
	if(empty($login)){
		set_error("Логин не может быть пустым");
		$error = TRUE;
	}
	if(empty($password)){
		set_error("Пароль не может быть пустым");
		$error = TRUE;
	}
	if(empty($email)){
		set_error("Не валидный адрес почты");
		$error = TRUE;
	}

	if(!$error){
		$sel = $DB -> prepare("SELECT * FROM `user` WHERE `my_code`=:code");
		$sel -> execute(array('code'=>$code));
		if($sel -> rowCount() > 0){
			$tc = $sel -> fetch();
			$code = $tc['my_code'];
			$sel = $DB -> prepare("SELECT * FROM `user` WHERE `login`=:login");
			$sel -> execute(array('login'=>$login));
			if($sel -> rowCount() > 0){
				set_error("Логин занят");
				$error = TRUE;
			}
			$sel = $DB -> prepare("SELECT * FROM `user` WHERE `email`=:email");
			$sel -> execute(array('email'=>$email));
			if($sel -> rowCount() > 0){
				set_error("Такой email уже зарегистрирован");
				$error = TRUE;
			}
			if(!$error){
				$ins = $DB -> prepare("INSERT INTO `user`(`login`, `password`, `name`, `email`, `email_confirm`, `my_code`, `enter_code`) VALUES (:login, MD5(:password), :name, :email, 0, :my_code, :enter_code)");
				$ins -> execute(array(
						'login'=>$login,
						'password'=>$password,
						'name'=>$name,
						'email'=>$email,
						'my_code'=>$USER->generate_serial(),
						'enter_code'=>$code,
					));
				if($ins -> rowCount() > 0){
					$user_id = $DB -> lastInsertId();
					$ins = $DB -> prepare("INSERT INTO `user_score`(`user_id`, `name`) VALUES (:user_id, 'Карта');
					INSERT INTO `user_score`(`user_id`, `name`) VALUES (:user_id, 'Нал.');");
					$ins -> execute(array(
							'user_id'=>$user_id
						));
					header('location: /');
					exit();
				}else{
					set_error("Не удалось зарегистрироваться");
				}
			}
		}else{
			set_error("Не действительный код приглашения");
		}
	}
}
if($Nocode){
	$code = '';
}
printf('<form method="POST">
		<input type="hidden" name="user_action" value="user_register"/>
		<div class="field">
			<label for="code">Код приглашения</label>
			<input type="text" name="code" id="code" value="%s" placeholder="Код"/>
		</div>
		<div class="field">
			<input type="checkbox" name="Nocode" id="Nocode" value="1" %s>
			<label for="Nocode">Нет кода приглашения</label>
		</div>
		<div class="field">
			<label for="name">Ваше имя:</label>
			<input type="text" name="name" id="name" value="%s" required="">
		</div>
		<div class="field">
			<label for="login">Логин</label>
			<input type="text" name="login" id="login" value="%s" placeholder="Логин" required/>
		</div>
		<div class="field">
			<label for="email">E-mail</label>
			<input type="text" name="email" id="email" value="%s" placeholder="mymail@site.ru" required/>
		</div>
		<div class="field">
			<label for="password">Пароль</label>
			<input type="password" name="password" id="password" value=""  placeholder="Пароль" required/>
		</div>
		<div class="field center">
			<input type="submit" value="Регистрация" />
		</div>
	</form>',
	$code,
	($Nocode)?'checked':'',
	$name,
	$login,
	$email
	);