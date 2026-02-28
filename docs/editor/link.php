<?php require_once('../Connections/dbconn.php'); ?>
<?PHP $requiredUserLevel = array(1, 2); 
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php"); ?>
<?php
/*mysql_select_db($database_dbconn, $dbconn);
$query_pages = "SELECT id, cat, caption FROM content ORDER BY cat ASC";
$pages = mysql_query($query_pages, $dbconn) or die(mysql_error());
$row_pages = mysql_fetch_assoc($pages);
$totalRows_pages = mysql_num_rows($pages);*/

$colname_text = "-1";
if (isset($_GET['id'])) {
  $colname_text = (get_magic_quotes_gpc()) ? $_GET['id'] : addslashes($_GET['id']);
}
mysql_select_db($database_dbconn, $dbconn);
$query_text = sprintf("SELECT * FROM content WHERE cat = %s", $colname_text);
$text = mysql_query($query_text, $dbconn) or die(mysql_error());
$row_text = mysql_fetch_assoc($text);
$totalRows_text = mysql_num_rows($text);
$textstr=$row_text['text'];
 switch($_GET['mode']){
	case 'files': $wi=800; $he=730; break;
	case 'parts': $wi=730; $he=600; break;
	case 'out': $wi=730; $he=250; break;
	default: $wi=730; $he=250; break;
}?>

<html> 
<head>
<title>Вставка гиперссылки</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<link href="style.css" rel="stylesheet" type="text/css">
<script type="text/JavaScript">
<!--
function MM_openBrWindow(theURL,winName,features) { //v2.0
  window.open(theURL,winName,features);
}
//-->
</script>
</head>
<script language=Javascript>
var url;
function kodlink(adress){
//url=document.all.pagesurl.value;
url=adress;
document.getElementById("urltext").value=adress;
}

function showdiv(){
	if(document.all.Target.value==" popup"){
		document.getElementById("popdiv").style.display="block";
		window.resizeTo(<?=$wi?>,<?=$he?>+30);
	}else{
		document.getElementById("popdiv").style.display="none";
		window.resizeTo(<?=$wi?>,<?=$he?>);
	}
}

function AddLink() {
<? if($_GET['mode']=='out' || $_GET['mode']==''){?>
var adres=document.all.Path.value;
<? }else{?>
var adres=document.getElementById("urltext").value;
<? }?>
if(document.all.Target.value==" target=_self" || document.all.Target.value==" target=_blank"){
	AnCode = '<a href='+document.all.Protocol.value+adres+document.all.Target.value+'>'+window.opener.frames.message.document.selection.createRange().text+'</a>';
}else if(document.all.Target.value==" popup"){
	AnCode = '<a href="javascript:void(0)" onClick="javascript:window.open(\''+document.all.Protocol.value+adres+'\',\'newwin\',\'scrollbars=yes,resizable=yes,width='+document.all.widt.value+',height='+document.all.heig.value+'\')">'+window.opener.frames.message.document.selection.createRange().text+'</a>';
}
 	try{
		var range = window.opener.frames.message.document.selection.createRange();
		range.pasteHTML(AnCode);
		range.select();
		range.execCommand();
		window.close();	
		window.opener.frames.message.document.focus();
	}catch(Error){
		alert("Выделите текст для вставки ссылки.");
		return false;
	}
}

function CheckText(){//alert(window.opener.frames.message.document.selection.createRange().text);
	if(window.opener.frames.message.document.selection.createRange().text==undefined ||
	window.opener.frames.message.document.selection.createRange().text==""){
		alert("Пожалуйста, выделите текст для вставки ссылки.");
		window.close();
	}
}

</script> 
<body onLoad="window.resizeTo(<?=$wi?>,<?=$he?>), CheckText()">
<? 
$tabs=array('out'=>'На другой сайт','parts'=>'На свой раздел','files'=>'На свой файл');
el_tabs($tabs, $_GET, 'mode', 'out') ?>

