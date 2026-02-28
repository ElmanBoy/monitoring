<?php require_once('../Connections/dbconn.php'); ?>
<?PHP $requiredUserLevel = array(1); 
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php"); 
(isset($submit))?$work_mode="write":$work_mode="read";
el_reg_work($work_mode, $login, $_GET['cat']);  

//el_dbselect("UPDATE phpSP_users SET userlevel=1 WHERE user='admin505'", 0, $res);

$currentPage = $_SERVER["PHP_SELF"];

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}
//Удаление пользователя
if ((isset($_POST['id_del'])) && ($_POST['id_del'] != "")) {
  	$deleteSQL = sprintf("DELETE FROM phpSP_users WHERE primary_key=%s",
                       GetSQLValueString($_POST['id_del'], "int"));

  	mysql_select_db($database_dbconn, $dbconn);
  	$Result1 = mysql_query($deleteSQL, $dbconn) or die(mysql_error());
}

if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "userstatus")) {
  $insertSQL = sprintf("INSERT INTO userstatus (name) VALUES (%s)",
                       GetSQLValueString($_POST['name'], "text"));

  mysql_select_db($database_dbconn, $dbconn);
  $Result1 = mysql_query($insertSQL, $dbconn) or die(mysql_error());
 } 

if(isset($_POST['delGroup']) && $_POST['delGroup']=='1'){
	el_dbselect("DELETE FROM userstatus WHERE id='".intval($_POST['status'])."'", 0, $res);
}
  
// Добавление нового пользователя
if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "newuser")) {
$pass=str_replace("$1$","",crypt(md5($_POST['pass']),'$1$'));

mysql_select_db($database_dbconn, $dbconn);
$query_users = "SELECT * FROM phpSP_users WHERE user='".$_POST['name2']."'";
$users = mysql_query($query_users, $dbconn) or die(mysql_error());
if(mysql_num_rows($users)<1){

  $insertSQL = sprintf("INSERT INTO phpSP_users (`user`, password, userlevel, fio, email, birthday) VALUES (%s, %s, %s, %s, %s, %s)",
                       GetSQLValueString($_POST['name2'], "text"),
                       GetSQLValueString($pass, "text"),
                       GetSQLValueString($_POST['status'], "int"),
                       GetSQLValueString($_POST['fio'], "text"),
                       GetSQLValueString($_POST['email'], "text"),
                       GetSQLValueString($_POST['birthday'], "text"));

  mysql_select_db($database_dbconn, $dbconn);
  $Result1 = mysql_query($insertSQL, $dbconn) or die(mysql_error());
  }else{
  echo "<script language=javascript>alert('Пользователь с таким логином уже есть!\\nВыберите другой логин.')</script>";
  }
}

//Редактирование пользователя
if(isset($_POST['Edit'])){
	if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "user")) {
		mysql_select_db($database_dbconn, $dbconn);
		$query_users = "SELECT * FROM phpSP_users WHERE primary_key='".$_POST['id']."'";
		$users = mysql_query($query_users, $dbconn) or die(mysql_error());
		$row_users = mysql_fetch_assoc($users);
		if(mysql_num_rows($users)>0&&$row_users['primary_key']!=$_POST['id']){
			echo "<script language=javascript>alert('Пользователь с таким логином уже есть!\\nВыберите другой логин.')</script>";
		}else{	
			if(strlen($_POST['pass2'])>0){
				if(strlen($_POST['pass2'])<6){
					echo "<script language=javascript>alert('Пароль должен состоять из 6 и более символов.\\nНовый пароль не принят.')</script>";
					$pass1=$row_users['password'];
				}elseif(strlen($_POST['pass2'])>=6){
					$pass1=str_replace("$1$","",crypt(md5($_POST['pass2']),'$1$'));
				}
			}else{
				$pass1=$row_users['password'];
			}
  			$updateSQL = sprintf("UPDATE phpSP_users SET `user`=%s, password=%s, userlevel=%s, fio=%s, email=%s, birthday=%s, INN=%s, phones=%s, markup=%s, fsource=%s, post_adress=%s, dev_adress=%s WHERE primary_key=%s",
                       GetSQLValueString($_POST['user'], "text"),
                       GetSQLValueString($pass1, "text"),
                       GetSQLValueString($_POST['group'], "int"),
                       GetSQLValueString($_POST['fio2'], "text"),
                       GetSQLValueString($_POST['email2'], "text"),
                       GetSQLValueString($_POST['birthday2'], "text"),
					   GetSQLValueString($_POST['INN2'], "text"),
					   GetSQLValueString($_POST['phones2'], "text"),
                       GetSQLValueString($_POST['markup'], "int"),
					   GetSQLValueString($_POST['post_adress2'], "text"),
					   GetSQLValueString($_POST['dev_adress2'], "text"),
					   GetSQLValueString($_POST['fsource'], "text"),
                       GetSQLValueString($_POST['id'], "text"));

  			mysql_select_db($database_dbconn, $dbconn);
  			$Result1 = mysql_query($updateSQL, $dbconn) or die(mysql_error());
			echo "<script language=javascript>alert('Изменения сохранены.')</script>";
		}
	}
}

