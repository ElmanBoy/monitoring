<?php require_once('../Connections/dbconn.php'); ?>
<?PHP $requiredUserLevel = array(1); 
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php"); 
(isset($submit))?$work_mode="write":$work_mode="read";
el_reg_work($work_mode, $login, $_GET['cat']);  

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

if(isset($_POST['delid'])){
	$d=el_dbselect("SELECT * FROM modules WHERE id='".$_POST['delid']."'", 0, $d, 'row');
	if(substr_count($d['type'], 'catalog')==0){
		if(!unlink($_SERVER['DOCUMENT_ROOT'].'/modules/'.$d['type'].'.php')){
			echo '<script>alert("Не удается удалить файл модуля \"'.$d['name'].'\"!")</script>';//el_deldir()
		}else{
			el_dbselect("DELETE FROM modules WHERE id='".$_POST['delid']."'", 0, $res);
			echo '<script>alert("Модуль \"'.$d['name'].'\" удален!")</script>';
		}
	}else{
		el_dbselect("DELETE FROM modules WHERE id='".$_POST['delid']."'", 0, $res);
		echo '<script>alert("Модуль \"'.$d['name'].'\" удален!")</script>';
	}
}

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "update")) {
  $updateSQL = sprintf("UPDATE modules SET status=%s, `path`=%s, sort=%s WHERE id=%s",
                       GetSQLValueString(isset($_POST['status']) ? "true" : "", "defined","'Y'","'N'"),
                       GetSQLValueString($_POST['path'], "text"),
                       GetSQLValueString($_POST['sort'], "int"),
                       GetSQLValueString($_POST['id'], "int"));

  mysql_select_db($database_dbconn, $dbconn);
  $Result1 = mysql_query($updateSQL, $dbconn) or die(mysql_error());
}

if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "add")) {
  $insertSQL = sprintf("INSERT INTO modules (type, name, `path`, sort) VALUES (%s, %s, %s, %s)",
                       GetSQLValueString($_POST['type'], "text"),
                       GetSQLValueString($_POST['name'], "text"),
                       GetSQLValueString($_POST['path'], "text"),
                       GetSQLValueString($_POST['sort'], "int"));

  mysql_select_db($database_dbconn, $dbconn);
  $Result1 = mysql_query($insertSQL, $dbconn) or die(mysql_error());
}

mysql_select_db($database_dbconn, $dbconn);
$query_modules = "SELECT * FROM modules ORDER BY sort ASC";
$modules = mysql_query($query_modules, $dbconn) or die(mysql_error());
$row_modules = mysql_fetch_assoc($modules);
$totalRows_modules = mysql_num_rows($modules);
?>
<html>
<head>
<title>Модули</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<link href="style.css" rel="stylesheet" type="text/css">
<script language="javascript">
function del(id, name){
	var OK=confirm("Вы действительно хотите удалить модуль \""+name+"\"");
	if(OK){
		document.delForm.delid.value=id;
		document.delForm.submit();
	}
}
</script>
<style type="text/css">
<!--
.notetable {background-color:#FFFFEC;}
.text1 {font-size:12px; color:#000000;}
-->
</style>
</head>

<body>
<form name="delForm" method="post"><input type="hidden" name="delid"></form>
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
<h4 align="center">Программные модули </h4>
<table width="90%"  border="0" align="center" cellpadding="3" cellspacing="0">
  <tr>
    <td width="22%">Название</td>
    <td width="17%">Код</td>
    <td width="19%">Путь</td>
    <td width="23%">Номер</td>
    <td width="19%">Действия</td>
  </tr>
</table>
<?php do { ?>
<table width="90%"  border="0" align="center" cellpadding="5" cellspacing="0" class="el_tbl">
  <form method="POST" action="<?php echo $editFormAction; ?>" name="update">
  <tr>
    <td width="37%"><strong><?php echo $row_modules['name']; ?></strong></td>
    <td width="18%"><em><?php echo $row_modules['type']; ?></em></td>
    <td width="14%" align="right"><input name="path" type="text" id="path" value="<?php if(strlen($row_modules['path'])>0){echo $row_modules['path'];}else{echo "modules/".$row_modules['type'];} ?>" size="20"></td>
    <td width="3%"><input name="sort" type="text" id="sort" value="<?php echo $row_modules['sort']; ?>" size="3"></td>
    <td width="18%" align="right"><?php if (!(strcmp($row_modules['status'],"Y"))) {echo "<font color=green>Установлен</font>";}else{echo "<font color=red>Не установлен</font>";} ?>
      <input name="status" type="checkbox" id="status" value="checkbox" <?php if (!(strcmp($row_modules['status'],"Y"))) {echo "checked";} ?>>
      <input name="id" type="hidden" id="id" value="<?php echo $row_modules['id']; ?>"></td>
    <td width="10%">
	<input type="image" src="img/menu_save.gif" alt="Сохранить изменения">&nbsp;
	<img src="img/menu_delete.gif" style="cursor:pointer" onClick="del(<?=$row_modules['id']?>, '<?=$row_modules['name']?>')" alt="Удалить модуль">
	</td> 
  </tr>
  <input type="hidden" name="MM_update" value="update">
</form>
</table>
<?php } while ($row_modules = mysql_fetch_assoc($modules)); ?><br>

<table width="90%"  border="0" align="center" cellpadding="3" cellspacing="0" class="el_tbl">
  <form method="POST" action="<?php echo $editFormAction; ?>" name="add">
  <tr>
    <td colspan="2" align="center"><strong>Новый модуль </strong></td>
  </tr>
  <tr>
    <td width="15%">Название:</td>
    <td width="85%"><input name="name" type="text" id="name"></td>
  </tr>
  <tr>
    <td>Код:</td>
    <td><input name="type" type="text" id="type"></td>
  </tr>
  <tr>
    <td>Путь:</td>
    <td><input name="path" type="text" id="path"></td>
  </tr>
  <tr>
    <td>Номер:</td>
    <td><input name="sort" type="text" id="sort" size="3"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><input name="Submit2" type="submit" class="but" value="Добавить"></td>
  </tr>
  <input type="hidden" name="MM_insert" value="add">
</form>
</table>
<p>&nbsp;</p>
</body>
</html>
<?php
mysql_free_result($modules);
?>
