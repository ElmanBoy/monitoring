<?
   $now = gmmktime();
	$now_year  = intval( gmdate( "Y", $now ) );
	$now_month = intval( gmdate( "m", $now ) );
	$now_day   = intval( gmdate( "d", $now ) );
	$now_hour  = gmdate( "H", $now );
	$now_min   = gmdate( "i", $now );
	$now_sec   = gmdate( "s", $now );

	
	if ( !empty( $_GET[ "year" ] ) ) $cal_year = $_GET[ "year" ];  else $cal_year = $now_year;
	if ( !empty( $_GET[ "month" ] ) ) $cal_month = $_GET[ "month" ]; else $cal_month = $now_month;
	if ( !empty( $_GET[ "day" ] ) ) $cal_day = $_GET[ "day" ];   else $cal_day = $now_day;
	
	if ( !empty( $_GET[ "gmt_ofs" ] ) ) $GMT_ofs = $_GET[ "gmt_ofs" ]; 
	if ( !empty( $_GET[ "view"]  ) )    $view = $_GET[ "view" ]; else $view = $view_default;
	
	$ox_tail = "";
	
	// calc following month and year
	$cal_next_year = $cal_year;
	if ( $cal_month < 12 )
	{
		$cal_next_month = $cal_month + 1;
	} 
	else 
	{
		$cal_next_month = 1;
		$cal_next_year = $cal_year + 1;
	}
	
	// calc previous month and year
	$cal_prev_year = $cal_year;
	if ( $cal_month > 1 )
	{
		$cal_prev_month = $cal_month - 1;
	} 
	else 
	{
		$cal_prev_month = 12;
		$cal_prev_year = $cal_year - 1;
	}	
		
	/**
	* @return integer
	* @param  string $year
	* @param  string $month
	* @desc   returns count of days for specified month/year
	*/
	function num_days( $year, $month )
	{
		$num = 31;
		while (!checkdate( $month, $num, $year ) ) { $num--; }
		return $num;	
	}
	
	if ( ( $cal_year == $now_year ) && ( $cal_month == $now_month ) )
		$today_day = $now_day;
	else   
   		$today_day = 0;

	$days_last_month = gmdate( "d", gmmktime(0,0,0,$cal_month,0,$cal_year ) );
	$days_this_month = gmdate( "d", gmmktime(0,0,0,$cal_next_month, 0, $cal_next_year ) );

	$first_day_this_month = gmmktime( "0","0","0",$cal_month, "1", $cal_year );
	$l_tm = localtime( $first_day_this_month, 1);

	//	$posx = array();
	
	// what's the weekday of the 1st day of this month?*/
	$first_day_pos = $l_tm[ "tm_wday" ];
	
	
	if ( $first_day_pos == 0 ) $first_day_pos = 7; // convert to Mo=1 to Su=7

	$day_num = $days_last_month - ($first_day_pos-2); $class=' class="el_last_month"';

?>

<TABLE border=1 bordercolor="#CCCCCC" cellspacing="0" cellpadding="3" align="center" class="calendar_tbl">
 <FORM name="frmCalendar" method="get">
  <TR>
    <TD>

<SELECT onchange="frmCalendar.submit()" name=month style="font-size:11px"> 
<? 
$cal_montnarr[0]="Январь";
$cal_montnarr[1]="Февраль";
$cal_montnarr[2]="Март";
$cal_montnarr[3]="Апрель";
$cal_montnarr[4]="Май";
$cal_montnarr[5]="Июнь";
$cal_montnarr[6]="Июль";
$cal_montnarr[7]="Август";
$cal_montnarr[8]="Сентябрь";
$cal_montnarr[9]="Октябрь";
$cal_montnarr[10]="Ноябрь";
$cal_montnarr[11]="Декабрь";