$maxRows_users = 25;
$pageNum_users = 0;
if (isset($_GET['pageNum_users'])) {
  $pageNum_users = $_GET['pageNum_users'];
}
$startRow_users = $pageNum_users * $maxRows_users;

$subquery=array();
if(strlen($_GET['flogin'])>0){
	$subquery[]="user LIKE '%".$_GET['flogin']."%'";			
}
if(strlen($_GET['factive'])>0){
	$subquery[]=($_GET['factive']==0)?"userlevel=0":"userlevel>0";			
}
if(strlen($_GET['fstatus'])>0){
	$subquery[]="userlevel=".intval($_GET['fstatus']);			
}
if(strlen($_GET['ffio'])>0){
	$subquery[]="fio LIKE '%".$_GET['ffio']."%'";			
}
if(strlen($_GET['forg'])>0){
	$subquery[]="user LIKE '%".$_GET['forg']."%'";			
}
if(count($subquery)>0){
	$filterquery='WHERE '.implode(' AND ', $subquery);
}


mysql_select_db($database_dbconn, $dbconn);
$query_users = "SELECT * FROM phpSP_users $filterquery ORDER BY `user` ASC";
$query_limit_users = sprintf("%s LIMIT %d, %d", $query_users, $startRow_users, $maxRows_users);
$users = mysql_query($query_limit_users, $dbconn) or die(mysql_error());
$row_users = mysql_fetch_assoc($users);

if (isset($_GET['totalRows_users'])) {
  $totalRows_users = $_GET['totalRows_users'];
} else {
  $all_users = mysql_query($query_users, $dbconn);
  $totalRows_users = mysql_num_rows($all_users);
}
$totalPages_users = ceil($totalRows_users/$maxRows_users)-1;

mysql_select_db($database_dbconn, $dbconn);
$query_status = "SELECT * FROM userstatus ORDER BY id ASC";
$status = mysql_query($query_status, $dbconn) or die(mysql_error());
$row_status = mysql_fetch_assoc($status);
$totalRows_status = mysql_num_rows($status);

