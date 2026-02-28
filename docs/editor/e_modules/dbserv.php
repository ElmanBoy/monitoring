<?
require_once($_SERVER['DOCUMENT_ROOT'].'/Connections/dbconn.php');
$requiredUserLevel = array(1, 2); 
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php"); 

if(isset($_GET['optimize'])){
mysql_select_db($database_dbconn, $dbconn);
$sql_query="SHOW TABLES";
$string = mysql_query($sql_query, $dbconn) or die(mysql_error());
$row_string = mysql_fetch_array($string);
$tablesc=0;
$tables = array();
	do{ 
		
		$tables[]=$row_string[0];
		mysql_select_db($database_dbconn, $dbconn);
		$sql_query2="REPAIR TABLE ".$row_string[0];
		$string1 = mysql_query($sql_query2, $dbconn) or die(mysql_error());
		$sql_query1="OPTIMIZE TABLE ".$row_string[0];
		$string1 = mysql_query($sql_query1, $dbconn) or die(mysql_error());
		$tablesc++;
	}while($row_string = mysql_fetch_array($string));
			
			$tabs = count($tables);
		// Определение размеров таблиц
		mysql_select_db($database_dbconn, $dbconn);
		$result = mysql_query("SHOW TABLE STATUS");
		$tabinfo = array();
		$tabinfo[0] = 0;
		$info = '';
		while($item = mysql_fetch_array($result)){
			if(in_array($item[0], $tables)) {
				$item[3] = empty($item[3]) ? 0 : $item[3];
				$tabinfo[0] += $item[3];
				$tabinfo[$item[0]] = $item[3];
				$size += $item[5];
				$tabsize[$item[0]] = 1 + round(10 * 1048576 / ($item[4] + 1));
				if($item[3]) $info .= "|" . $item[3];
			}
		}
		$show = 10 + $tabinfo[0] / 50;
		$info = $tabinfo[0] . $info;
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<title>Обслуживание базы данных</title>
<link href="/editor/style.css" rel="stylesheet" type="text/css">
<script type="text/JavaScript">
<!--
function MM_goToURL() { //v3.0
  var i, args=MM_goToURL.arguments; document.MM_returnValue = false;
  for (i=0; i<(args.length-1); i+=2) eval(args[i]+".location='"+args[i+1]+"'");
}
//-->
</script>
</head>

<body>
<h4 align=center>Обслуживание базы данных</h4>
<p><? el_showalert("info", "Резервная копия базы данных позволяет сохранить контент сайта для последующего восстановления в случае технических проблем у хостинг-провайдера.") ?><br>

  <input name="Submit" type="submit" class="but" onClick="MM_goToURL('self','/editor/e_modules/dumper.php');return document.MM_returnValue" value="Создать/Восстановить резервную копию базы данных">
</p>
<p>&nbsp;</p>
<p><? el_showalert("info", "Оптимизация таблиц позволит уменьшить размер базы данных и ускорить обработку запросов.<br>Дополнительно будет проведено восстановление испорченных таблиц.") ?><br>

  <input name="Submit" type="submit" class="but" onClick="MM_goToURL('self','dbserv.php?optimize');return document.MM_returnValue" value="Оптимизировать таблицы базы данных">
</p>
<? if(isset($_GET['optimize'])){echo "Обработано таблиц : ".$tabs."<br>Размер базы данных: " . round($size / 1048576, 3) . " МБ"; }?>


</body>
</html>
