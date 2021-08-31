<?php

class USER{
	public $errors = array();
	public $auth = FALSE;
	public $session_id = 0;
	public $session_data = array();
	public $id = 0;
	public $name = NULL;
	public $ip = NULL;
	public $email = NULL;
	public $email_confirm = FALSE;
	public $family_id = 0;
	public $family_creator = FALSE;
	public $family_key = NULL;
	public $family_users = array();
	public $score = array();
	public $code = NULL;
	
	//session_id()
	public function __construct(){
		if(session_status() === 2){
			$this->session_id = session_id();
			$this->session_data = $_SESSION;
		}
		$this->ip = filter_input(INPUT_SERVER,"REMOTE_ADDR");
		if($this->session_id AND isset($this->session_data['user_id'])){
			$this->check_auth();
		}
		$action = filter_input(INPUT_POST,"user_action");
		if(empty($action)){
			$action = filter_input(INPUT_GET,"user_action");
		}
		switch($action){
			case "user_auth":
				$this->user_auth();
				break;
			case "logout":
				$this->logout();
				break;
				
		}
		if(session_status() === 2){
			$this->session_data = $_SESSION;
		}
		if($this->auth){
			$this->load_family_data();
			$this->get_score();
		}
		
		
	}
	
	private function user_auth(){
		global $DB;
		if($this->auth){
			$this->errors[] = 'Вы авторизовались ранее.';
			return TRUE;
		}
		$login = trim(filter_input(INPUT_POST,"login"));
		$password = filter_input(INPUT_POST,"password");
		if(!empty($login) OR !empty($password)){
			$sel = $DB -> prepare("SELECT * FROM `user` WHERE `login`=:login AND `password`=MD5(:password)");
			$sel -> execute(array('login'=>$login,'password'=>$password));
			if($sel -> rowCount() > 0){
				$data = $sel->fetch();
				//debug($data);
				$this->auth = TRUE;
				$this->id = $data['id'];
				$this->name = $data['name'];
				$this->email = $data['email'];
				$this->code = $data['my_code'];
				if($data['email_confirm']){
					$this->email_confirm = TRUE;
				}
				$this->save_session();
			}else{
				$this->errors[] = 'Неверная комбинация логин - пароль.';
			}
		}else{
			$this->errors[] = 'Логин или пароль не могут быть пустыми.';
		}
	}
	
	private function logout(){
		global $DB;
		$upd = $DB -> prepare("UPDATE `user_sessions` SET `closed`=1 WHERE `session_id`=:session_id");
		$upd -> execute(array(
				'session_id'=>$this->session_id,
			));
		if($upd ->rowCount() == 0){
			$this->errors[] = 'Not close session';
		}
		if ( session_id() ) {
			setcookie(session_name(), session_id(), time()-60*60*24);
			session_unset();
			session_destroy();
		}
		$this->auth =  FALSE;
		$this->id = 0;
		header("location: /");
		exit();
	}
	
	private function save_session(){
		global $DB;
		$sel = $DB -> prepare("SELECT * FROM `user_sessions` WHERE `session_id`=:session_id");
		$sel -> execute(array('session_id'=>$this->session_id));
		if($sel-> rowCount() > 0){
			$upd = $DB -> prepare("UPDATE `user_sessions` SET `user_id`=:user_id,`time_login`=:time_login,`user_ip`=:user_ip,`closed`=0 WHERE `session_id`=:session_id");
			$upd -> execute(array(
					'user_id'=>$this->id,
					'time_login'=>time(),
					'user_ip'=>$this->ip,
					'session_id'=>$this->session_id,
				));
			if($upd ->rowCount() == 0){
				$this->errors[] = 'Not update session';
			}
		}else{
			$ins = $DB -> prepare("INSERT INTO `user_sessions`(`session_id`, `user_id`, `time_login`, `user_ip`) VALUES (:session_id, :user_id, :time_login, :user_ip)");
			$ins -> execute(array(
					'session_id'=>$this->session_id,
					'user_id'=>$this->id,
					'time_login'=>time(),
					'user_ip'=>$this->ip,
				));
			if($ins ->rowCount() == 0){
				$this->errors[] = 'Not set session';
			}
		}
		
		if(session_status() === 2){
			$_SESSION['user_id'] = $this->id;
		}
	}
	
