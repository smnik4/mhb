<?php

class THEME{
	private $path = NULL;
	private $HTML = NULL;
	public $errors = array();
	public $user_auth = FALSE;
	public $notitle = FALSE;
	public $public_page = FALSE;
	public $css_compress = FALSE;
	public $js_compress = FALSE;
	
	private $www_dir = FALSE;
	private $current_cache = array();
	private $generate_cache = array();
	private $get_new_cahe = FALSE;
	
	private $input_vars = array();
	
	private $html_path = NULL;
	private $html_theme = NULL;
	private $html_vars = array();
	
	private $page_path = NULL;
	private $page_theme = NULL;
	private $page_vars = array();
	
	private $auth_path = NULL;
	private $auth_theme = NULL;
	private $auth_vars = array();
	
	private $public_path = NULL;
	private $public_theme = NULL;
	private $public_vars = array();
	
	public function __construct($path = FALSE){
		global $GLOBAL;
		$this->www_dir = $GLOBAL->get_value("www_dir");
		$this->HTML = new CFG("thm");
		
		if($this->HTML){
			$dth = $this->HTML->get_value("default_theme");
			if(!empty($dth)){
				$this->path =$this->www_dir ."thm/theme/". $dth ."/";
				$this->set_input_array($this->HTML->get_vars());
			}else{
				$GLOBAL->set_value('errors','Empty default theme name');
			}
		}
		$this->errors = array_merge($this->errors,$this->HTML->errors);
		$this->html_compress = $GLOBAL->get_value("html_compress");
		$this->css_compress = $GLOBAL->get_value("css_compress");
		$this->js_compress = $GLOBAL->get_value("js_compress");
		$this->get_new_cahe = filter_input(INPUT_GET,"reCache",FILTER_VALIDATE_BOOLEAN);
		$site_name = $this->HTML->get_value("site_name");
		if($site_name){
			$start_y = "2017";
			if((INT)date("Y") > (INT)$start_y){
				$start_y .= " - " .date("Y");
			}
			$this->set_input("copyright",sprintf('&copy; %s, %s',$start_y,$site_name));
		}
		if(!empty($this->path)){
			$this->html_path = $this->path."html.tpl.html";
			$this->page_path = $this->path."page.tpl.html";
			$this->auth_path = $this->path."auth.tpl.html";
			$this->public_path = $this->path."public.tpl.html";
			
			if(file_exists($this->html_path)){
				$this->html_theme = trim(file_get_contents($this->html_path));
				if(!empty($this->html_theme)){
					$this->html_vars = $this->find_theme_vars($thml = $this->html_theme);
				}else{
					$this->errors[] = 'Theme HTML is empty';
				}
			}else{
				$this->errors[] = 'Theme HTML not found';
			}
			if(file_exists($this->page_path)){
				$this->page_theme = trim(file_get_contents($this->page_path));
				if(!empty($this->page_theme)){
					$this->page_vars = $this->find_theme_vars($thml = $this->page_theme);
				}else{
					$this->errors[] = 'Theme PAGE is empty';
				}
			}else{
				$this->errors[] = 'Theme PAGE not found';
			}
			if(file_exists($this->auth_path)){
				$this->auth_theme = trim(file_get_contents($this->auth_path));
				if(!empty($this->auth_theme)){
					$this->auth_vars = $this->find_theme_vars($thml = $this->auth_theme);
				}else{
					$this->errors[] = 'Theme AUTH is empty';
				}
			}else{
				$this->errors[] = 'Theme AUTH not found';
			}
			if(file_exists($this->public_path)){
				$this->public_theme = trim(file_get_contents($this->public_path));
				if(!empty($this->public_theme)){
					$this->public_vars = $this->find_theme_vars($thml = $this->public_theme);
				}else{
					$this->errors[] = 'Theme PUBLIC is empty';
				}
			}else{
				$this->errors[] = 'Theme PUBLIC not found';
			}
			$this->get_current_asset_cache();
		}else{
			$this->errors[] = 'empty theme path';
		}
	}
	
	private function find_theme_vars($thml = ''){
		if(preg_match_all("/\{\{[\w\d_-]{1,}\}\}/",$thml,$find)){
			$find = $find[0];
			$res = array();
			foreach($find as $key=>$val){
				$val = str_replace(array("{","}"),"",$val);
				if(!in_array($val,$res)){
					$res[] = $val;
				}
			}
			return $res;
		}else{
			return array();
		}
	}
	 
	public function set_input($var_name,$value = NULL,$add = FALSE){
		if(!empty($var_name)){
			if($add AND isset($this->input_vars[$var_name])){
				if(is_array($this->input_vars[$var_name])){
					$this->input_vars[$var_name][] = $value;
				}else{
					$this->input_vars[$var_name] .= $value;
				}
			}else{
				$this->input_vars[$var_name] = $value;
			}
		}else{
			$this->errors[] = 'Empty var name';
		}
	}
	