<table width=100% bgcolor="#FFFFFF">
  <tr><td>Тип: 
    <select name=Protocol>
      <option value=>Другой</option>
      <option value="http://"selected>http://</option>
      <option value="file://">file://</option> 
      <option value="ftp://">ftp://</option> 
      <option value="https://">https://</option> 
      <option value="mailto:">mailto:</option>
      <option value="gopher://">gopher://</option> 
      <option value="news:">news:</option>
      <option value="telnet:">telnet:</option>
      <option value="wais:">wais:</option>
    </select></td></tr><tr><td>
	  <? if($_GET['mode']=='out' || $_GET['mode']==''){ ?>
	  Укажите адрес внешнего ресурса в этом поле без протокола (пример www.site.ru/page.htm) <br>
      <input size=80 name=Path>
	  <? }?>
      <br><? if($_GET['mode']=='parts'){ ?>
      Выберите раздел, на который делается ссылка. Кликните по его названию и нажмите кнопку "Вставить".<br>
<?
 $imenu=0;
function el_menuadmin(){//Parent items, first level only
global $database_dbconn;
global $dbconn;
global $SERVER_NAME;
global $textstr;
mysql_select_db($database_dbconn, $dbconn);
$querymenutree = "SELECT * FROM cat WHERE parent=0 ORDER BY sort ASC";	
$menutree = mysql_query($querymenutree, $dbconn) or die(mysql_error());
$row_menutree = mysql_fetch_assoc($menutree);
$end1= mysql_num_rows($menutree);
$n1=0;
do{
$n1++;
if($n1==$end1){$img1="vetkaend.gif";}else{$img1="vetka.gif";}
$parent=$row_menutree['id'];
$child=mysql_query("SELECT * FROM cat WHERE parent='$parent'", $dbconn);
?>
      <table width="100%" height="20"  border="0" cellpadding="0" cellspacing="0">
        <tr id="<?php echo $row_menutree['id']; ?>" onMouseOver='document.getElementById("<?php echo $row_menutree['id']; ?>").style.backgroundColor="#E7E7E7"' onMouseOut='document.getElementById("<?php echo $row_menutree['id']; ?>").style.backgroundColor=""' style="cursor:pointer">
          <td width="13" valign="top"><img src="/editor/img/<?=$img1  ?>" border=0 align=middle></td>
          <td style="font-weight:bold;"><span onClick="kodlink('<?php echo $_SERVER['SERVER_NAME'].$row_menutree['path']; ?>')"><a name="<? echo $row_menutree['id']; ?>"></a><?php echo $row_menutree['name']; ?></span> <span style="font-size:80%; font-weight:normal;"> <a href="/editor/link.php?paragraf=y&mode=parts&id=<? echo $row_menutree['id']; ?>#<? echo $row_menutree['id']; ?>">[якоря]</a></span>
            <? if(($_GET['paragraf']=='y')&&($_GET['id']==$row_menutree['id'])){ el_anchor_print($_SERVER['SERVER_NAME'].$row_menutree['path'],$textstr);} ?> </td>
        </tr>
   </table>
      <?
menuadminchild($parent, 'cat');
}while($row_menutree = mysql_fetch_assoc($menutree));
mysql_free_result($menutree);
}

function menuadminchild($parent, $table){//Child Items
global $database_dbconn;
global $dbconn;
global $imenu;
global $SERVER_NAME;
global $textstr;
$querymenuchild = "SELECT * FROM cat WHERE parent='$parent' ORDER BY sort ASC";
$menuchild = mysql_query($querymenuchild, $dbconn) or die(mysql_error());
$row_menuchild = mysql_fetch_assoc($menuchild);
$end= mysql_num_rows($menuchild);
$idchild=$row_menuchild['id'];
if($idchild){//if item is exist...
$imenu++;
$parentchild=mysql_query("SELECT * FROM cat WHERE parent='$idchild'", $dbconn);
echo "<div id=\"menudiv".$row_menuchild['parent']."\">\n";
$n=0;
do{
$n++;
if($n==$end){$img="vetkaend.gif";}else{$img="vetka.gif";}
?>
<table width="90%" height="20"  border="0" cellpadding="0" cellspacing="0" style="margin:-1px -1px -1px 0px">
        <tr id="<?php echo $row_menuchild['id']; ?>" onMouseOver='document.getElementById("<?php echo $row_menuchild['id']; ?>").style.backgroundColor="#E7E7E7"' onMouseOut='document.getElementById("<?php echo $row_menuchild['id']; ?>").style.backgroundColor=""' style="cursor:pointer">
          <td width="6" valign="top" background="/editor/img/vetkaline.gif"><img src="/editor/img/vetkaline.gif" width="11" height="20" border="0" align="middle"></td>
          <td width="7" valign="top"><img src="/editor/img/<?=$img  ?>" border=0 align=middle></td>
          <td><span onClick="kodlink('<?php echo $_SERVER['SERVER_NAME'].$row_menuchild['path']; ?>')">   <a name="<? echo $row_menuchild['id']; ?>"></a><?php echo $row_menuchild['name']; ?></span> <span style="font-size:80%; font-weight:normal;"> <a href="/editor/link.php?paragraf=y&mode=parts&id=<? echo $row_menuchild['id']; ?>#<? echo $row_menuchild['id']; ?>">[якоря]</a></span>
          <? if(($_GET['paragraf']=='y')&&($_GET['id']==$row_menuchild['id'])){el_anchor_print($_SERVER['SERVER_NAME'].$row_menuchild['path'],$textstr);} ?></td>
        </tr>
      <input type="hidden" name="MM_update" value="edit">
    <tr><td colspan="2"></form></table>
<? 
menuadminchild($row_menuchild['id'],$table);
}while($row_menuchild = mysql_fetch_assoc($menuchild));
mysql_free_result($menuchild);
echo "</div>\n"; 
 }
} 
?> 
<div style="overflow:scroll; width:600px; height:300px; border:1px solid gray; padding-left:5px" name="pagesurl" id="pagesurl">
<? el_menuadmin() ?>	  
</div>  
<input name="urltext" type="text" id="urltext" size="80">
<br>
<? }
if($_GET['mode']=='files'){
	define('LINK',1);
	include $_SERVER['DOCUMENT_ROOT'].'/editor/upfile.php' 
?>	  

<input name="urltext" type="text" id="urltext" size="80">
<br>
<? }?>
<br></td>
</tr><tr><td>Открытие:
  <select name=Target onChange="showdiv()">
  <option value=' target=_self' selected>В этом же окне</option>
  <option value=' target=_blank'>В новом окне</option>
  <option value=' popup'>В pop-up окне</option>
  </select>
  <div id="popdiv" style="display:none"><br>
  Ширина окна: <input name="widt" type="text" size="3"> 
  &nbsp;&nbsp;&nbsp;Высота окна: 
  <input name="heig" type="text" id="heig" size="3">
  <br><br></div>
  </td></tr></table>
<center><input type=button value=Вставить OnClick="AddLink()" class="but">
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <input type="button" name="Button" value="Закрыть" onClick="window.close();" class="but">
</center>
</body> 
</html>
<?php
mysql_free_result($text);
?>
