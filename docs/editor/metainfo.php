<?php 
require_once('../Connections/dbconn.php'); 
$requiredUserLevel = array(1, 2); 
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php"); 

function changePath($idcat, $level, $new_dirname){
	$exCat=el_dbselect("SELECT id, path FROM cat WHERE parent='".$idcat."'", 0, $exCat);
	if(mysql_num_rows($exCat)>0){
		$rex=mysql_fetch_assoc($exCat);
		do{
			$dirArr=array();
			$new_path='';
			$dirArr=explode('/', $rex['path']);
			$dirArr[$level]=str_replace('/', '', $new_dirname);
			$new_path=implode('/', $dirArr);
			$ch=el_dbselect("SELECT id FROM cat WHERE parent='".$rex['id']."'", 0, $ch);
			if(mysql_num_rows($ch)>0){
				changePath($rex['id'], $level, $new_dirname);
			} 
			el_dbselect("UPDATE cat SET path='".$new_path."' WHERE id='".$rex['id']."'", 0, $pa);
			el_dbselect("UPDATE content SET path='".$new_path."' WHERE cat='".$rex['id']."'", 0, $pa);
		}while($rex=mysql_fetch_assoc($exCat));
	}
	el_clearcache('menu');
}

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "edit")) {
	if($_POST['kod']==''){
		el_2ini('cache'.$_POST['id'], 'Y');
	}else{
		el_2ini('cache'.$_POST['id'], 'N');
	}

$colname_db_content = "1";
if (isset($_GET['id'])) {
  $colname_db_content = (get_magic_quotes_gpc()) ? $_GET['id'] : addslashes($_GET['id']);
}
mysql_select_db($database_dbconn, $dbconn);
$query_db_content = sprintf("SELECT * FROM content WHERE cat = %s", $colname_db_content);
$db_content = mysql_query($query_db_content, $dbconn) or die(mysql_error());
$row_db_content = mysql_fetch_assoc($db_content);

	if(isset($_POST['edit'])){
	  if(count($_POST['edit'])>1 || count($_POST['view'])>1){
		  $editf = implode(",",$_POST['edit']);
		  $viewf = implode(",",$_POST['view']);
	  }else{
		  $editf = $_POST['edit'];
		  $viewf = $_POST['view'];
	  }	
	}else{
		$editf = $row_db_content['edit'];
		$viewf = $row_db_content['view'];
	}


$new_dirname=str_replace(strrchr($row_db_content['path'],"/"),"",$row_db_content['path'])."/".str_replace("/","",$_POST['path']);
($_POST['path']=='/')?$_POST['path']='':$_POST['path']=$_POST['path'];
($new_dirname=='/')?$new_dirname='':$new_dirname=$new_dirname;
	if($row_db_content['path']!=$new_dirname){
		if(!rename($_SERVER['DOCUMENT_ROOT'].$row_db_content['path'], $_SERVER['DOCUMENT_ROOT'].$new_dirname)){
			echo "<script>alert('Не удается переименовать директорию.')</script>";
		}else{
			changePath($_GET['id'], $_GET['lev'], $_POST['path']);
		}
	}

  $updateSQL = sprintf("UPDATE content SET path=%s, title=%s, description=%s, keywords=%s, caption=%s, kod=%s, template=%s, edit=%s, view=%s WHERE cat=%s",
                       GetSQLValueString($new_dirname, "text"),
					   GetSQLValueString(addslashes($_POST['title']), "text"),
                       GetSQLValueString(addslashes($_POST['description']), "text"),
                       GetSQLValueString(addslashes($_POST['keywords']), "text"),
					   GetSQLValueString(addslashes($_POST['caption']), "text"),
					   GetSQLValueString($_POST['kod'], "text"),
					   GetSQLValueString($_POST['template'], "text"),
					   GetSQLValueString($editf, "text"),
					   GetSQLValueString($viewf, "text"),
                       GetSQLValueString($_POST['id'], "int"));

  mysql_select_db($database_dbconn, $dbconn);
  $Result1 = mysql_query($updateSQL, $dbconn) or die(mysql_error());
  if($_POST['menu']!="Y"){$_POST['menu']="N";}else{$_POST['menu']="Y";}
  (strlen($_POST['redirect_out'])>0)?$_POST['redirect']=$_POST['redirect_out']:$_POST['redirect']=$_POST['redirect'];
  $updateSQL1 = sprintf("UPDATE cat SET path=%s, name=%s, sort=%s, menu=%s, edit=%s, view=%s, `left`=%s, `redirect`=%s WHERE id=%s",
					   GetSQLValueString($new_dirname, "text"),
					   GetSQLValueString($_POST['name'], "text"),
					   GetSQLValueString($_POST['sort'], "int"),
					   GetSQLValueString($_POST['menu'], "text"),
					   GetSQLValueString($editf, "text"),
					   GetSQLValueString($viewf, "text"),
					   GetSQLValueString($_POST['left'], "text"),
					   GetSQLValueString($_POST['redirect'], "text"),
                       GetSQLValueString($_POST['id'], "int"));

  mysql_select_db($database_dbconn, $dbconn);
  $Result2 = mysql_query($updateSQL1, $dbconn) or die(mysql_error());
  el_clearcache('pages', $_POST['id']);
  el_clearcache('menu');
  echo "<script language=javascript>
  alert('Внесенные изменения сохранены!');
  </script>";
  
  if(substr_count($_POST['kod'], 'catalog')>0){
  	$catEx=el_dbselect("SELECT id FROM catalogs WHERE catalog_id='".str_replace('catalog', '', $_POST['kod'])."'", 0, $catEx, 'row');
	if(strlen($catEx['id'])>0){
		el_dbselect("UPDATE catalogs SET cat='".$_POST['id']."' WHERE id=".$catEx['id'], 0, $res);
	}
  }
}

