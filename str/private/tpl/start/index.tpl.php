<?php
$this->notitle = TRUE;
/*Статистика*/
$month_in = $month_out = $year_in = $year_out = $all_in = $all_out = 0;
$stat = $USER->fast_stat();
//debug($stat);
$balance = $balancet = '';
if(count($stat['balance']) > 0){
	foreach($stat['balance'] as $val){
		if(empty($balance)){
			$balance = sprintf('<td colspan="2">%s</td><td>%s</td>',$val['name'],number_format($val['value'],2));
		}else{
			$balancet .= sprintf('<tr><td colspan="2">%s</td><td>%s</td></tr>',$val['name'],number_format($val['value'],2));
		}
	}
}
if(empty($balance)){
	$balance = '<td colspan="2"></td>';
}
printf('<div class="start_stat">
	<table border="0" cellspacing="0" cellpadding="5" class="history">
	<tr>
		<td></td>
		<th>Месяц</th>
		<th>Год</th>
		<th>Всего</th>
	</tr>
	<tr class="line_green">
		<th>Пришло</th>
		
		<td>%s</td>
		<td>%s</td>
		<td>%s</td>
	</tr>
	<tr class="line_red">
		<th>Ушло</th>
		<td>%s</td>
		<td>%s</td>
		<td>%s</td>
	</tr>
	<tr>
		<th rowspan="%s">Осталось</th>
		%s
	</tr>
	%s
</table></div>',
	number_format($stat['month_in'],2),
	number_format($stat['year_in'],2),
	number_format($stat['all_in'],2),
	number_format($stat['month_out'],2),
	number_format($stat['year_out'],2),
	number_format($stat['all_out'],2),
	count($stat['balance']),
	$balance,
	//count($stat['balance']),
	//number_format(($stat['all_in']-$stat['all_out']),2),
	$balancet
	);
/*Частые траты*/

print '<comment class="center">Добавить расход</comment>
<div class="start_block">';
$sel = $DB -> prepare("SELECT COUNT(OD.id) as mm, OL.* FROM `outgo_data` OD, `outgo_line` OL WHERE OD.line_id = OL.id AND OD.user_id=:user_id AND `time`>:time GROUP BY OD.line_id ORDER BY mm DESC, OL.name ASC LIMIT 10");
$sel -> execute(array('user_id'=>$USER->id,'time'=>(time()-5184000)));
if($sel -> rowCount() > 0){
	print '<div class="fast_icons_block block_out">';
	while($row = $sel -> fetch()){
		printf('<div class="fast_icon icon_out_type_%s"><a class="button" href="/?page=start&action=out&line=%s"><span>%s</span></a></div>',
			$row['id'],
			$row['id'],
			$row['name']
			);
	}
	print '</div>';
}else{
	//print 'Расходов не было.';
}
print '<div class="center">
	<a class="button big" href="/?page=start&action=out">Добавить другой</a>
	</div>
	</div>';

/*Частые доходы*/
print '<comment class="center">Добавить доход</comment>
	<div class="start_block">';
$sel = $DB -> prepare("SELECT COUNT(ID.id) as mm, IL.* FROM `input_data` ID, `input_line` IL WHERE ID.line_id = IL.id AND ID.user_id=:user_id AND `time`>:time GROUP BY ID.line_id ORDER BY mm DESC, IL.name ASC LIMIT 10");
$sel -> execute(array('user_id'=>$USER->id,'time'=>(time()-5184000)));
if($sel -> rowCount() > 0){
	print '<div class="fast_icons_block block_in">';
	while($row = $sel -> fetch()){
		printf('<div class="fast_icon icon_in_type_%s"><a class="button" href="/?page=start&action=in&line=%s"><span>%s</span></a></div>',
			$row['id'],
			$row['id'],
			$row['name']
			);
	}
	print '</div>';
}else{
	//print 'Доходов не было.';
}
print '<div class="center">
			<a class="button big" href="/?page=start&action=in">Добавить другой</a>
		</div>
	</div>';
?>
<div class="start_block">
	<div class="center">
		<a href="/?page=score&amp;action=switch" class="button big">Перевод между счетами</a>
	</div>
</div>