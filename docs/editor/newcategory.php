<?php require_once('../Connections/dbconn.php'); 
//error_reporting(E_ALL);
$requiredUserLevel = array(1, 2); 
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php"); 

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . $_SERVER['QUERY_STRING'];
}
//create_cat($_GET['cat']);

#################################################################################################################
$foldexist=''; 
	if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "form1")) {
	$parid=$_POST['parent'];
	if(function_exists('el_translit')){
		$_POST['path']=el_translit($_POST['path']);
	}
	mysql_select_db($database_dbconn, $dbconn);
	 $parentfolder=mysql_query("select * from cat where id='$parid'", $dbconn);
	 $parentfold = mysql_fetch_assoc($parentfolder);
		 if($parentfold['path']){$parentf=$parentfold['path'];}else{$parentf="";}
	$rootfolder=$_SERVER['DOCUMENT_ROOT'];
	 if (file_exists($rootfolder.$parentf."/".$_POST['path'])){
	 	echo '<center><h5 style="color:red">Папка с таким названием уже существует. Выберите другое название.</h5></center>';
		$foldexist=1;
	}else{
		mkdir($rootfolder.$parentf."/".$_POST['path'], 0777);
	}
	$newpath=$parentf."/".$_POST['path'];
	mysql_free_result($parentfolder);
	if(!copy($rootfolder."/tmpl/index.php",$rootfolder.$newpath."/index.php")){
		rmdir($rootfolder.$parentf."/".$_POST['path']);
		mkdir($rootfolder.$parentf."/".$_POST['path'], 0777);
		copy($rootfolder."/tmpl/index.php",$rootfolder.$newpath."/index.php");
	}
	//chmod($rootfolder.$newpath."/index.php", 0755); 
	}
	if($foldexist!=1){
	if(!$_POST['menu']){$_POST['menu']="Y";}
	if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "form1")) {
	  $insertSQL = sprintf("INSERT INTO cat (parent, name, `path`, menu, ptext, sort) VALUES (%s, %s, %s, %s, %s, %s)",
						   GetSQLValueString($_POST['parent'], "int"),
						   GetSQLValueString($_POST['name'], "text"),
						   GetSQLValueString($newpath, "text"),
						   GetSQLValueString($_POST['menu'], "text"),
						   GetSQLValueString($_POST['ptext'], "text"),
						   GetSQLValueString($_POST['sort'], "int"));
	
	  mysql_select_db($database_dbconn, $dbconn);
	  $Result1 = mysql_query($insertSQL, $dbconn) or die(mysql_error());
	//Определяем id новой записи
	  $parid=$_POST['parent'];
	mysql_select_db($database_dbconn, $dbconn);
	 $parentfolder=mysql_query("select * from cat where path='$newpath'", $dbconn);
	 $parentfold = mysql_fetch_assoc($parentfolder);
	 $idnew=$parentfold['id'];
	mysql_free_result($parentfolder);
	}
	if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "form1")) {
	  $insertSQL = sprintf("INSERT INTO content (cat, `path`, text, caption, title, description, kod, template) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)",
						   GetSQLValueString($idnew, "int"),
						   GetSQLValueString($newpath, "text"),
						   GetSQLValueString($_POST['contenttext'], "text"),
						   GetSQLValueString($_POST['name'], "text"),
						   GetSQLValueString($_POST['name'], "text"),
						   GetSQLValueString($_POST['text'], "text"),
						   GetSQLValueString($_POST['kod'], "text"),
						   GetSQLValueString($_POST['template'], "text"));
	
	  mysql_select_db($database_dbconn, $dbconn);
	  $Result1 = mysql_query($insertSQL, $dbconn) or die(mysql_error());
		}
		el_log('Создан раздел &laquo;'.$_POST['name'].'&raquo;', 1);
		el_clearcache('menu');

	}
#################################################################################################################

mysql_select_db($database_dbconn, $dbconn);
$query_cat = "SELECT * FROM cat";
$cat = mysql_query($query_cat, $dbconn) or die(mysql_error());
$row_cat = mysql_fetch_assoc($cat);
$totalRows_cat = mysql_num_rows($cat);

