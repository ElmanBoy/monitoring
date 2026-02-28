<?php require_once('../Connections/dbconn.php'); ?>
<?PHP $requiredUserLevel = array(1); 
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php"); 
(isset($submit))?$work_mode="write":$work_mode="read";
el_reg_work($work_mode, $login, $_GET['cat']) ; 

session_start();
 
$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

if ((isset($_POST["action"])) && ($_POST["action"] == "save")) {
  el_dbselect("UPDATE template SET `default`=0", 0, $res);
  
  $updateSQL = sprintf("UPDATE template SET name=%s, `path`=%s, `default`=%s WHERE id=%s",
                       GetSQLValueString($_POST['name'], "text"),
                       GetSQLValueString($_POST['path'], "text"),
					   GetSQLValueString($_POST['default1'], "text"),
                       GetSQLValueString($_POST['id'], "int"));

  mysql_select_db($database_dbconn, $dbconn);
  $Result1 = mysql_query($updateSQL, $dbconn) or die(mysql_error());
  echo "<script language=javascript>alert('Изменения сохранены!')</script>";
}

if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "insert")) {
	if ($_FILES['file']['tmp_name'] == "none") {
		echo "<H4 style='color:red' align=center>Не указан файл шаблона страницы <br><br><a href=javascript:history.back(-1)>Назад</a>.</h4>";
	}elseif(is_file($_SERVER['DOCUMENT_ROOT']."/tmpl/page/".$_FILES['file']['name'])){
		echo "<H4 style='color:red' align=center>Файл с таким именем уже существует. Укажите другое имя файла.<br><br><a href=javascript:history.back(-1)>Назад</a>.</h4>";
	}else{
		copy($_FILES['file']['tmp_name'], $_SERVER['DOCUMENT_ROOT']."/tmpl/page/".$_FILES['file']['name']);
		chmod($_SERVER['DOCUMENT_ROOT']."/tmpl/page/".$_FILES['file']['name'], 0777);
		unlink($_FILES['file']['tmp_name']);
		$insertSQL = sprintf("INSERT INTO template (name, `path`, `master`) VALUES (%s, %s, %s)",
						   GetSQLValueString($_POST['name'], "text"),
						   GetSQLValueString($_FILES['file']['name'], "text"),
						   GetSQLValueString($_POST['master'], "int"));
	
		mysql_select_db($database_dbconn, $dbconn);
		$Result1 = mysql_query($insertSQL, $dbconn) or die(mysql_error());
	}
}

if ((isset($_POST["action"])) && ($_POST["action"] == "del")) {
	$_POST['path']=el_translit($_POST['path']);
	$delFile=$_SERVER['DOCUMENT_ROOT'].'/tmpl/page/'.$_POST['path'];
	el_dbselect("DELETE FROM template WHERE id='".$_POST['id']."'", 0, $res);
	if(unlink($delFile)){
		if($_POST['master']==0){$word='Шаблон'; $end='';}else{$word='Страница'; $end='а';}
		echo '<script language=javascript>alert("'.$word.' '.$_POST['path'].' удален'.$end.'!")</script>';
	}else{
		echo '<script language=javascript>alert("Не удается удалить файл '.$_POST['path'].'!\\nВозможно, такого файла нет или его имя указано неверно.\\nИз базы данных шаблон удален.")</script>';
	}
}