for($i=0; $i<count($cal_montarr); $i++){
$sel="";
if(isset($_GET['month'])&&($_GET['month']==$cal_montarr[$i])){$sel="selected";}
if(!isset($_GET['month'])&&date('m')==$cal_montarr[$i]){$sel="selected";}
echo "<OPTION value=".$cal_montarr[$i]." ".$sel.">".$cal_montnarr[$cal_montarr[$i]-1]."</OPTION>\n";
}
 ?>
</SELECT> 

<SELECT onchange="frmCalendar.submit()" name=year style="font-size:11px">
<? 

for($i=0; $i<count($cal_yeararr); $i++){
$sel="";
if(isset($_GET['year'])&&($_GET['year']==$cal_yeararr[$i])){$sel="selected";}
echo "<OPTION value=".$cal_yeararr[$i]." ".$sel.">".$cal_yeararr[$i]."</OPTION>\n";
}
 ?>
</SELECT>
<input type="submit" name="show" value=">>">
</TD></TR>
<tr> 
<td colspan="3" id="el_today_select" align="center"><a href="?year=<?=$now_year?>&month=<?=$now_month?>&day=<?=$now_day?>" style="text-decoration:none"><img border="0" src="/images/back_today.gif">сегодня</a></td>
	</tr>
<TR><TD>
<table border='0' width="100%" style="font-size:11px;" cellpadding="3">
<tr>
<td width="15%" align='center' style='FONT-FAMILY:Arial;FONT-SIZE:11px;FONT-WEIGHT: bold'>Пн</td>
<td width="15%" align='center' style='FONT-FAMILY:Arial;FONT-SIZE:11px;FONT-WEIGHT: bold'>Вт</td>
<td width="15%" align='center' style='FONT-FAMILY:Arial;FONT-SIZE:11px;FONT-WEIGHT: bold'>Ср</td>
<td width="15%" align='center' style='FONT-FAMILY:Arial;FONT-SIZE:11px;FONT-WEIGHT: bold'>Чт</td>
<td width="15%" align='center' style='FONT-FAMILY:Arial;FONT-SIZE:11px;FONT-WEIGHT: bold'>Пт</td>
<td width="15%" align='center' style='FONT-FAMILY:Arial;FONT-SIZE:11px;FONT-WEIGHT: bold' bgcolor="#FFFFCC">Сб</td>
<td width="15%" align='center' style='FONT-FAMILY:Arial;FONT-SIZE:11px;FONT-WEIGHT: bold' bgcolor="#FFFFCC">Вс</td>
</tr>
<tr>
<?
	for ( $y=1; $y<=6; $y++ )
	{
		echo "	<tr>\n";
		for ( $x=1; $x<=7; $x++ )
		{
			if ( ($y==1) && ($x==$first_day_pos) ) 
			{ 
				$day_num = 1; $class="";
			}
			
			if ( ($y >1) && ($day_num==$days_this_month+1) ) 
			{ 
				$day_num = 1; $class=' class="el_next_month"';
			}
			
			if ( ($class=="") && ($day_num == $today_day) )	$id=' id="el_today"'; else $id="";
			if ( ( $id!=' id="el_today"') && ($class=="") && ($day_num == $cal_day ) ) $id=' id="el_selected"';

			if ( $class != "" ) 
				{ $ap1 = ""; $ap2 = ""; }
			else 
				{
				if(in_array($day_num,$cal_dayarr)){ 
					$ap1 = '<a href="'.$calendar_open_url."/?year=$cal_year&month=$cal_month&day=$day_num".'" target="'.$calendar_open_target.'" style="font-weight:bold">'; $ap2 = '</a>'; 
					}else{
					$ap1 = ''; $ap2 = '';
					}
				}
				
			if($x==6||$x==7){
				echo '		<td'.$class.$id.' bgcolor="#FFFFCC" align="right">'.$ap1.$day_num.$ap2.'</td>'."\n";
			}else{	
				echo '		<td'.$class.$id.' align="right">'.$ap1.$day_num.$ap2.'</td>'."\n";
			}
			
			$day_num++;								
		}
		echo "	</tr>\n";
	}  

?>
</table>
</TD></TR></FORM></TABLE>