mysql_select_db($database_dbconn, $dbconn);
$query_typepage = "SELECT * FROM modules ORDER BY sort ASC";
$typepage = mysql_query($query_typepage, $dbconn) or die(mysql_error());
$row_typepage = mysql_fetch_assoc($typepage);
$totalRows_typepage = mysql_num_rows($typepage);

mysql_select_db($database_dbconn, $dbconn);
$query_template = "SELECT * FROM template WHERE `master`<>1";
$template = mysql_query($query_template, $dbconn) or die(mysql_error());
$row_template = mysql_fetch_assoc($template);
$totalRows_template = mysql_num_rows($template);

$colname_parent = "1";
if (isset($_GET['parentid'])) {
  $colname_parent = (get_magic_quotes_gpc()) ? $_GET['parentid'] : addslashes($_GET['parentid']);
}
mysql_select_db($database_dbconn, $dbconn);
$query_parent = sprintf("SELECT id, name FROM cat WHERE id = %s", $colname_parent);
$parent = mysql_query($query_parent, $dbconn) or die(mysql_error());
$row_parent = mysql_fetch_assoc($parent);
$totalRows_parent = mysql_num_rows($parent);
?>
 
<html>
<head>
<title>Создание нового раздела в меню</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<script language="JavaScript" type="text/JavaScript">
<!--
function MM_goToURL() { //v3.0
  var i, args=MM_goToURL.arguments; document.MM_returnValue = false;
  for (i=0; i<(args.length-1); i+=2) eval(args[i]+".location='"+args[i+1]+"'");
}

function MM_openBrWindow(theURL,winName,features, myWidth, myHeight, isCenter) { //v3.0
  if(window.screen)if(isCenter)if(isCenter=="true"){
    var myLeft = (screen.width-myWidth)/2;
    var myTop = (screen.height-myHeight)/2;
    features+=(features!='')?',':'';
    features+=',left='+myLeft+',top='+myTop;
  }
  window.open(theURL,winName,features+((features!='')?',':'')+'width='+myWidth+',height='+myHeight);
}

