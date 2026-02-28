<?
$requiredUserLevel = array(0); 
require_once($_SERVER['DOCUMENT_ROOT'].'/Connections/dbconn.php');
$cat=intval($_GET['p']);
$row_dbcontent=el_dbselect("SELECT * FROM content WHERE cat='$cat'", 0, $p, 'row');
$path=$row_dbcontent['path'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<? el_meta() ?>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251" />
<link href="/css/style.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="/css/mainmenu.css" />
<link rel="stylesheet" type="text/css" href="/css/calendar.css" />
<link rel="stylesheet" type="text/css" href="/css/tabtastic.css" />
<link type="text/css" href="/css/ui-lightness/jquery-ui.css" rel="stylesheet" />
<script src="/js/jquery.min.js"></script>
<script src="/js/scripts.js"></script>
</head>

<body onload="window.print()">
						<!--Дата-->
                        <? 
						switch(date('w')){
							case 0: $dayName='Воскресенье'; break;
							case 1: $dayName='Понедельник'; break;
							case 2: $dayName='Вторник'; break;
							case 3: $dayName='Среда'; break;
							case 4: $dayName='Четверг'; break;
							case 5: $dayName='Пятница'; break;
							case 6: $dayName='Суббота'; break;
						}
						?>
						
    <div align="center">
                    	<a href="/" title="Красное знамя - независимая газета республики Коми"><img src="/img/logo.jpg" alt="Красное знамя - независимая газета республики Коми" /></a><br />
                        <div class="date"><?=$dayName?>, <? el_date(date('Y-m-d'))?></div>
                    </div>
                    <hr />
    <div class="all">
        	<!--Контент-->
            <div class="colum2" style="width:95%; padding:10px; margin:0">
					
					<!--Центральная колонка-->
					<div class="maincolum fll" style="width:100%">
							<!--Главная новость-->
							<div class="content">
    <h1><? //el_text('el_pageprint','caption')?></h1>
    <? el_text('el_pageprint','text')?>
	<? el_module('el_pagemodule', '')?>
	</div>
</div>
</div></div>
<div class="clear"></div>
<hr />
<div style="padding:10px;">
Copyright © 2009 - <?=date('Y')?> ЗАО Газета «Красное знамя».<br />
Все материалы, находящиеся на сайте www.komikz.ru , охраняются в соответствии с законодательством РФ об авторском и смежных правах и принадлежат ЗАО «Газета «Красное знамя». При использовании материалов сайта ссылка на источник обязательна.
</div>
<script language="javascript">
$('div, span, td, p, li').css('color','#000');
//$('span').css('color','#000');
$('a').attr('href', '/print.php?p=<?=$row_dbcontent['cat']?><?=(intval($_GET['id'])>0)?'&id='.intval($_GET['id']):''?>');
$('a').css({'text-decoration':'none', 'color':'#000', 'cursor':'text'});
</script>
</body>
</html>