	public function set_input_array($array,$add = TRUE){
		foreach($array as $key => $val){
			if($add AND isset($this->input_vars[$key])){
				if(is_array($this->input_vars[$key])){
					$this->input_vars[$key][] = $val;
				}else{
					$this->input_vars[$key] .= $val;
				}
			}else{
				$this->input_vars[$key] = $val;
			}
		}
	}

	public function render(){
		$this->parse_sys_messages();
		if($this->user_auth AND !$this->public_page){
			/*if($this->title === strip_tags($this->title)){
				$this->title = sprintf('<h1 class="center">%s</h1>',$this->title);
			}*/
			foreach($this->page_vars as $var){
				$value = '';
				if(isset($this->input_vars[$var])){
					if($var === "title"){
						if(!$this->notitle){
							$title = $this->input_vars[$var];
							if($this->input_vars[$var] === strip_tags($this->input_vars[$var])){
								$title = sprintf('<h1 class="center">%s</h1>',$this->input_vars[$var]);
							}
							$value = $title;
						}
						
					}else{
						if(is_array($this->input_vars[$var])){
							$value = $this->render_array($this->input_vars[$var]);
						}else{
							$value = $this->input_vars[$var];
						}
					}
				}
				$var = "{{".$var."}}";
				$this->page_theme = str_replace($var,$value,$this->page_theme);
			}
			$this->input_vars['body'] = $this->page_theme;
			$this->input_vars['assets'] = $this->get_assets("page");
		}elseif(!$this->user_auth AND !$this->public_page){
			$this->set_input('title',"Авторизация");
			foreach($this->auth_vars as $var){
				$value = '';
				if(isset($this->input_vars[$var])){
					if(is_array($this->input_vars[$var])){
						$value = $this->render_array($this->input_vars[$var]);
					}else{
						$value = $this->input_vars[$var];
					}
				}
				$var = "{{".$var."}}";
				$this->auth_theme = str_replace($var,$value,$this->auth_theme);
			}
			$this->input_vars['body'] = $this->auth_theme;
			$this->input_vars['assets'] = $this->get_assets("auth");
		}else{
			foreach($this->public_vars as $var){
				$value = '';
				if(isset($this->input_vars[$var])){
					if($var === "title"){
						if(!$this->notitle){
							$title = $this->input_vars[$var];
							if($this->input_vars[$var] === strip_tags($this->input_vars[$var])){
								$title = sprintf('<h1 class="center">%s</h1>',$this->input_vars[$var]);
							}
							$value = $title;
						}
						
					}else{
						if(is_array($this->input_vars[$var])){
							$value = $this->render_array($this->input_vars[$var]);
						}else{
							$value = $this->input_vars[$var];
						}
					}
					
				}
				$var = "{{".$var."}}";
				$this->public_theme = str_replace($var,$value,$this->public_theme);
			}
			$this->input_vars['body'] = $this->public_theme;
			$this->input_vars['assets'] = $this->get_assets("public");
		}
		
		foreach($this->html_vars as $var){
			$value = '';
			if(isset($this->input_vars[$var])){
				if(is_array($this->input_vars[$var])){
					$value = $this->render_array($this->input_vars[$var]);
				}else{
					$value = $this->input_vars[$var];
				}
			}
			$var = "{{".$var."}}";
			$this->html_theme = str_replace($var,$value,$this->html_theme);
		}
		return $this->html_theme;
	}
	
	private function parse_sys_messages(){
		global $ERRORS,$MESSAGES;
		$res = '';
		if(is_array($ERRORS)){
			$ERRORS = $this->clear_array($ERRORS);
			if(count($ERRORS) > 0){
				$res .= $this->render_array($ERRORS, "information error");
			}
		}
		if(is_array($MESSAGES)){
			$MESSAGES = $this->clear_array($MESSAGES);
			if(count($MESSAGES) > 0){
				$res .= $this->render_array($MESSAGES, "information message");
			}
		}
		$this->set_input("messages",$res,TRUE);
	}
	
	private function clear_array($array){
		$res = array();
		if(is_array($array)){
			if(count($array) > 0){
				foreach($array as $key=>$val){
					$val = trim($val);
					if(!empty($val)){
						$res[$key] = $val;
					}
				}
			}
		}
		return $res;
	}
	
	private function render_array($array, $class = FALSE){
		$html = '';
		if(is_array($array)){
			if(count($array) > 0){
				if(!empty($class)){
					$class = sprintf(' class="%s"',$class);
				}
				$html = sprintf('<ul%s>',$class);
				foreach($array as $val){
					$html .= sprintf('<li>%s</li>',$val);
				}
				$html .= '</ul>';
			}
		}
		return $html;
	}
	
