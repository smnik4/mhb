<?php
$period = filter_input(INPUT_POST,"month");
$showtype = filter_input(INPUT_POST,"showtype");
if(!$period){
	if(isset($_SESSION['period'])){
		$period = $_SESSION['period'];
	}else{
		$period = date("Y-m");
	}
}else{
	$_SESSION['period'] = $period;
}
if(!$showtype){
	if(isset($_SESSION['showtype'])){
		$showtype = $_SESSION['showtype'];
	}else{
		$showtype ='lines';
	}
}else{
	$_SESSION['showtype'] = $showtype;
}
printf('<form method="POST">
	<div class="field center">
		<label for="month">Период</label>
		<input type="month" name="month" id="month" value="%s" required/>
	</div>
	<div class="field line row2">
		
		<input type="radio" name="showtype" id="showtype_lines" value="lines"%s/>
		<label for="showtype_lines">История</label>
	</div>
	<div class="field line row2">
		
		<input type="radio" name="showtype" id="showtype_table" value="table"%s/>
		<label for="showtype_table">Выписка</label>
	</div>
	<div class="field center">
		<input type="submit" value="Показать" />
	</div>
	</form>',
	$period,
	($showtype == 'lines')?' checked':'',
	($showtype == 'table')?' checked':''
	);
$data = array();

$times = strtotime($period."-01 00:01:00");
$timee = strtotime($period."-01 00:01:00 +1 month");

$sel = $DB -> prepare("SELECT * FROM `input_data` WHERE `time` >= :times AND `time` < :timee AND `user_id`=:user_id");
$sel -> execute(array(
	'times'=>$times,
	'timee'=>$timee,
	'user_id'=>$USER->id,
	));
while($row = $sel -> fetch()){
	$row['action'] = 'in';
	$data[] = $row;
}
$sel = $DB -> prepare("SELECT * FROM `outgo_data` WHERE `time` >= :times AND `time` < :timee AND `user_id`=:user_id");
$sel -> execute(array(
	'times'=>$times,
	'timee'=>$timee,
	'user_id'=>$USER->id,
	));
while($row = $sel -> fetch()){
	$row['action'] = 'out';
	$data[] = $row;
}
if($showtype == 'table'){
	usort($data,"kdsortd");
}else{
	usort($data,"kdsort");
}

$lines_in = $USER->get_gain_array();
$lines_out = $USER->get_outgo_array();
if(count($data)>0){
	$sc = $USER->get_score();
	if($showtype == 'table'){
		printf('<table border="1" class="history" cellspacing="0">
			<tr><th>%s</th>',date("d.m.Y",$times));
		$tn = array();
		$nn = 0;
		foreach($sc as $key=>$val){
			$nn++;
			$in = $USER->get_in_date_summ($times,$key);
			printf('<th>%s</th><th>%s</th>',$val,number_format($in,2,".",""));
			$tn[$key] = array(
					'num'=>$nn,
					'summ'=>$in,
				);
		}
		echo '</tr>';
	}
	foreach($data as $record){
		$class = '';
		$edit = '';
		$line = '';
		switch($record['action']){
			case 'in':
				$class = 'line_input';
				$edit = sprintf('/?page=start&action=in&record=%s"',$record['id']);
				if(isset($lines_in[$record['line_id']])){
					$line = $lines_in[$record['line_id']];
				}
				break;
			case 'out':
				$class = 'line_outgo';
				$edit = sprintf('/?page=start&action=out&record=%s',$record['id']);
				if(isset($lines_out[$record['line_id']])){
					$line = $lines_out[$record['line_id']];
				}
				break;
		}
		if(!empty($record['comment'])){
			$record['comment'] = sprintf('<comment>%s</comment>',$record['comment']);
		}
		if($showtype == 'table'){
			printf('<tr class="%s">',$class);
			printf('<td><a href="%s">%s<br/>%s%s</a></td>',
				$edit,
				date("d.m.Y H:i",
				$record['time']),
				$line,
				$record['comment']);
			if($record['action'] == "in"){
				$tn[$record['score_id']]['summ'] += $record['cost'];
			}else{
				$tn[$record['score_id']]['summ'] -= $record['cost'];
			}
			foreach($sc as $key=>$val){
				if($key == $record['score_id']){
					printf('<td class="summ">%s%s</td><td class="summ">%s</td>',
						($record['action'] == "in")?'+':'-',
						$record['cost'],
						number_format($tn[$record['score_id']]['summ'],2,".",""));
				}else{
					echo '<td colspan="2"></td>';
				}
			}
			echo '</tr>';
		}else{
			printf('<div class="histiry_line %s">
				<a href="%s">
					<div class="summ">%s</div>
					<div class="time">%s</div>
					<div class="score">%s</div>
					<div class="line">%s</div>
					%s
					<div class="clear"></div>
				</a>
				</div>',
				$class,
				$edit,
				number_format($record['cost'],2,".",""),
				date("d.m.Y H:i",$record['time']),
				$sc[$record['score_id']],
				$line,
				$record['comment']
				);
		}
		
	}
	if($showtype == 'table'){
		echo '<tr><th>ИТОГО</th>';
		foreach($sc as $key=>$val){
			printf('<th>%s</th><th>%s</th>',$val,number_format($tn[$key]['summ'],2,".",""));
		}
		echo '</tr>';
		echo '</table>';
	}
}else{
	set_error("Записи не найдены"); 
}
