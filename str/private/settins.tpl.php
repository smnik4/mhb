<?php
$family_key = filter_input(INPUT_POST,"family_key");
$thisaction = filter_input(INPUT_GET,"thisaction");

if(!empty($family_key)){
	if(preg_match("/^[0-9A-Z]{4}-[0-9A-Z]{4}-[0-9A-Z]{4}-[0-9A-Z]{4}$/",$family_key)){
		$thisaction = "connect_to_family";
	}else{
		set_error("Не верный формат идентификатора");
		$family_key = '';
	}
	
}

switch($thisaction){
	case "create_family":
		if($USER->create_famaly()){
			header("location: /?page=settins");
			exit();
		}
		break;
	case 'connect_to_family':
		if($USER->connect_famaly($family_key)){
			header("location: /?page=settins");
			exit();
		}
		break;
	case 'disconnect':
		$user_id = filter_input(INPUT_GET,"userid",FILTER_VALIDATE_INT);
		if($USER->disconnect_famaly($user_id)){
			header("location: /?page=settins");
			exit();
		}
		break;
	case 'switch_data':
		$name = trim(filter_input(INPUT_POST,"name"));
		$email = trim(filter_input(INPUT_POST,"email"));
		if($USER->switch_data($name,$email)){
			header("location: /?page=settins");
			exit();
		}
		break;
	case 'switch_pass':
		$passt = filter_input(INPUT_POST,"passt");
		$pass = filter_input(INPUT_POST,"pass");
		$passcр = filter_input(INPUT_POST,"passcр");
		if($USER->switch_pass($passt,$pass,$passcр)){
			header("location: /?page=settins");
			exit();
		}
		break;
}
?>
<div class="center field">
	<a href="/?page=gain" class="button">Источники доходов</a>
	<a href="/?page=outgo" class="button">Направления расходов</a>
	<a href="/?page=score" class="button">Счета</a>
</div>
<?php
printf('
	<div class="field">
		<label for="key">Код приглашения:</label>
		<div class="center"><input class="center" type="text" id="key"  value="%s" readonly /></div>
	</div>
	<comment>Передайте этот идентификатор другим людям для регистрации</comment>',
	$USER->code);

print '<h2 class="center">Учетная запись</h2>';
printf('
	<div class="field">
		<form method="POST" action="/?page=settins&thisaction=switch_data">
			<div class="field">
				<label for="name">Ваше имя:</label>
				<input type="text" name="name" id="name"  value="%s" required />
				<comment>Ваше имя будут видеть участники группы при подключении</comment>
			</div>
			<div class="field">
				<label for="email">E-mail:</label>
				<input type="text" name="email" id="email"  value="%s" required />
			</div>
			<div class="field center">
				<input type="submit" value="Сохранить" />
			</div>
		</form>
	</div>',
	$USER->name,
	$USER->email);

print '<h2 class="center">Пароль</h2>';
printf('
	<div class="field">
		<form method="POST" action="/?page=settins&thisaction=switch_pass">
			<div class="warning">Заполните поля для смены пароля.<br/>Внимание: после смены пароля авторизация на всех устройствах будет аннулирована!</div>
			<div class="field">
				<label for="passt">Текущий пароль:</label>
				<input type="password" name="passt" id="pass" placeholder="Пароль" required />
			</div>
			<div class="field">
				<label for="pass">Новый пароль:</label>
				<input type="password" name="pass" id="pass" placeholder="Пароль" required />
				<input type="password" name="passcр" id="passc" placeholder="Подтверждение" required />
				<comment>Мнимум 6 символов</comment>
			</div>
			<div class="field center">
				<input type="submit" value="Сохранить" />
			</div>
		</form>
	</div>');

print '<h2 class="center">Группа</h2>';
if($USER->family_id == 0){
	printf('
	<div class="center field">
		<a href="/?page=settins&thisaction=create_family" class="button">Создать идентификатор группы</a>
		<br />
		или
		<br />
		<form method="POST">
			<h3>Подключиться к группе</h3>
			<input type="text" name="family_key" id="family_key" value="%s" placeholder="Идентификатор группы"/>
			<input type="submit" value="Присоедениться" />
		</form>
	</div>',
	$family_key);
}
if($USER->family_id > 0 AND $USER->family_creator){
	printf('
	<div class="field">
		<label for="family_key">Идентификатор группы:</label>
		<input class="center" type="text" id="family_key"  value="%s" readonly />
	</div>
	<p>Передайте этот идентификатор учасникам группы для присоединения к ней.</p>
	<h3>Пользователи в группе:</h3>',
	$USER->family_key);
	if(count($USER->family_users) > 1){
		print $USER->get_family_user_list();
	}else{
		print '<p>В этой группе состоите только Вы.</p>';
	}
}

if($USER->family_id > 0 AND !$USER->family_creator){
	print '<p>Вы подключены к группе.</p>
	<p class="center"><a href="/?page=settins&thisaction=disconnect" class="button submit" text="Подтвердите отключение">Отключиться</a></p>
	<h3>Пользователи в группе:</h3>';
	if(count($USER->family_users) > 1){
		print $USER->get_family_user_list();
	}
}