	private function get_current_asset_cache(){
		$result = array();
		$path_cahe = $this->www_dir . "ass/";
		if(file_exists($path_cahe)){
			if($dir = scandir($path_cahe)){
				foreach($dir as $key=>$file){
					if(!preg_match("/^\./",$file)){
						$result[] = $file;
					}
				}
			}
		}
		$this->current_cache = $result;
	}

	private function in_asset_cache($file_name = FALSE){
		if(count($this->current_cache) > 0){
			$reg = "/" . $file_name . "/";
			$path_cahe = $this->www_dir . "ass/";
			foreach($this->current_cache as $file){
				$file = str_replace($path_cahe,"",$file);
				if($file_name AND preg_match($reg,$file)){
					return $path_cahe.$file;
				}
			}
		}
		return FALSE;
	}

	private function get_assets($type){
		$html = '';
		$files = array();
		$config_path = str_replace($this->www_dir,"",$this->path);
		$path_cahe = $this->www_dir ."ass/";
		$ASSETS = new CFG($config_path);
		if($ASSETS){
			if(count($ASSETS->errors) == 0){
				$ini_files = array(
						'css'=>array(),
						'js'=>array(),
					);
				$VARS = $ASSETS->get_vars();
				foreach($VARS as $key=>$var){
					if(in_array($key,array('auth','page','public'))){
						foreach($var as $e_type=>$elements){
							$e_type = strtolower($e_type);
							foreach($elements as $element){
								if(isset($ini_files[$e_type])){
									if(!in_array($element,$ini_files[$e_type])){
										$element_path = $this->path . "assets/" . $e_type . "/" .$element;
										if(file_exists($element_path)){
											$ini_files[$e_type][] = $element;
										}
									}
								}
							}
						}
					}
				}
				
				foreach($ini_files as $type_file=>$files_base){
					foreach($files_base as $file){
						$file_path = $this->path . "assets/" . $type_file . "/" .$file;
						$fbsh = md5_file($file_path);
						$file_current = $this->in_asset_cache($fbsh);
						$f_name = $fbsh.".".$type_file;
						if($file_current AND !$this->get_new_cahe){
							$this->generate_cache[] = $f_name;
						}else{
							$file_cache = $path_cahe . $f_name;
							if(copy($file_path,$file_cache)){
								$this->generate_cache[] = $f_name;
								$this->compress_link($file_cache);
							}else{
								$this->errors[] = 'Not write cache file: '.$file;
							}
						}
					}
				}
				$del = array_diff($this->current_cache,$this->generate_cache);
				foreach($del as $dfile){
					$dfile = $path_cahe . $dfile;
					unlink($dfile);
				}
				foreach($VARS as $key=>$var){
					if($type === $key){
						foreach($var as $e_type=>$elements){
							$e_type = strtolower($e_type);
							foreach($elements as $element){
								if(!in_array($element,$files)){
									$file_path = $this->path . "assets/" . $e_type . "/" .$element;
									$fbsh = md5_file($file_path);
									$file_cahe = $path_cahe . $fbsh.".".$e_type;
									$link_cahe = "/ass/" . $fbsh.".".$e_type;
									if(file_exists($file_cahe)){
										switch($e_type){
											case 'css':
												$html .= sprintf('<link rel="stylesheet" type="text/css" href="%s">',$link_cahe);
												break;
											case 'js':
												$html .= sprintf('<script type="text/javascript" src="%s" charset="UTF-8"></script>',$link_cahe);
												break;
										}
									}
								}
							}
						}
					}
				}
			}
		}
		return $html;
	}
	
	private function compress_link($file){
		$search1 = array(
				"\r",
				"\n",
				"	",
			);
		$search2 = array(
				"  ",
			);
		$resplace2 = array(
				" ",
			);
		if(preg_match("/\.css$/",$file) AND $this->css_compress){
			if(file_exists($file)){
				$data = file_get_contents($file);
				$data = str_replace($search1,"",$data);
				$data = str_replace($search2,$resplace2,$data);
				$data = str_replace($search2,$resplace2,$data);
				file_put_contents($file,$data);
			}
		}
		if(preg_match("/\.js$/",$file) AND $this->js_compress){
			if(file_exists($file)){
				$data = file_get_contents($file);
				$data = str_replace($search1,"",$data);
				$data = str_replace($search2,$resplace2,$data);
				$data = str_replace($search2,$resplace2,$data);
				file_put_contents($file,$data);
			}
		}
	}
	
	public function compress_html(){
		$search1 = array(
				"\r",
				"\n",
				"	",
			);
		$search2 = array(
				"  ",
			);
		$resplace2 = array(
				" ",
			);
		$data = '';
		if(ob_get_level() AND $this->html_compress){
			$data = ob_get_clean();
			$data = str_replace($search1,"",$data);
			$data = str_replace($search2,$resplace2,$data);
			$data = str_replace($search2,$resplace2,$data);
		}else{
		}
		return $data;
	}
}