$colname_db_content = "1";
if (isset($_GET['id'])) {
  $colname_db_content = (get_magic_quotes_gpc()) ? $_GET['id'] : addslashes($_GET['id']);
}
mysql_select_db($database_dbconn, $dbconn);
$query_db_content = sprintf("SELECT * FROM content WHERE cat = %s", $colname_db_content);
$db_content = mysql_query($query_db_content, $dbconn) or die(mysql_error());
$row_db_content = mysql_fetch_assoc($db_content);
$totalRows_db_content = mysql_num_rows($db_content);

mysql_select_db($database_dbconn, $dbconn);
$query_db_contentfirst = "SELECT keywords FROM content WHERE cat = 1";
$db_contentfirst = mysql_query($query_db_contentfirst, $dbconn) or die(mysql_error());
$row_db_contentfirst = mysql_fetch_assoc($db_contentfirst);
$totalRows_db_contentfirst = mysql_num_rows($db_contentfirst);

$page_url="http://".$_SERVER['SERVER_NAME'].$row_db_content['path'];
$page_name=$row_db_content['caption'];

mysql_select_db($database_dbconn, $dbconn);
$query_modules = "SELECT * FROM modules ORDER BY sort ASC";
$modules = mysql_query($query_modules, $dbconn) or die(mysql_error());
$row_modules = mysql_fetch_assoc($modules);

mysql_select_db($database_dbconn, $dbconn);
$query_tmpl = "SELECT * FROM template WHERE `master`<>1";
$tmpl = mysql_query($query_tmpl, $dbconn) or die(mysql_error());
$row_tmpl = mysql_fetch_assoc($tmpl);

mysql_select_db($database_dbconn, $dbconn);
$query_users = "SELECT * FROM userstatus";
$users = mysql_query($query_users, $dbconn) or die(mysql_error());
$row_users = mysql_fetch_assoc($users);
$totalRows_users = mysql_num_rows($users);

mysql_select_db($database_dbconn, $dbconn);
$views = mysql_query($query_users, $dbconn) or die(mysql_error());
$row_views= mysql_fetch_assoc($views); 

