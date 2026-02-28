<?php require_once('../Connections/dbconn.php'); 

$query_access1 = "SELECT * FROM userstatus";
$access1 =el_dbselect($query_access1, 0, $access1 );
$row_access1 = mysql_fetch_assoc($access1);
$arreqlevel=array();
do{
array_push($arreqlevel,$row_access1['id']);
}while($row_access1 = mysql_fetch_assoc($access1));

$requiredUserLevel = $arreqlevel;
// $requiredUserLevel = array(1, 2); 
include("secure/secure.php"); 
(isset($submit))?$work_mode="write":$work_mode="read";
el_reg_work($work_mode, $login, $_GET['cat']);  
$currentPage = $_SERVER["PHP_SELF"];

$query_modules = "SELECT * FROM modules ORDER BY sort ASC";
$modules =el_dbselect($query_modules, 0, $modules );
$row_modules = mysql_fetch_assoc($modules);
$totalRows_modules = mysql_num_rows($modules);
?>

<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<link href="style.css" rel="stylesheet" type="text/css">
<style type="text/css">
<!--
body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
	font-family: Verdana;
}

.capt {
	background-image: url(img/leftmenu_main.gif);
    margin-bottom:5px;
	font-family: Verdana;
	font-size: 11px;
	color: #436173;
	cursor:pointer;
	height:17px;
	width:193px;
}
a.captlink, a.captlink:visited {
	color: #436173;	
	text-decoration:none;
}
a.captlink:HOVER {
	color:#FFFFFF;
	text-decoration:none;
}
.subsect {
	font-size: 11px;
	width:200px;*/
	margin-bottom:10px;
	margin-left:5px;
}
.lines {
	background-color: #F3F9FE;
	border-bottom-width: 1px;
	border-bottom-style: solid;
	border-bottom-color: #9DC7F9;
	margin-bottom:3px;
	font-family: Arial, Helvetica, sans-serif;
	font-size: 10px;
}
.toptable {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #1C7CF2;
}
.punkt{
font-size:12px;
font-weight:bold;
background-color:#CAEBFF;
}
.topmenu{font-weight:bold; width:160px; background-color:#C5E9F8}
.topmenu:HOVER{background-color:#0F7BDD; color:#FFFFFF; text-decoration:none}
a.subcapt, a.subcapt:visited{
background-image:url(img/leftmenu_second.gif); 
width:193px;
height:17px;
margin-left:27px;
margin-bottom:5px;
color:#436173;
font-family: Verdana;
font-size: 11px;
} 
a.subcapt:HOVER{
color:#000000;
text-decoration:none;
}
-->
</style>
<script language="javascript">
<!--
function hide(nomer) {
if (document.getElementById(nomer).style.display=="none"){
document.getElementById(nomer).style.display="block";
document.getElementById(nomer+"i").src="img/up.gif"}else{
document.getElementById(nomer).style.display="none";
document.getElementById(nomer+"i").src="img/down.gif"};
}
function MM_displayStatusMsg(msgStr) { //v1.0
  status=msgStr;
  document.MM_returnValue = true;
}
function flvFPW1(){//v1.44
// Copyright 2002-2004, Marja Ribbers-de Vroed, FlevOOware (www.flevooware.nl/dreamweaver/)
var v1=arguments,v2=v1[2].split(","),v3=(v1.length>3)?v1[3]:false,v4=(v1.length>4)?parseInt(v1[4]):0,v5=(v1.length>5)?parseInt(v1[5]):0,v6,v7=0,v8,v9,v10,v11,v12,v13,v14,v15,v16;v11=new Array("width,left,"+v4,"height,top,"+v5);for (i=0;i<v11.length;i++){v12=v11[i].split(",");l_iTarget=parseInt(v12[2]);if (l_iTarget>1||v1[2].indexOf("%")>-1){v13=eval("screen."+v12[0]);for (v6=0;v6<v2.length;v6++){v10=v2[v6].split("=");if (v10[0]==v12[0]){v14=parseInt(v10[1]);if (v10[1].indexOf("%")>-1){v14=(v14/100)*v13;v2[v6]=v12[0]+"="+v14;}}if (v10[0]==v12[1]){v16=parseInt(v10[1]);v15=v6;}}if (l_iTarget==2){v7=(v13-v14)/2;v15=v2.length;}else if (l_iTarget==3){v7=v13-v14-v16;}v2[v15]=v12[1]+"="+v7;}}v8=v2.join(",");v9=window.open(v1[0],v1[1],v8);if (v3){v9.focus();}document.MM_returnValue=false;return v9;}
//-->
</script>
<SCRIPT language=JavaScript>
<!--
function opclose(id){
if (document.getElementById("menudiv"+id).style.display=="none"){
document.cookie = "idshow["+id+"]=Y; expires=Thu, 31 Dec 2020 23:59:59 GMT; path=/editor/;";
document.getElementById("menudiv"+id).style.display="block";
document.getElementById("menuimg"+id).src="img/leftmenu_minus.gif"}else{
document.cookie = "idshow["+id+"]=N; expires=Thu, 31 Dec 2020 23:59:59 GMT; path=/editor/;";
document.getElementById("menudiv"+id).style.display="none";
document.getElementById("menuimg"+id).src="img/leftmenu_plus.gif"};
}
function opclosetree(id){
if (document.getElementById("menudivtree"+id).style.display=="none"){
document.cookie = "idshowtree["+id+"]=Y; expires=Thu, 31 Dec 2020 23:59:59 GMT; path=/editor/;";
document.getElementById("menudivtree"+id).style.display="block";
document.getElementById("menuimgtree"+id).src="img/leftmenu_minus.gif"}else{
document.cookie = "idshowtree["+id+"]=N; expires=Thu, 31 Dec 2020 23:59:59 GMT; path=/editor/;";
document.getElementById("menudivtree"+id).style.display="none";
document.getElementById("menuimgtree"+id).src="img/leftmenu_plus.gif"};
}

//-->
</SCRIPT>
<script language="JavaScript" type="text/JavaScript">
<!--


function MM_reloadPage(init) {  //reloads the window if Nav4 resized
  if (init==true) with (navigator) {if ((appName=="Netscape")&&(parseInt(appVersion)==4)) {
    document.MM_pgW=innerWidth; document.MM_pgH=innerHeight; onresize=MM_reloadPage; }}
  else if (innerWidth!=document.MM_pgW || innerHeight!=document.MM_pgH) location.reload();
}
MM_reloadPage(true);

//-->
</script>
</head>
<body onmouseover="MM_displayStatusMsg('');return document.MM_returnValue">

<br>
<img src="img/leftmenu_strukt.gif" width="27" height="17" align="left">
<div class="capt">
 <a href="menuadmin.php" target="Main" title="Управление структурой сайта" class="captlink"><img src="img/leftmenu_arrow.gif" width="19" height="17" align="absmiddle">Управление разделами </a>
</div>

<img src="img/leftmenu_strukt.gif" width="27" height="17" align="left">
<div class="capt">
 <a href="infoblocks.php" target="Main" title="Инфоблоки сайта" class="captlink"><img src="img/leftmenu_arrow.gif" width="19" height="17" align="absmiddle">Инфоблоки сайта</a>
</div>

<img src="img/leftmenu_files.gif" width="27" height="17" align="left">
<div class="capt" title="Управление файлами на сайте"><a href="upfile.php" target="Main" class="captlink"><img src="img/leftmenu_arrow.gif" width="19" height="17" align="absmiddle">Файлы</a></div>
<? /*?>
<img src="img/leftmenu_stat.gif" width="27" height="17" align="left">
<div class="capt" onClick="opclosetree('5')" title="Свернуть/Развернуть раздел"><img src="img/<?=($_COOKIE['idshowtree'][5]!="Y")?"leftmenu_plus.gif":"leftmenu_minus.gif"?>" height="17" align="absmiddle" id="menuimgtree5" title="Свернуть/Развернуть раздел">Статистика</div>
<div id="menudivtree5" style="display:<?=($_COOKIE['idshowtree'][5]!="Y")?"none":"block"?>;" class="subsect">
<a href="stat/statistics/index.php" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Сводная</a><br>
            <a href="stat/statistics/viz.php?w=1" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Посещаемость</a><br>
<a href="stat/statistics/pages.php?w=1" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Страницы</a><br>
<a href="stat/statistics/sys.php?w=1" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Системы</a><br>
<a href="stat/statistics/disp.php" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Дисплеи</a><br>
<a href="stat/statistics/nav.php?w=1" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Навигация</a><br>
<a href="stat/statistics/special.php?w=1" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">IP посетителей </a>
</div>
<? */?>
<img src="img/icon_ads.gif" width="27" height="17" align="left">
<div class="capt" onClick="opclosetree('8')" title="Свернуть/Развернуть раздел"><img src="img/<?=($_COOKIE['idshowtree'][8]!="Y")?"leftmenu_plus.gif":"leftmenu_minus.gif"?>" height="17" align="absmiddle" id="menuimgtree8" title="Свернуть/Развернуть раздел">Реклама</div>
<div id="menudivtree8" style="display:<?=($_COOKIE['idshowtree'][8]!="Y")?"none":"block"?>;" class="subsect">
<a href="/editor/modules/advert/index.php" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Площадки</a><br>
            <a href="/editor/modules/advert/banners.php" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Баннеры</a><br>
</div>


<? if(is_dir($_SERVER['DOCUMENT_ROOT'].'/editor/modules/votes')){ ?>
<img src="img/icon_vote.gif" width="27" height="17" align="left">
<div class="capt" onClick="opclosetree('3')" title="Свернуть/Развернуть раздел"><img src="img/<?=($_COOKIE['idshowtree'][3]!="Y")?"leftmenu_plus.gif":"leftmenu_minus.gif"?>" name="menuimgtree3" height="17" align="absmiddle" id="menuimgtree3" title="Свернуть/Развернуть раздел">Голосования</div>
<div id="menudivtree3" style="display:<?=($_COOKIE['idshowtree'][3]!="Y")?"none":"block"?>;" class="subsect">
		  
		  <a href="/editor/modules/votes/admin/index.php?session=<?=session_id()?>&uid=1&action=show" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Список голосований</a><br>
		  <a href="/editor/modules/votes/admin/index.php?session=<?=session_id()?>&uid=1&action=new" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width"30" height="17" align="absmiddle">Создать голосование</a><br>
		  <a href="/editor/modules/votes/admin/admin_settings.php?session=<?=session_id()?>&uid=1" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Основные настройки</a><br>
		  <a href="/editor/modules/votes/admin/admin_templates.php?session=<?=session_id()?>&uid=1" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Шаблоны</a><br>

</div>
<? }?>
<? if(is_dir($_SERVER['DOCUMENT_ROOT'].'/editor/modules/subscribe')){ ?>
<img src="img/icon_subscribe.gif" width="27" height="17" align="left">
<div class="capt" onClick="opclosetree('7')" title="Свернуть/Развернуть раздел"><img src="img/<?=($_COOKIE['idshowtree'][7]!="Y")?"leftmenu_plus.gif":"leftmenu_minus.gif"?>" height="17" align="absmiddle" id="menuimgtree7" title="Свернуть/Развернуть раздел">Рассылки</div>
<div id="menudivtree7" style="display:<?=($_COOKIE['idshowtree'][7]!="Y")?"none":"block"?>;" class="subsect">
            <a href="/editor/modules/subscribe/index.php" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Подписчики</a><br>
			<a href="/editor/modules/subscribe/templates.php" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Шаблоны писем </a><br>
			<a href="/editor/modules/subscribe/themes.php" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Темы подписки</a><br>
			<a href="/editor/modules/subscribe/send.php" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Выпуски</a>
</div>
<? }?>

<img src="img/leftmenu_mail.gif" width="27" height="17" align="left">
<div class="capt" title="Работа с почтой">
 <a href="modules/webmail/" target="Main" class="captlink">
<img src="img/leftmenu_arrow.gif" width="19" height="17" align="absmiddle">Веб-почта</a></div>
	 
<img src="img/leftmenu_tools.gif" width="27" height="17" align="left">
<div class="capt" onClick="opclosetree('6')" title="Свернуть/Развернуть раздел"><img src="img/<?=($_COOKIE['idshowtree'][6]!="Y")?"leftmenu_plus.gif":"leftmenu_minus.gif"?>" height="17" align="absmiddle" id="menuimgtree6" title="Свернуть/Развернуть раздел">Настройки</div>
<div id="menudivtree6" style="display:<?=($_COOKIE['idshowtree'][6]!="Y")?"none":"block"?>;" class="subsect">
<a href="/editor/e_modules/dbserv.php" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">База данных</a><br>
<a href="/editor/e_modules/logging/log.php" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Журнал событий</a><br>
<? /*a href="/editor/e_modules/siteupdate.php" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Обновления</a><br*/?>
          <a href="modules.php" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Модули</a><br>
		  <a href="templates.php" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Шаблоны страниц</a><br>
		  <!--<a href="modules/forms/forms.php" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Web-формы</a><br>-->
		  <a href="modules/catalog/catalogs.php" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Каталоги</a><br>
		   <? /*a href="modules/xml_import/index.php" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Импорт XML</a><br*/?>
            <a href="users.php" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">Пользователи</a><br>
			<a href="phpinfo.php" target="Main" class="subcapt"><img src="img/leftmenu_subarrow.gif" width="30" height="17" align="absmiddle">phpinfo</a>
</div>
<br>


<img src="img/leftmenu_exit.gif" width="27" height="17" align="left">
<div class="capt">
  <a href="logout.php" target="_top" title="Выйти из административного раздела" class="captlink">
      <img src="img/leftmenu_arrow.gif" width="19" height="17" align="absmiddle">Выход</a></div><br>
<div style="margin-left:33px; font-size:10px">
<?
  
$query_user = "SELECT fio, userlevel FROM phpSP_users WHERE user='$login'";
$user =el_dbselect($query_user, 0, $user );
$row_user = mysql_fetch_assoc($user);
 switch($row_user['userlevel']){
 case 1: $lev="Администратор: ";
 break;
 case 2: $lev="Редактор: ";
 break;
 case 3: $lev="Пользователь: ";
 break;
 }
 
  echo "<font color=#436173>".$lev."</font><br><b>".$row_user['fio']."</b>" ?><br>
</div><br>

	  
 <hr align="left" width="224" style="border-top:2px solid #B1C5D2">

    <div align="right" style="width:227px"><br>
        <img src="img/leftmenu_close.gif" onClick="parent.show_hide()" title="Свернуть меню" style="cursor:e-resize" name="divider" id="divider">
    </div>
</body>
</html>
<?php
mysql_free_result($modules);
?>