function checkForm(){
	if(form1.name.value.length==0){
		alert("Укажите название раздела!");
		return false
	}else if(form1.path.value.length==0){
		alert("Укажите название новой папки!");
		return false;
	}else{
		return true;
	}
}
//-->
</script>
<link href="style.css" rel="stylesheet" type="text/css">
<style type="text/css">
<!--
.style1 {
	font-size: 12px;
	font-style: italic;
	color: #006633;
}
.style2 {
	color: #FF0000;
	font-weight: bold;
}
.style3 {color: #FF0000}
-->
</style>
</head>

<body bgcolor="#FFFFFF">
<h4> Создание страницы и определение ее параметров.</h4>
<? if ((isset($_POST['Submit']))&&($foldexist!=1)) { ?><center><!-- <form action="new.php" method="post" name="sendnew" target="_self"> -->
<? 
$parentid=$_POST['parent'];
$catname=el_dbselect("select * from cat where id=$parentid", 0, $res);
$row=mysql_fetch_array($catname);
$namecat=$row['name']; 
$name=$_POST["name"];
 ?>
  Создана страница <? echo "<b>&#8220;".$name."&#8221;</b>" ?> в разделе <? if($_GET['parentid']==0){$namecat="Главное меню";} echo "<b>&#8220;".$namecat."&#8221;</b>" ; ?> !
  <br>
    <input name="catname" type="hidden" id="catname" value="<? 
$catnameid=el_dbselect("select * from cat where name='$name'", 0, $res);
$row=mysql_fetch_array($catnameid);
$idcat=$row['id'];
	echo $idcat;
	?>">
    <input name="name" type="hidden" id="name" value="<? 
	$name1=$_POST["name"];
	echo $name1;
	?>">
    <input name="namecat" type="hidden" id="namecat" value="<? echo $namecat; ?>">
    <? 	mysql_free_result($catname); ?> <br>
    <input name="Submit2" type="button" class="but" onClick="MM_goToURL('self','newcategory.php?parentid=<?=$parentid?>');return document.MM_returnValue" value="Создать еще">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;    
<input name="Next" type="button" class="but" onClick="opener.location.href='menuadmin.php';window.close()"  value="Закрыть">
  <!-- </form> -->
   </center>
<? } else { ?>
<form method="POST" name="form1" action="<?php echo $editFormAction; ?>" onSubmit="return checkForm()">
  <table width="95%" align="center" class="el_tbl">
    <tr valign="baseline"> 
      <td align="right" valign="top" nowrap>Вставить в раздел:</td>
      <td>        <input type="hidden" name="parent" value="<? echo $_GET['parentid'] ?>" size="32">
        <input name="contenttext" type="hidden" id="contenttext" value="">
&laquo;        <strong><?php if($_GET['parentid']==0){echo "Главное меню";}else{echo $row_parent['name'];} ?></strong> &raquo;</td>
    </tr>
    <tr valign="baseline"> 
      <td align="right" nowrap>Название<span class="style3">*</span>:</td>
      <td><input type="text" name="name" value="" size="32"></td>
    </tr>
    <tr valign="baseline">
      <td align="right" valign="top" nowrap>Описание:<br>
        <span class="style1">(не обязательное поле)</span> </td>
      <td valign="top"><textarea name="ptext" cols="40" rows="5" id="ptext"></textarea>
      <img onClick="MM_openBrWindow('newcatditor.php?field=ptext','newcateditor','','785','625','true')" src="img/code.gif" alt="HTML-редактор" width="21" height="20" border="0" style="cursor:pointer; "></td>
    </tr>
    <tr valign="baseline">
      <td align="right" valign="top" nowrap>Название папки <br> 
      одним словом (<span class="style2">обязательно</span>),<br> 
      используйте все маленькие латинские буквы<span class="style3">*</span>: </td>
      <td valign="bottom"><input name="path" type="text" id="path" size="32"></td>
    </tr>
    <tr valign="baseline">
      <td align="right" valign="top" nowrap>Порядковый номер в меню: </td>
      <td valign="top"><input name="sort" type="text" id="sort" value="100" size="5"></td>
    </tr>
    <tr valign="baseline">
      <td align="right" valign="top" nowrap>Тип страницы: </td>
      <td valign="top"><select name="kod" id="kod">
        <?php
do { if($row_typepage['status']=="Y"){ 
?>
        <option value="<?php echo $row_typepage['type']?>"><?php echo $row_typepage['name']?></option>
        <?php
}} while ($row_typepage = mysql_fetch_assoc($typepage));
  $rows = mysql_num_rows($typepage);
  if($rows > 0) {
      mysql_data_seek($typepage, 0);
	  $row_typepage = mysql_fetch_assoc($typepage);
  }
?>
      </select></td>
    </tr>
    <tr valign="baseline">
      <td align="right" valign="top" nowrap>Шаблон страницы: </td>
      <td valign="top"><select name="template" id="template">
        <?php
do {  
?>
        <option value="<?php echo $row_template['path']?>" <?=($row_template['default']==1)?'selected':''?>><?php echo $row_template['name']?></option>
        <?php
} while ($row_template = mysql_fetch_assoc($template));
  $rows = mysql_num_rows($template);
  if($rows > 0) {
      mysql_data_seek($template, 0);
	  $row_template = mysql_fetch_assoc($template);
  }
?>
      </select></td>
    </tr>
    <tr valign="baseline">
      <td align="right" nowrap>Не показывать  в меню: </td>
      <td><p>
        <input name="menu" type="checkbox" id="menu" value="N">
        
       
      </p></td>
    </tr>
    <tr valign="baseline"> 
      <td nowrap align="right"><br>
      <input name="Submit" type="submit" class="but" value="Создать"></td>
      <td> 

<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input name="closewin" type="button" class="but" id="closewin" onClick="opener.location.href='menuadmin.php';window.close()" value="Закрыть"></td>
    </tr>
  </table>
  <center><span class="style3">*</span>-поля отмеченный звездочкой заполняются обязательно.</center>
  <input type="hidden" name="MM_insert" value="form1">
</form>

<? } ?>
</body>
</html>
<?php
mysql_free_result($cat);

mysql_free_result($typepage);

mysql_free_result($template);

mysql_free_result($parent);
?>