mysql_select_db($database_dbconn, $dbconn);
$query_menu = "SELECT * FROM cat WHERE id='".$_GET['id']."'";
$menu= mysql_query($query_menu, $dbconn) or die(mysql_error());
$row_menu = mysql_fetch_assoc($menu);

?>
<html>
<head>
<title>Свойства раздела "<?=$row_db_content['caption']; ?>"</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<style type="text/css">
<!--
body {
	background-color: #FFFFFF;
}
-->
</style>
<link href="style.css" rel="stylesheet" type="text/css">
<script language="javascript">
function openAcc(d){
	var layer=document.getElementById("accs");
	if(d==0){
		layer.style.display="none";
		for(i=0; i<layer.children.length; i++){
			if(layer.children[i].tagName=="INPUT"){
				layer.children[i].checked=false;
			}
		}
	}else if(d==1){
		layer.style.display="block";
	}
}
</script>

</head>

<body>
<table width="100%"  border="0" cellspacing="0" cellpadding="5"class="el_tbl">
  <form method="POST" action="<?php echo $editFormAction; ?>" name="edit" >
   <tr valign="top">
    <td align="right">Адрес раздела: </td>
    <td colspan="2"><a href="<?=$page_url?>" target="_blank"><?=$_SERVER['SERVER_NAME'].str_replace(strrchr($row_db_content['path'],"/"),"",$row_db_content['path'])?></a><input name="path" type="text" id="path" value="<?php echo strrchr($row_db_content['path'],"/"); ?>">
      <input name="id" type="hidden" id="id" value="<?php echo $row_db_content['cat']; ?>">
   </td>
  </tr>
<tr>
  <td align="right">Название раздела : </td>
  <td><input name="name" type="text" id="name" value="<?php echo str_replace('\"', '``', $row_menu['name']); ?>" size="50"></td>
</tr>
 <tr valign="top">
    <td align="right">Заголовок окна страницы(&lt;title&gt;): </td>
    <td colspan="2"><input name="title" type="text" id="title" value="<?php echo str_replace('\"', '``', $row_db_content['title']); ?>" size="50"></td>
  </tr>
   <tr valign="top">
    <td align="right">Заголовок над текстом: </td>
    <td colspan="2"><input name="caption" type="text" id="caption" value="<?php echo str_replace('\"', '``', $row_db_content['caption']); ?>" size="50"></td>
  </tr>

  <tr valign="top">
    <td align="right">Описание страницы(&lt;description&gt;): </td>
    <td colspan="2"><textarea name="description" cols="40" rows="2" id="description"><?php echo str_replace('\"', '``', $row_db_content['description']); ?></textarea></td>
  </tr>
  <tr valign="top">
    <td align="right">Ключевые слова через запятую(&lt;keywords&gt;): <br></td>
    <td colspan="2"><textarea name="keywords" cols="40" rows="5" id="keywords"><?php if(strlen($row_db_content['keywords'])<1){echo str_replace('\"', '``', $row_db_contentfirst['keywords']);}else{echo str_replace('\"', '``', $row_db_content['keywords']);} ?></textarea></td>
  </tr>
  <tr valign="top">
    <td align="right">Используемый модуль:</td>
    <td colspan="2"><select name="kod" id="kod">
  <?php
do { if($row_modules['status']=="Y"){  
?>
  <option value="<?php echo $row_modules['type']?>" <? if($row_db_content['kod']==$row_modules['type']){echo "selected"; $mname=$row_modules['name'];} ?>><?php echo $row_modules['name']?></option>
  <?php }
} while ($row_modules = mysql_fetch_assoc($modules));
  $rows = mysql_num_rows($modules);
  if($rows > 0) {
      mysql_data_seek($modules, 0);
	  $row_modules = mysql_fetch_assoc($modules);
  }
?>
</select></td>
  </tr>
  <tr valign="top">
    <td align="right">Шаблон дизайна:</td>
    <td colspan="2"><select name="template" id="template">
  <?php