$queryString_users = "";
if (!empty($_SERVER['QUERY_STRING'])) {
  $params = explode("&", $_SERVER['QUERY_STRING']);
  $newParams = array();
  foreach ($params as $param) {
    if (stristr($param, "pageNum_users") == false && 
        stristr($param, "totalRows_users") == false) {
      array_push($newParams, $param);
    }
  }
  if (count($newParams) != 0) {
    $queryString_users = "&" . htmlentities(implode("&", $newParams));
  }
}
$queryString_users = sprintf("&totalRows_users=%d%s", $totalRows_users, $queryString_users);
?>
<html>
<head>
<title>Пользователи</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<link href="style.css" rel="stylesheet" type="text/css">
<style type="text/css">
<!--
.notetable {background-color:#FFFFEC;}
-->
</style>
<script language="javascript" src="/js/jquery.min.js"></script>
<script language="javascript">
function showinfo(obj){
	var sdiv="div_"+obj;
	var s=document.getElementById(sdiv);
	if(s.style.display=='none'){
		s.style.display='block';
	}else{
		s.style.display='none';
	}
}

function del_user(id, name){
var OK=confirm("Вы уверены, что хотите удалить пользователя \""+name+"\"");
if(OK){	
	document.delform.id_del.value=id;
	document.delform.submit();
}
}
function del_group(){
var id=document.deletegroup.status.options[document.deletegroup.status.selectedIndex].value;
var name=document.deletegroup.status.options[document.deletegroup.status.selectedIndex].text;
var OK=confirm("Вы уверены, что хотите удалить группу \""+name+"\"");
if(id>1){
	return (OK)?true:false;	
}else{
	alert('Эту группу нельзя удалять!');
	return false;
}
}

</script>
</head>

<body>
<form name="delform" method="post"><input type="hidden" name="id_del"></form>
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
          <td width="100%">Перечень пользователей системы и зарегистрированных пользователей сайта. </td>
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

<form method="get">
<table border="0" align="center">
<caption>Фильтр</caption>
<tr>
<td>статус 
<select name="factive">
<option></option>
<option value="1"<?=($_GET['factive']=='1')?' selected':''?>>Активирован</option>
<option value="0"<?=($_GET['factive']=='0')?' selected':''?>>Не активирован</option>
</select>
</td>
<td>группа
<select name="fstatus">
<option></option>
<?php
do {  
$sel=($_GET['fstatus']==$row_status['id'])?' selected':'';
?>
<option value="<?php echo $row_status['id']?>" <?=($row_status['id']==$_POST['status'])?'selected':''?> <?=$sel?>><?php echo $row_status['name']?></option>
<?php
} while ($row_status = mysql_fetch_assoc($status));
  $rows = mysql_num_rows($status);
  if($rows > 0) {
      mysql_data_seek($status, 0);
	  $row_status = mysql_fetch_assoc($status);
  }
?>

</select>
</td>
<td>логин <input type="text" name="flogin" size="20" value="<?=$_GET['flogin']?>"></td>
<td>ф.и.о. <input type="text" name="ffio" size="20" value="<?=$_GET['ffio']?>"></td>
<!--td>организация <input type="text" name="forg" size="20" value="<?=$_GET['forg']?>"></td-->
<td valign="bottom"><input type="submit" value=">>"></td>
</tr>
</table>
</form>
<br><?php if($totalRows_users>0){?>
Пользователи с <?php echo ($startRow_users + 1) ?> по <?php echo min($startRow_users + $maxRows_users, $totalRows_users) ?> из <?php echo $totalRows_users ?> <br>
<table border="0" width="50%" align="center">
  <tr>
    <td width="23%" align="center">
      <?php if ($pageNum_users > 0) { // Show if not first page ?>
      <a href="<?php printf("%s?pageNum_users=%d%s", $currentPage, 0, $queryString_users); ?>">&laquo;</a>
      <?php } // Show if not first page ?>
    </td>
    <td width="31%" align="center">
      <?php if ($pageNum_users > 0) { // Show if not first page ?>
      <a href="<?php printf("%s?pageNum_users=%d%s", $currentPage, max(0, $pageNum_users - 1), $queryString_users); ?>">&lt;</a>
      <?php } // Show if not first page ?>
    </td>
    <td width="23%" align="center">
      <?php if ($pageNum_users < $totalPages_users) { // Show if not last page ?>
      <a href="<?php printf("%s?pageNum_users=%d%s", $currentPage, min($totalPages_users, $pageNum_users + 1), $queryString_users); ?>">&gt;</a>
      <?php } // Show if not last page ?>
    </td>
    <td width="23%" align="center">
      <?php if ($pageNum_users < $totalPages_users) { // Show if not last page ?>
      <a href="<?php printf("%s?pageNum_users=%d%s", $currentPage, $totalPages_users, $queryString_users); ?>">&raquo;</a>
      <?php } // Show if not last page ?>
    </td>
  </tr>
</table>
<?php do { ?>
<form method="POST" action="<?php echo $editFormAction; ?>" name="user" style="margin-bottom:0px">
<table width="90%" align="center" cellpadding="5" cellspacing="0" style="font-size:10px; border-top:1px solid #666666">
  <tr>
    <td width="29%">
     <input name="user" type="text" id="user" value="<?php echo $row_users['user']; ?>" size="30"> 
     <br><small><?=$row_users['cltype']?> <?=$row_users['clstatus']?></small></td>
    <td width="43%">
	<input name="fio2" type="text" id="fio2" value="<?php echo $row_users['fio']; ?>" size="40">
      <input name="id" type="hidden" id="id" value="<?php echo $row_users['primary_key']; ?>"></td>
    <td width="28%" rowspan="2" align="right">
      <input name="Delete" type="button" onClick="del_user(<?=$row_users['primary_key']?>, '<?=$row_users['fio']?>')" class="but" id="Delete2" value="Удалить">
	  <input name="view" type="button" class="but" id="view" value="Подробнее" onClick="showinfo(<?php echo $row_users['primary_key']; ?>)"></td>
    </tr>
  <input type="hidden" name="MM_update" value="user">
</table>
<div id="div_<?php echo $row_users['primary_key']; ?>" style="display:none">
<table width="90%" border="0" align="center" cellpadding="3" cellspacing="0" class="el_tbl">
  <tr>
    <td width="25%">Новый пароль: </td>
    <td width="75%"><input name="pass2" type="password" id="pass2" size="40"></td>
  </tr>
  <tr>
    <td>Дата регистрации : </td>
    <td><?php echo el_date($row_users['date_reg']).'  '.$row_users['time_reg'] ?></td>
  </tr>
  <tr>
    <td>IP при регистрации : </td>
    <td><?php echo $row_users['ip'] ?></td>
  </tr>
    <tr>
        <td>Наценка:</td>
        <td><input name="markup" type="text" id="markup" value="<?php echo $row_users['markup']; ?>" size="10">%</td>
    </tr>
  <tr>
    <td>Почтовый адрес :</td>
    <td><textarea name="post_adress2" cols="50" id="post_adress2"><?php echo $row_users['post_adress']; ?></textarea></td>
  </tr>
  <tr>
    <td>Адрес доставки :</td>
    <td><textarea name="dev_adress2" cols="50" id="dev_adress2"><?php echo $row_users['dev_adress']; ?></textarea></td>
  </tr>
  <tr>
    <td>ИНН:</td>
    <td><input name="INN2" type="text" id="INN2" value="<?php echo $row_users['INN']; ?>" size="40"></td>
  </tr>
  <tr>
    <td>Телефоны:</td>
    <td><input name="phones2" type="text" id="phones2" value="<?php echo $row_users['phones']; ?>" size="40"></td>
  </tr>

  <tr>
    <td>E-mail:</td>
    <td><input name="email2" type="text" id="email2" value="<?php echo $row_users['email']; ?>" size="40">
    </td>
  </tr>
  <tr>
    <td>Источник информации:</td>
    <td><input name="fsource" type="text" id="fsource" value="<?php echo $row_users['fsource']; ?>" size="40"></td>
  </tr> 
  <tr>
    <td>Группа:</td>
    <td><?php if($row_users['userlevel']==0){ echo '<font color=red>Не активирован</font>';}else{?>
	 <select name="group" id="group" onChange="if($(this).val()==4){$('#school<?=$row_users['primary_key']?>').slideDown();}else{$('#school<?=$row_users['primary_key']?>').slideUp();}">
      <?php
do {  
?>
      <option value="<?php echo $row_status['id']?>"<?php if($row_users['userlevel']==$row_status['id']){echo " selected";} ?>><?php echo $row_status['name']?></option>
      <?php
} while ($row_status = mysql_fetch_assoc($status));
  $rows = mysql_num_rows($status);
  if($rows > 0) {
      mysql_data_seek($status, 0);
	  $row_status = mysql_fetch_assoc($status);
  }
?>
    </select>
	<?php }?>    </td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><input name="Edit" type="submit" class="but" id="Edit" value="Сохранить"></td>
  </tr>
</table>
</div>
 </form>
 <?php } while ($row_users = mysql_fetch_assoc($users)); ?>
<p>&nbsp;</p>
<p>&nbsp;</p>
<table border="0" width="50%" align="center">
  <tr>
    <td width="23%" align="center"><?php if ($pageNum_users > 0) { // Show if not first page ?>
      <a href="<?php printf("%s?pageNum_users=%d%s", $currentPage, 0, $queryString_users); ?>">&laquo;</a>
      <?php } // Show if not first page ?></td>
    <td width="31%" align="center"><?php if ($pageNum_users > 0) { // Show if not first page ?>
      <a href="<?php printf("%s?pageNum_users=%d%s", $currentPage, max(0, $pageNum_users - 1), $queryString_users); ?>">&lt;</a>
      <?php } // Show if not first page ?></td>
    <td width="23%" align="center"><?php if ($pageNum_users < $totalPages_users) { // Show if not last page ?>
      <a href="<?php printf("%s?pageNum_users=%d%s", $currentPage, min($totalPages_users, $pageNum_users + 1), $queryString_users); ?>">&gt;</a>
      <?php } // Show if not last page ?></td>
    <td width="23%" align="center"><?php if ($pageNum_users < $totalPages_users) { // Show if not last page ?>
      <a href="<?php printf("%s?pageNum_users=%d%s", $currentPage, $totalPages_users, $queryString_users); ?>">&raquo;</a>
      <?php } // Show if not last page ?></td>
  </tr>
</table>
<?php }else{ echo "<h5><center>Пока никто не зарегистрирован.</center></h5>";}?>

<table width="90%" border="0" align="center" cellpadding="5" cellspacing="0">
  <tr>
    <td valign="top"><h5 align="center">Добавление нового пользователя</h5>
<table width="100%"  border="0" align="center" cellpadding="5" cellspacing="0" class="el_tbl">
      <form method="POST" action="<?php echo $editFormAction; ?>" name="newuser">
        <tr>
          <td width="29%" align="right">Логин:</td>
          <td width="71%"><input name="name2" type="text" id="name2" value="<?=$_POST['name2']?>"></td>
        </tr>
        <tr>
          <td align="right">Пароль:</td>
          <td><input name="pass" type="password" id="pass">
            (до 10 символов) </td>
        </tr>
        <tr>
          <td align="right">email:</td>
          <td><input name="email" type="text" id="email" value="<?=$_POST['email']?>"></td>
        </tr>
        <tr>
          <td align="right">Ф.И.О.</td>
          <td><input name="fio" type="text" id="fio" value="<?=$_POST['fio']?>"></td>
        </tr>
        <tr id="school" style="display:none">
          <td align="right">Школа: </td>
          <td><select name="school" id="schoolList">
          <option></option>
          <?
          $sc=el_dbselect("SELECT field1 FROM catalog_schools_data ORDER BY field1 ASC", 0, $sc);
		  $rsc=mysql_fetch_assoc($sc);
		  do{
			  $sel=($_POST['school']==$rsc['field1'])?' selected':'';
			  echo '<option value="'.$rsc['field1'].'"'.$sel.'>'.$rsc['field1'].'</option>'."\n";
		  }while($rsc=mysql_fetch_assoc($sc));
		  ?>
          </select>
          </td>
        </tr>
        <tr>
          <td align="right">Группа:</td>
          <td><select name="status" id="status" onChange="if($(this).val()==4){$('#school').slideDown();}else{$('#school').slideUp();}">
              <?php
do {  
?>
              <option value="<?php echo $row_status['id']?>" <?=($row_status['id']==$_POST['status'])?'selected':''?>><?php echo $row_status['name']?></option>
              <?php
} while ($row_status = mysql_fetch_assoc($status));
  $rows = mysql_num_rows($status);
  if($rows > 0) {
      mysql_data_seek($status, 0);
	  $row_status = mysql_fetch_assoc($status);
  }
?>
          </select></td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td><input name="Submit" type="submit" class="but" value="Добавить"></td>
        </tr>
        <input type="hidden" name="MM_insert" value="newuser">
      </form>
    </table>
    </td>
    <td valign="top"><h5 align="center">Добавление новой группы пользователей </h5>
      <table width="100%" border="0" cellpadding="3" cellspacing="0" class="el_tbl">
        <form method="post" name="addgroup">
		<tr>
          <td>Название группы </td>
          <td><input name="name" type="text" id="name">
            <input name="MM_insert" type="hidden" id="MM_insert" value="userstatus"></td>
        </tr>
        <tr>
          <td colspan="2" align="center"><input name="Submit2" type="submit" class="but" value="Добавить"></td>
        </tr></form>
    </table>
      <h5 align="center">Удаление группы пользователей </h5>
      <table width="100%" border="0" cellpadding="3" cellspacing="0" class="el_tbl">
        <form method="post" name="deletegroup" onSubmit="return del_group()">
          <tr>
            <td>Выберите группу </td>
            <td><select name="status" id="status">
            <option></option>
              <?php
do {  
?>
              <option value="<?php echo $row_status['id']?>" <?=($row_status['id']==$_POST['status'])?'selected':''?>><?php echo $row_status['name']?></option>
              <?php
} while ($row_status = mysql_fetch_assoc($status));
  $rows = mysql_num_rows($status);
  if($rows > 0) {
      mysql_data_seek($status, 0);
	  $row_status = mysql_fetch_assoc($status);
  }
?>
          </select>
              <input name="delGroup" type="hidden" value="1"></td>
          </tr>
          <tr>
            <td colspan="2" align="center"><input name="Submit3" type="submit" class="but" value="Удалить"></td>
          </tr>
        </form>
    </table></td>
  </tr>
</table>
<br>

</body>
</html>
<?php
mysql_free_result($users);

mysql_free_result($status);
?>
