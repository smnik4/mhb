<?php

class PAGE{
	public $errors = array();
	public $menu = FALSE;
	public $notitle = FALSE;
	private $sructure = array();
	private $dir = FALSE;
	private $page = FALSE;
	private $action = FALSE;
	private $user_auth = FALSE;
	public $public_page = FALSE;
	private $page_path = FALSE;
	public $title = 'Страница не найдена';
	public $content = NULL;
	
	public function __construct($user_auth = FALSE){
		global $CURRENT_PAGE,$USER;
		$this->page = $CURRENT_PAGE;
		$this->user_auth = $user_auth;
		$this->sructure = new CFG("str");
		if($this->user_auth){
			if(count($USER->family_users) == 0){
				if(isset($this->sructure->VARS['private']['statgroup'])){
					unset($this->sructure->VARS['private']['statgroup']);
				}
				//debug($this->sructure->VARS['private']);
			}
		}
		$this->errors = $this->sructure->errors;
		if(!$user_auth){
			$this->sructure->set_value("private",array(),FALSE);
		}
		$action_g = filter_input(INPUT_GET,"action");
		$action_p = filter_input(INPUT_POST,"action");
		if(!empty($action_p)){
			$this->action = $action_p;
		}
		if(!empty($action_g)){
			$this->action = $action_g;
		}
		$this->get_page_path();
		$this->generate_menu();
	}
	
	private function generate_menu(){
		$html = '';
		$private = $this->sructure->get_value("private");
		if(count($private) > 0){
			$html .= '<ul>';
			foreach($private as $file=>$title){
				if(in_array($file,array("gain","outgo","score"))){
					continue;
				}
				$html .= sprintf('<li><a href="/?page=%s">%s</a></li>',$file,$title);
			}
			$html .= '</ul>';
		}
		$public = $this->sructure->get_value("public");
		if(count($public) > 0){
			if(count($private) > 0){
				$html .= '<hr />';
			}
			$html .= '<ul>';
			foreach($public as $file=>$title){
				$html .= sprintf('<li><a href="/?page=%s">%s</a></li>',$file,$title);
			}
			$html .= '</ul><hr /><ul>';
			if(!$this->user_auth){
				$html .= '<li><a href="/">Войти</a></li>';
			}else{
				$html .= '<li><a href="/?user_action=logout">Выйти</a></li>';
			}
			$html .= '</ul>';
		}
		$this->menu = $html;
	}
	
	private function get_page_path(){
		global $GLOBAL,$DB,$USER;
		$pages = $all = array();
		$vars = $this->sructure->get_vars();
		if(is_array($vars)){
			foreach($vars as $dir=>$sub_pages){
				if(!isset($pages[$dir])){
					$pages[$dir]= array();
				}
				foreach($sub_pages as $file=>$title){
					if(!in_array($file,$all)){
						$pages[$dir][] = $file;
						$all[] = $file;
						if($this->page === $file){
							$this->dir = $dir;
							$this->title = $title;
						}
					}else{
						$this->errors[] = sprintf('Dublicate page in: %s/%s:%s',$dir,$file,$title);
					}
				}
			}
		}
		
		if(empty($this->dir)){
			switch($this->page){
				case "register":
					$this->dir = 'public';
					$this->title = 'Регистрация';
					break;
			}
		}
		if(!empty($this->dir) AND !empty($this->page)){
			$www = $GLOBAL->get_value("www_dir");
			$file_path = $www ."str/" . $this->dir . "/" . $this->page . ".tpl.php";
			if(file_exists($file_path)){
				if($this->dir === "public"){
					$this->public_page = TRUE;
				}
				$this->page_path = $file_path;
				ob_start();
				require_once $this->page_path;
				$this->content = ob_get_clean();
			}else{
				$this->errors[] = sprintf('Error read page in: %s/%s',$this->dir,$this->page);
			}
		}
		
	}
}