do {  
?>
  <option value="<?php echo $row_tmpl['path']?>" <? if($row_db_content['template']==$row_tmpl['path']){echo "selected";} ?>><?php echo $row_tmpl['name']?></option>
  <?php
} while ($row_tmpl = mysql_fetch_assoc($tmpl));
  $rows = mysql_num_rows($tmpl);
  if($rows > 0) {
      mysql_data_seek($tmpl, 0);
	  $row_tmpl = mysql_fetch_assoc($tmpl);
  }
?>
</select></td>
  </tr>
<tr>
  <td align="right">Порядковый номер в меню: </td>
  <td><input name="sort" type="text" id="sort" value="<?php echo str_replace('\"', '``', $row_menu['sort']); ?>" size="5"></td>
</tr>
<tr>
  <td align="right">Показывать в меню: </td>
  <td><input <?=($row_menu['menu']=='Y')?"checked":""?> name="menu" type="checkbox" id="menu" value="Y"></td>
</tr>
<tr>
  <td align="right">Открывать раздел в новом окне: </td>
  <td><input <?=($row_menu['left']=='Y')?"checked":""?> name="left" type="checkbox" id="left" value="Y"></td>
</tr>
<tr>
  <td align="right" valign="top">Редирект на: </td>
  <td><input name="redirect_out" type="text" id="redirect_out" value="<?=$row_menu['redirect']?>" size="50">
    <br>
    
  или на свой раздел:
  <select name="redirect">
  <option></option>
  <? //el_pageSelect('', $row_menu['redirect']) ?>
  </select></td>
</tr>
<? if($userLevel=="1"){?>  
  <tr valign="top">
    <td align="right">Группы, которым разрешен доступ на редактирование этого раздела: </td>
    <td colspan="2">
	<? 
$aredit=explode(",",$row_db_content['edit']); 
do{ ?>
	<input name="edit[<?=$row_users['id']?>]" type="checkbox" id="edit[<?=$row_users['id']?>]" value="<?=$row_users['id']?>" <?=(in_array($row_users['id'], $aredit))?"checked":""?>> <?=$row_users['name']?><br>
	<? }while($row_users = mysql_fetch_assoc($users));?>	</td>
  </tr>
<tr valign="top">
    <td align="right">Группы, которым разрешен доступ на просмотр этого раздела:<br>
      (если не отмечена ни одна группа, то доступ будет только у администратора) </td>
    <td colspan="2">
	  <label>
	    <input type="radio" name="access" value="0" onClick="openAcc(0)" <?=(strlen($row_db_content['view'])<1)?'checked="checked"':''?>>
	    Общий доступ</label>
	  <br>
	  <label>
	    <input type="radio" name="access" value="1" onClick="openAcc(1)" <?=(strlen($row_db_content['view'])>0)?'checked="checked"':''?>>
	    Ограниченный доступ</label>
	  <div style="display:<?=(strlen($row_db_content['view'])>0)?'block':'none'?>" id="accs">
	<? 
	$arview=explode(",",$row_db_content['view']); 
	 do{ ?>
	<input name="view[<?=$row_views['id']?>]" type="checkbox" id="view[<?=$row_views['id']?>]" value="<?=$row_views['id']?>" <?=(in_array($row_views['id'], $arview))?"checked":""?>> <?=$row_views['name']?><br>
	<? }while($row_views = mysql_fetch_assoc($views));?>	
	</div>
	</td>
  </tr>  <? }?>
  <tr valign="top">
    <td colspan="3" align="center"><input name="Submit" type="submit" class="but" value="Сохранить">
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;      
      <input name="Submit2" type="button" class="but" onClick="window.close()" value="Закрыть"></td>
    </tr>
  <input type="hidden" name="MM_update" value="edit">
</form>
</table>
</body>
</html>
<?php
mysql_free_result($db_content);

mysql_free_result($db_contentfirst);
?>

