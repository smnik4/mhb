<script type="text/javascript" src="/ass_p/jquery.jqplot.js"></script>
<script type="text/javascript" src="/ass_p/plugins/jqplot.pieRenderer.js"></script>
<link rel="stylesheet" type="text/css" href="/ass_p/jquery.jqplot.css" />
<?php
/*Статистика*/
$SELECT_USERS = $USER->id;
$stat = filter_input(INPUT_POST,"stat");
$stat_period = filter_input(INPUT_POST,"stat_period");
$summary = filter_input(INPUT_POST,"summary");
if(!$stat){
	if(isset($_SESSION['stat'])){
		$stat = $_SESSION['stat'];
		if(isset($_SESSION['stat_period'])){
			$stat_period = $_SESSION['stat_period'];
		}
	}else{
		$stat_period = "month";
		$stat = date("Y-m");
	}
}else{
	$_SESSION['stat'] = $stat;
	$_SESSION['stat_period'] = $stat_period;
}
if($stat_period == "year" AND preg_match("/(\d{4})-\d{2}/",$stat,$m)){
	if(isset($m[1])){
		$stat = $m[1];
	}else{
		$stat = date("Y");
	}
}
if($stat_period == "month" AND preg_match("/^(\d{4})$/",$stat,$m)){
	if(isset($m[1])){
		$stat = $m[1] . date("-m");
	}else{
		$stat = date("Y-m");
	}
}

printf('<form method="POST">
	<div class="field">
		<input type="radio" name="stat_period" id="stat_period_m" value="month" %s/>
		<label for="stat_period_m"> Показать за месяц</label>
		<input type="radio" name="stat_period" id="stat_period_y" value="year" %s/>
		<label for="stat_period_y"> Показать за год</label>
	</div>
	<div class="field center">
		<label for="stat">Период</label>
		<input type="%s" name="stat" id="stat" value="%s" required/>
		<input type="submit" value="Показать" />
	</div>
	</form>',
	($stat_period == "month")?" checked":"",
	($stat_period == "year")?" checked":"",
	($stat_period == "year")?"number":"month",
	$stat);

if($stat_period == "month"){
	$times = strtotime($stat."-01 00:01:00");
	$timee = strtotime($stat."-01 00:01:00 +1 month");
}else{
	$times = strtotime($stat."-01-01 00:01:00");
	$timee = strtotime($stat."-01-01 00:01:00 +1 year");
}

$sql = sprintf("SELECT SUM(`cost`) as costs, `line_id` FROM `outgo_data` WHERE `time` >= %s AND `time` < %s AND `user_id`=%u AND `line_id` != 5 GROUP BY `line_id` ORDER BY costs DESC",
	$times,$timee,$SELECT_USERS);
$sel = $DB -> prepare($sql);
$sel -> execute();
$data = $other = array();

while($row = $sel -> fetch()){
	if(count($data)<15){
		$data[$row['line_id']] = $row['costs'];
	}else{
		if(!isset($data[0])){
			$data[0] = 0;
		}
		if(!in_array($row['line_id'],$other)){
			$other[] = $row['line_id'];
		}
		$data[0] += (float)$row['costs'];
	}
}
$lines = $USER->get_outgo_array();
$lines[0] = "Другие расходы";
if(count($data) > 0){
	arsort($data);
	$stat_line = $select = array();
	foreach($data as $line=>$val){
		$line_val = $line;
		if($line == 0){
			$line_val = implode(", ",$other);
		}
		$stat_line[] = sprintf("['<span onclick=\"view_detail(\'%s\',\'summary\')\">%s(%s)</span>',%s]",$line_val,$lines[$line],$val,$val);
	}
	print('<br/><div id="stat_plot"></div><div class="clear"></div>');
	printf("<script>
		$(document).ready(function(){
			var plot1 = $.jqplot('stat_plot', [[%s]], {
				gridPadding: {top:0, bottom:38, left:0, right:0},
				gridDimensions: { height: 400, width: null },
				seriesDefaults:{
					renderer:$.jqplot.PieRenderer, 
					trendline:{ show:false },
					rendererOptions: { padding: 8, showDataLabels: true }
				},
				legend:{
					show:true, 
					placement: 'outside', 
					rendererOptions: {
						numberRows: 5
					}, 
					location:'s',
					marginTop: '15px'
				},
				defaultHeight:'400',
			});
			$('#stat_plot').css('height','auto');
			$('#stat_plot .jqplot-base-canvas').css('position','relative');
			$('#stat_plot .jqplot-table-legend').css({'position':'relative','top':'unset','margin-top':'-45px'});
		});
		</script>",
		implode(",",$stat_line));
	printf('<form method="POST" style="display:none;">
	<div class="field center">
		<label for="summary">Просмотреть подробно</label>
		<input type="hiden" name="summary" id="summary" value="0" />
		<input type="submit" value="Показать" />
	</div>
	</form>',
	implode("",$select));
	if($summary){
		$sql = sprintf("SELECT * FROM `outgo_data` WHERE `time` >= %s AND `time` < %s AND `user_id`=%u AND `line_id` IN (%s) ORDER BY `time` DESC",
		$times,$timee,$SELECT_USERS,$summary);
		$sel = $DB -> prepare($sql);
		$sel -> execute();
		while($row = $sel -> fetch()){
			if(!empty($row['comment'])){
				$row['comment'] = '<comment>'.$row['comment'].'</comment>';
			}
			printf('<div class="histiry_line line_outgo">
					<div class="summ">%s</div>
					<div class="time">%s</div>
					<div class="line">%s</div>
					%s
					<div class="clear"></div>
				</div>',
				number_format($row['cost'],2,".",""),
				date("d.m.Y H:i",$row['time']),
				$lines[$row['line_id']],
				$row['comment']
				);
		}
	}
}else{
	set_error("Записи не найдены");
}
if($stat_period == "year"){
	$labels = $ticks = $data = array();
	
}
?>