	private function check_auth(){
		global $DB;
		$sel = $DB -> prepare("SELECT * FROM `user_sessions` WHERE `session_id`=:session_id AND `user_id`=:user_id AND `time_login`>:time_login AND `closed`=0");
		$sel -> execute(array(
				'session_id'=>$this->session_id,
				'user_id'=>$this->session_data['user_id'],
				'time_login'=>(time() - (60*60*24*7)),
				//'user_ip'=>$this->ip,
			));
		if($sel->rowCount() > 0){
			$sel = $DB -> prepare("SELECT * FROM `user` WHERE `id`=:id");
			$sel -> execute(array('id'=>$this->session_data['user_id']));
			if($sel -> rowCount() > 0){
				$data = $sel->fetch();
				$this->auth = TRUE;
				$this->id = $data['id'];
				$this->name = $data['name'];
				$this->email = $data['email'];
				$this->code = $data['my_code'];
				if($data['email_confirm']){
					$this->email_confirm = TRUE;
				}
			}
		}
	}
	
	public function create_famaly(){
		global $DB;
		$key = $this->generate_family_serial();
		if($key){
			$ins = $DB -> prepare("INSERT INTO `user_family`(`user_id`, `serial`) VALUES (:user_id,:serial)");
			$ins -> execute(array(
				'user_id'=>$this->id,
				'serial'=>$key,
				));
			if($ins -> rowCount() > 0){
				$this->load_family_data();
				set_message("Группа успешно создана");
				$ins = $DB -> prepare("INSERT INTO `user_score`(`user_id`, `name`, `group`) VALUES (:user_id, 'Общий', 1);");
				$ins -> execute(array(
						'user_id'=>$this->id
					));
				return TRUE;
			}else{
				$this->errors[] = 'Не удалось создать группу';
			}
		}else{
			$this->errors[] = 'Не удалось создать идентификатор';
		}
		return FALSE;
	}
	
	public function connect_famaly($key){
		global $DB;
		if(!empty($key)){
			$sel = $DB -> prepare("SELECT * FROM `user_family` WHERE `serial`=:serial");
			$sel -> execute(array('serial'=>$key));
			if($sel->rowCount() > 0){
				$data = $sel-> fetch();
				$ins = $DB -> prepare("INSERT INTO `user_family_assign`(`user_id`, `family_id`) VALUES (:user_id,:family_id)");
				$ins -> execute(array(
					'user_id'=>$this->id,
					'family_id'=>$data['id'],
					));
				if($ins -> rowCount() > 0){
					return TRUE;
				}else{
					$this->errors[] = 'Не удалось подключиться к группе';
				}
			}else{
				$this->errors[] = 'Идентификатор не найден';
			}
		}else{
			$this->errors[] = 'Не корректный идентификатор';
		}
	}
	
	public function disconnect_famaly($user_id){
		global $DB;
		if(!$user_id AND !$this->family_creator){
			$user_id = $this->id;
		}
		if(!in_array($user_id,$this->family_users)){
			$this->errors[] = 'Пользователь отключен ранее или не был подключен';
			return FALSE;
		}
		if($user_id > 0){
			$sel = $DB -> prepare("SELECT * FROM `user_family_assign` WHERE `user_id`=:user_id AND `family_id`=:family_id");
			$sel -> execute(array(
				'user_id'=>$user_id,
				'family_id'=>$this->family_id,
				));
			if($sel->rowCount() > 0){
				$data = $sel-> fetch();
				$del = $DB -> prepare("DELETE FROM `user_family_assign` WHERE `id`=:id");
				$del -> execute(array(
					'id'=>$data['id'],
					));
				if($del -> rowCount() > 0){
					return TRUE;
				}else{
					$this->errors[] = 'Не удалось отключить пользователя';
				}
			}else{
				$this->errors[] = 'Пользователь отключен ранее или не был подключен';
			}
		}else{
			$this->errors[] = 'Не корректный идентификатор пользователя';
		}
		return FALSE;
	}
	