($_COOKIE['templateHTML_temp']=='Y')?$dispHTML='block':$dispHTML='none';
($_COOKIE['templatePHP_temp']=='Y')?$dispPHP='block':$dispPHP='none';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Шаблоны</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<link href="style.css" rel="stylesheet" type="text/css">
<style type="text/css">
<!--
.notetable {background-color:#FFFFEC;}
-->
</style>
<script language="JavaScript" type="text/JavaScript">
<!--
function MM_openBrWindow(theURL,winName,features, myWidth, myHeight, isCenter) { //v3.0
  if(window.screen)if(isCenter)if(isCenter=="true"){
    var myLeft = (screen.width-myWidth)/2;
    var myTop = (screen.height-myHeight)/2;
    features+=(features!='')?',':'';
    features+=',left='+myLeft+',top='+myTop;
  }
  window.open(theURL,winName,features+((features!='')?',':'')+'width='+myWidth+',height='+myHeight);
}

function MM_goToURL() { //v3.0
  var i, args=MM_goToURL.arguments; document.MM_returnValue = false;
  for (i=0; i<(args.length-1); i+=2) eval(args[i]+".location='"+args[i+1]+"'");
}

function act1(mode, row){
	var name=document.getElementById("name"+row);
	var path=document.getElementById("path"+row);
	var def=document.getElementById("def"+row);
	var master=document.getElementById("master"+row);
	if (mode=="save"){
			document.act.action.value=mode; 
			document.act.id.value=row; 
			document.act.name.value=name.value;
			document.act.path.value=path.value;
			document.act.default1.value=def.value;
			document.act.submit();
	}
	if (mode=="del"){
		var word;
		master.value==0 ? word='шаблон' : word='страницу';
		var OK=confirm("Вы уверены, что хотите удалить "+word+" №"+row+" ?\nЭто может отразиться на работе сайта."); 
		if(OK){
			document.act.action.value=mode; 
			document.act.id.value=row; 
			document.act.path.value=path.value;
			document.act.master.value=master.value;
			document.act.submit();
		} 
	}
}

function showhideDiv(name){
	var d=document.getElementById(name);
	var dc=document.getElementById(name+"_child");
	if(dc.style.display=="none"){
		d.className="row_block";
		dc.style.marginLeft=40+"px";
		dc.style.display="block";
		document.cookie = "template"+name+"=Y; expires=Thu, 31 Dec 2020 23:59:59 GMT; path=/editor/;";
	}else{
		d.className="row_none";
		dc.style.display="none";
		document.cookie = "template"+name+"=N; expires=Thu, 31 Dec 2020 23:59:59 GMT; path=/editor/;";
	}
}


function b(obj){
	obj.style.backgroundColor="#E0E7E9";
}

function c(obj){
	obj.style.backgroundColor="transparent";
}

//-->
</script>
</head>

<body>
<table width="50%" border=0 align="center" cellpadding=0 cellspacing=0>
  <tr>
    <td width="7"><img height=7 alt="" src="img/inc_ltc.gif" width=7></td>
    <td background="img/inc_tline.gif"><img height=1 alt="" src="img/1.gif" width=1></td>
    <td width="7"><img height=7 alt="" src="img/inc_rtc.gif" width=7></td>
  </tr>
  <tr>
    <td width="7" background="img/inc_lline.gif"><img height=1 alt="" src="img/1.gif" width=1></td>
    <td valign=top class="notetable">
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="100%">Пожалуйста, не меняйте настроек, если Вы <strong>не</strong> являетесь специалистом! </td>
        </tr>
    </table></td>
    <td  width="7" background="img/inc_rline.gif"><img height=1 alt="" src="img/1.gif" width=1></td>
  </tr>
  <tr>
    <td width="7"><img height=7 alt="" src="img/inc_lbc.gif" width=7></td>
    <td background="img/inc_bline.gif"><img height=1 alt="" src="img/1.gif" width=1></td>
    <td width="7"><img height=7 alt="" src="img/inc_rbc.gif" width=7></td>
  </tr>
</table>
<h4 align="center">Шаблоны страниц </h4>
<form method="post" name="act">
<input type="hidden" name="action">
<input type="hidden" name="id">
<input type="hidden" name="name">
<input type="hidden" name="path">
<input type="hidden" name="default1">
<input type="hidden" name="master">
</form>
<div onClick="showhideDiv('HTML_temp')" id="HTML_temp" class="row_<?=$dispHTML?>"><h5>HTML-страницы</h5></div>
		<div id="HTML_temp_child" style="display:<?=$dispHTML?>; margin-left:40px; margin-top:-10px">
<?php 
$template=el_dbselect("SELECT * FROM template WHERE `master`=1", 0, $template);
$row_template = mysql_fetch_assoc($template);
if(mysql_num_rows($template)>0){
	$c=0;
	do { 
		$c++;
		($c==mysql_num_rows($template))?$rowEnd='_end':$rowEnd='';
	?>
		<div id="<?=$row_template['id']?>" class="row<?=$rowEnd?>" onMouseOver="b(this)" onMouseOut="c(this)">
			<div id="left">
				ID<?=$row_template['id']?>&nbsp;Страница <input type="text" size="15" id="name<?=$row_template['id']?>" value="<?=$row_template['name']?>">&nbsp; 
				 файл: <input type="text" size="15" id="path<?=$row_template['id']?>" value="<?=$row_template['path']?>">&nbsp;
				 <input type="hidden" id="master<?=$row_template['id']?>" value="<?=$row_template['master']?>">
			 </div>  
			<div id="right">
				<img border="0" src="img/menu_edit.gif" onClick="MM_goToURL('self','/editor/e_modules/template_editor.php?id=<?=$row_template['id']?>');return document.MM_returnValue" alt="Редактировать шаблон">&nbsp;
				<img border="0" src="img/menu_save.gif" onClick="act1('save', <?=$row_template['id']?>)" alt="Сохранить изменения">&nbsp;
				<img border="0" src="img/menu_view.gif" onClick="MM_openBrWindow('/editor/tmpl/page/<?=$row_template['path']?>','view','status=yes,scrollbars=yes,resizable=yes','1024','768','true')" alt="Просмотреть страницу">&nbsp;
				<img border="0" src="img/menu_delete.gif" onClick="act1('del', <?=$row_template['id']?>)" alt="Удалить страницу">&nbsp;
			</div>
		</div>
<?php } while ($row_template = mysql_fetch_assoc($template)); 
}else{
	echo 'Пока нет ни одной HTML-страницы.';
}
mysql_free_result($template);
?>
</div>



<div onClick="showhideDiv('PHP_temp')" id="PHP_temp" class="row_<?=$dispPHP?>"><h5>Готовые шаблоны</h5></div>
		<div id="PHP_temp_child" style="display:<?=$dispPHP?>; margin-left:40px; margin-top:-10px">
<?php 
$template=el_dbselect("SELECT * FROM template WHERE `master`!=1", 0, $template);
$row_template = mysql_fetch_assoc($template);
if(mysql_num_rows($template)>0){
	$c=0;
	do { 
		$c++;
		($c==mysql_num_rows($template))?$rowEnd='_end':$rowEnd='';
		?>
		<div id="<?=$row_template['id']?>" class="row<?=$rowEnd?>" onMouseOver="b(this)" onMouseOut="c(this)">
		<div id="left">
		ID<?=$row_template['id']?>&nbsp;Шаблон <input type="text" size="15" id="name<?=$row_template['id']?>" value="<?=$row_template['name']?>">&nbsp; 
		 файл: <input type="text" size="15" id="path<?=$row_template['id']?>" value="<?=$row_template['path']?>">&nbsp; 
		 шаблон по умолчанию: <input type="radio" name="default" id="def<?=$row_template['id']?>" <?=($row_template['default']==1)?'checked':''?> value="1">
		 <input type="hidden" id="master<?=$row_template['id']?>" value="<?=$row_template['master']?>">
		 </div>  
		<div id="right">
		<img border="0" src="img/menu_edit.gif" onClick="MM_goToURL('self','/editor/e_modules/template_editor.php?id=<?=$row_template['id']?>');return document.MM_returnValue" alt="Редактировать шаблон">&nbsp;
		<img border="0" src="img/menu_save.gif" onClick="act1('save', <?=$row_template['id']?>)" alt="Сохранить изменения">&nbsp;
		<img border="0" src="img/menu_view.gif" onClick="MM_openBrWindow('/editor/e_modules/template_view.php?id=<?php echo $row_template['id']; ?>','view','status=yes,scrollbars=yes,resizable=yes','1024','768','true')" alt="Просмотреть шаблон">&nbsp;
		<img border="0" src="img/menu_delete.gif" onClick="act1('del', <?=$row_template['id']?>)" alt="Удалить шаблон">&nbsp;
		</div></div>
<?php } while ($row_template = mysql_fetch_assoc($template)); 
}else{
	echo 'Пока нет ни одного готового шаблона.';
}
mysql_free_result($template);
?>
</div>

<p>
<table width="50%"  border="0" align="center" cellpadding="5" cellspacing="0" class="el_tbl">
  <form action="<?php echo $editFormAction; ?>" method="POST" name="insert" ENCTYPE="multipart/form-data">
  <tr>
      <td colspan="2" align="center"><b>Добавить HTML-страницу или готовый  шаблон </b></td>
  </tr>
    <tr>
      <td align="right">Название:</td>
      <td><input name="name" type="text" id="name"></td>
    </tr>
    <tr>
      <td align="right">Закачать шаблон: </td>
      <td><input name="file" type="file" id="file"></td>
    </tr>
    <tr>
      <td align="right">Тип: </td>
      <td>
	  <input name="master" type="radio" value="1" checked="checked"> HTML-страница<br>
	  <input name="master" type="radio" value="0"> Готовый шаблон<br>
	  </td>
    </tr>
    <tr>
      <td align="right">&nbsp;</td>
      <td><input name="Submit" type="submit" class="but" value="Сохранить"></td>
    </tr>
    <input type="hidden" name="MM_insert" value="insert">
</form>
</table>
</p>
</body>
</html>