	private function generate_family_serial(){
		global $DB;
		$serial = $this->generate_serial();
		$i = 0;
		$check = FALSE;
		while($i < 5 AND !$check){
			$i++;
			$sel = $DB -> prepare("SELECT * FROM `user_family` WHERE `serial`=:serial");
			$sel -> execute(array('serial'=>$serial));
			if($sel ->rowCount() == 0){
				$check = TRUE;
				break;
			}else{
				$serial = $this->generate_serial();
			}
		}
		if($check){
			return $serial;
		}
		return FALSE;
	}
	
	public function generate_serial(){
		$str='1234567890ABCDEFIJKLMNOPQRSTUVWXYZ';
		$code_length=16;
		$codes_count=1;
		$code_separartor=4;
		$str_length=strlen($str)-1;
		$code='';
		for ($i=0; $i<$code_length; $i++){
			if ($i>0 && $code_separartor>0 && $i%$code_separartor==0) {
				$code.='-';
			}
			$code.=substr($str, mt_rand(0,$str_length), 1);
		}
		return $code;
	}
	
	private function load_family_data(){
		global $DB;
		if($this->id > 0 AND $this->auth){
			$sel = $DB -> prepare("SELECT * FROM `user_family` WHERE `user_id`=:user_id");
			$sel -> execute(array('user_id'=>$this->id));
			if($sel->rowCount() > 0){
				$this->family_creator = TRUE;
				$data = $sel->fetch();
				$this->family_id = $data['id'];
				$this->family_key = $data['serial'];
				if(!in_array($data['user_id'],$this->family_users)){
					$this->family_users[] = $data['user_id'];
				}
			}else{
				$sel = $DB -> prepare("SELECT * FROM `user_family_assign` WHERE `user_id`=:user_id");
				$sel -> execute(array('user_id'=>$this->id));
				if($sel->rowCount()){
					$data = $sel->fetch();
					$this->family_id = $data['family_id'];
					if(!in_array($data['user_id'],$this->family_users)){
						$this->family_users[] = $data['user_id'];
					}
					$sel = $DB -> prepare("SELECT * FROM `user_family` WHERE `id`=:id");
					$sel -> execute(array('id'=>$this->family_id));
					$data = $sel->fetch();
					if(!in_array($data['user_id'],$this->family_users)){
						$this->family_users[] = $data['user_id'];
					}
				}
			}
			if($this->family_id){
				$sel = $DB -> prepare("SELECT * FROM `user_family_assign` WHERE `family_id`=:family_id");
				$sel -> execute(array('family_id'=>$this->family_id));
				if($sel->rowCount() > 0){
					while($row = $sel -> fetch()){
						if(!in_array($row['user_id'],$this->family_users)){
							$this->family_users[] = $row['user_id'];
						}
					}
				}
			}
		}
	}
	
	public function get_family_users(){
		global $DB;
		$res = array();
		if(count($this->family_users) > 0){
			$sql = sprintf("SELECT `id`,`name` FROM `user` WHERE `id` IN (%s)",
				implode(",",$this->family_users));
			$sel = $DB -> prepare($sql);
			$sel -> execute();
			while($row = $sel -> fetch()){
				$res[$row['id']] = $row['name'];
			}
		}
		return $res;
	}
	
	public function get_family_user_list(){
		global $DB;
		$html = 'Пользователи не найдены';
		if(count($this->family_users) > 0){
			$html = '<ul>';
			$list = array_diff($this->family_users,array($this->id));
			$sel = $DB -> prepare("SELECT `id`,`name` FROM `user` WHERE `id` IN (:ids)");
			$sel -> execute(array('ids'=>implode(",",$list)));
			while($row = $sel -> fetch()){
				$disconnect = '';
				if($this->family_creator){//
					$disconnect = sprintf('&emsp;<a href="/?page=settins&thisaction=disconnect&userid=%s" class="button small submit" text="Отключить пользователя?">Отключить</a>',$row['id']);
				}
				$html .= sprintf('<li>%s%s</li>',$row['name'],$disconnect);
			}
			$html .= '</ul>';
		}
		return $html;
	}
	
	public function switch_data($name = NULL,$email = NULL){
		global $DB;
		if($name === $this->name AND $email === $this->email){
			return TRUE;
		}
		if(empty($name)){
			$this->errors[] = 'Поле "Ваше имя" не может быть пустым';
		}
		$mail_error = FALSE;
		if(empty($email)){
			$this->errors[] = 'Поле "E-mail" не может быть пустым';
			$mail_error = TRUE;
		}elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){
			$this->errors[] = 'Поле "E-mail" заполнено не корректно';
			$mail_error = TRUE;
		}
		if(!empty($name) AND !$mail_error){
			$upd = $DB -> prepare("UPDATE `user` SET `name`=:name, `email`=:email, `email_confirm`=0 WHERE `id`=:id");
			$upd -> execute(array(
				'name'=>$name,
				'email'=>$email,
				'id'=>$this->id,
				));
			if($upd->rowCount() > 0){
				return TRUE;
			}else{
				$this->errors[] = 'Не удалось обновить данные';
				return FALSE;
			}
		}
		return FALSE;
	}
	
	public function switch_pass($p = NULL,$p1 = NULL,$p2 = NULL){
		global $DB;
		$cp = $this->current_password($p);
		if(!$cp){
			$this->errors[] = 'Не корректный текущий пароль';
			return FALSE;
		}
		if($p1 !== $p2){
			$this->errors[] = 'Новый пароль не совпадает с подтверждением';
			return FALSE;
		}elseif(strlen($p1) < 6){
			$this->errors[] = 'Слишком короткий пароль';
			return FALSE;
		}
		
		if($cp AND $p1 === $p2){
			$upd = $DB -> prepare("UPDATE `user` SET `password`=MD5(:password) WHERE `id`=:id");
			$upd -> execute(array(
				'password'=>$p1,
				'id'=>$this->id,
				));
			if($upd->rowCount() > 0){
				$upd = $DB -> prepare("UPDATE `user_sessions` SET `time_login`=0 WHERE `user_id`=:user_id");
				$upd -> execute(array(
					'user_id'=>$this->id,
					));
				return TRUE;
			}else{
				$this->errors[] = 'Не удалось обновить данные';
				return FALSE;
			}
		}
		return FALSE;
	}
	
	private function current_password($p){
		global $DB;
		$sel = $DB -> prepare("SELECT * FROM `user` WHERE `id`=:id AND `password`=MD5(:password)");
		$sel -> execute(array(
				'id'=>$this->id,
				'password'=>$p,
			));
		if($sel -> rowCount() > 0){
			return TRUE;
		}
		return FALSE;
	}
	
	public function fast_stat(){
		global $DB;
		$time_m = strtotime(date("Y-m-01 00:00:00"));
		$time_y = strtotime(date("Y-01-01 00:00:00"));
		$res = array(
				'month_in'=>0,
				'month_out'=>0,
				'year_in'=>0,
				'year_out'=>0,
				'all_in'=>0,
				'all_out'=>0,
				'balance'=>array(),
			);
		$sql = sprintf("SELECT SUM(`cost`) as cost FROM `outgo_data` WHERE  `time`>%s AND(`user_id`=%s OR `score_id` IN (%s))",
			$time_m,
			$this->id,
			implode(",",$this->score));
		$sel = $DB -> prepare($sql);
		$sel -> execute();
		if($sel -> rowCount() > 0){
			$data = $sel -> fetch();
			if(isset($data['cost'])){
				$res['month_out'] = (float)$data['cost'];
			}
		}
		$sql = sprintf("SELECT SUM(`cost`) as cost FROM `input_data` WHERE  `time`>%s AND(`user_id`=%s OR `score_id` IN (%s))",
			$time_m,
			$this->id,
			implode(",",$this->score));
		$sel = $DB -> prepare($sql);
		$sel -> execute();
		if($sel -> rowCount() > 0){
			$data = $sel -> fetch();
			if(isset($data['cost'])){
				$res['month_in'] = (float)$data['cost'];
			}
		}
		$sql = sprintf("SELECT SUM(`cost`) as cost FROM `outgo_data` WHERE  `time`>%s AND(`user_id`=%s OR `score_id` IN (%s))",
			$time_y,
			$this->id,
			implode(",",$this->score));
		$sel = $DB -> prepare($sql);
		$sel -> execute();
		if($sel -> rowCount() > 0){
			$data = $sel -> fetch();
			if(isset($data['cost'])){
				$res['year_out'] = (float)$data['cost'];
			}
		}
		$sql = sprintf("SELECT SUM(`cost`) as cost FROM `input_data` WHERE  `time`>%s AND(`user_id`=%s OR `score_id` IN (%s))",
			$time_y,
			$this->id,
			implode(",",$this->score));
		$sel = $DB -> prepare($sql);
		$sel -> execute();
		if($sel -> rowCount() > 0){
			$data = $sel -> fetch();
			if(isset($data['cost'])){
				$res['year_in'] = (float)$data['cost'];
			}
		}
		$sql = sprintf("SELECT SUM(`cost`) as cost FROM `outgo_data` WHERE `user_id`=%s OR `score_id` IN (%s)",
			$this->id,
			implode(",",$this->score));
		$sel = $DB -> prepare($sql);
		$sel -> execute();
		if($sel -> rowCount() > 0){
			$data = $sel -> fetch();
			if(isset($data['cost'])){
				$res['all_out'] = (float)$data['cost'];
			}
		}
		$sql = sprintf("SELECT SUM(`cost`) as cost FROM `input_data` WHERE `user_id`=%s OR `score_id` IN (%s)",
			$this->id,
			implode(",",$this->score));
		$sel = $DB -> prepare($sql);
		$sel -> execute();
		if($sel -> rowCount() > 0){
			$data = $sel -> fetch();
			if(isset($data['cost'])){
				$res['all_in'] = (float)$data['cost'];
			}
		}
		$sc = $this->get_score();
		foreach($sc as $key=>$val){
			$in = $out = 0;
			$sel = $DB -> prepare("SELECT SUM(`cost`) as cost FROM `outgo_data` WHERE  `score_id`=:score_id");
			$sel -> execute(array('score_id'=>$key));
			if($sel -> rowCount() > 0){
				$data = $sel -> fetch();
				if(isset($data['cost'])){
					$out = (float)$data['cost'];
				}
			}
			$sel = $DB -> prepare("SELECT SUM(`cost`) as cost FROM `input_data` WHERE  `score_id`=:score_id");
			$sel -> execute(array('score_id'=>$key));
			if($sel -> rowCount() > 0){
				$data = $sel -> fetch();
				if(isset($data['cost'])){
					$in = (float)$data['cost'];
				}
			}
			$res['balance'][$key] = array(
					'name'=>$val,
					'value'=>$in - $out,
				);
		}
		return $res;
	}
	
	public function get_outgo_array(){
		global $DB;
		$res = array();
		$sel = $DB -> prepare("SELECT * FROM `outgo_line` WHERE `system`=1 OR (`system`=0 AND `id` IN (SELECT `line_id` FROM `outgo_assign` WHERE `user_id`=:user_id)) ORDER BY `name`");
		$sel -> execute(array(
				'user_id'=>$this->id,
			));
		while($row = $sel->fetch()){
			$res[$row['id']] = $row['name'];
		}
		return $res;
	}
	
	public function get_outgo_array_group(){
		global $DB;
		$res = array();
		$sql = sprintf("SELECT * FROM `outgo_line` WHERE `system`=1 OR (`system`=0 AND `id` IN (SELECT `line_id` FROM `outgo_assign` WHERE `user_id` IN (%s) GROUP BY `line_id`)) ORDER BY `name`",
			implode(", ",$this->family_users));
		$sel = $DB -> prepare($sql);
		$sel -> execute();
		while($row = $sel->fetch()){
			$res[$row['id']] = $row['name'];
		}
		return $res;
	}
	
	public function get_outgo_select($selected = FALSE, $name = 'out_line'){
		$html = sprintf('<select name="%s" name="%s" required>',$name,$name);
		$array = $this -> get_outgo_array();
		foreach($array as $key=>$val){
			$html .= sprintf('<option value="%s"%s>%s</option>',
				$key,
				($selected == $key)?" selected":'',
				$val);
		}
		$html .= '</select>';
		return $html;
	}
	
	public function get_gain_array(){
		global $DB;
		$res = array();
		$sel = $DB -> prepare("SELECT * FROM `input_line` WHERE `system`=1 OR (`system`=0 AND `id` IN (SELECT `line_id` FROM `input_assign` WHERE `user_id`=:user_id)) ORDER BY `name`");
		$sel -> execute(array(
				'user_id'=>$this->id,
			));
		while($row = $sel->fetch()){
			$res[$row['id']] = $row['name'];
		}
		return $res;
	}
	
	public function get_gain_select($selected = FALSE, $name = 'in_line'){
		$html = sprintf('<select name="%s" name="%s" required>',$name,$name);
		$array = $this -> get_gain_array();
		foreach($array as $key=>$val){
			$html .= sprintf('<option value="%s"%s>%s</option>',
				$key,
				($selected == $key)?" selected":'',
				$val);
		}
		$html .= '</select>';
		return $html;
	}
	
	public function get_score(){
		global $DB;
		$res = array();
		//debug($this);
		if(count($this->family_users) > 0){
			$sql = sprintf("SELECT * FROM `user_score` WHERE `user_id`=%s OR (`user_id` IN (%s) AND `group`=1) ORDER BY `weight`,`group`,`name`",
				$this->id,
				implode(", ",$this->family_users)
				);
		}else{
			$sql = sprintf("SELECT * FROM `user_score` WHERE `user_id`=%s ORDER BY `weight`,`group`,`name`",
				$this->id
				);
		}
		
		$sel = $DB -> prepare($sql);
		$sel -> execute();
		while($row = $sel->fetch()){
			$res[$row['id']] = $row['name'];
			$this->score[] = $row['id'];
		}
		return $res;
	}
	
	public function get_score_select($selected = FALSE, $name = 'score_id'){
		$html = sprintf('<select name="%s" name="%s" required>',$name,$name);
		$array = $this -> get_score();
		foreach($array as $key=>$val){
			$html .= sprintf('<option value="%s"%s>%s</option>',
				$key,
				($selected == $key)?" selected":'',
				$val);
		}
		$html .= '</select>';
		return $html;
	}
	
	public function get_in_date_summ($time,$score_id = 0){
		global $DB;
		$in = $out = 0;
		$sc = $this->get_score();
		if($score_id == 0 OR !isset($sc[$score_id])){
			$sel1 = $DB -> prepare("SELECT SUM(`cost`) as cost FROM `outgo_data` WHERE `user_id`=:user_id AND `time`<:time");
			$sel1 -> execute(array('user_id'=>$this->id,'time'=>$time));
			$sel2 = $DB -> prepare("SELECT SUM(`cost`) as cost FROM `input_data` WHERE `user_id`=:user_id AND `time`<:time");
			$sel2 -> execute(array('user_id'=>$this->id,'time'=>$time));
		}else{
			$sel1 = $DB -> prepare("SELECT SUM(`cost`) as cost FROM `outgo_data` WHERE `user_id`=:user_id AND `time`<:time AND score_id=:score_id");
			$sel1 -> execute(array('user_id'=>$this->id,'time'=>$time,'score_id'=>$score_id));
			$sel2 = $DB -> prepare("SELECT SUM(`cost`) as cost FROM `input_data` WHERE `user_id`=:user_id AND `time`<:time AND score_id=:score_id");
			$sel2 -> execute(array('user_id'=>$this->id,'time'=>$time,'score_id'=>$score_id));
		}
		
		if($sel1 -> rowCount() > 0){
			$data = $sel1 -> fetch();
			if(isset($data['cost'])){
				$out = (float)$data['cost'];
			}
		}
		if($sel2 -> rowCount() > 0){
			$data = $sel2 -> fetch();
			if(isset($data['cost'])){
				$in = (float)$data['cost'];
			}
		}
		return ($in - $out);
	}
}