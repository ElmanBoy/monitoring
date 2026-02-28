<?php require_once('../Connections/dbconn.php'); ?>
<?PHP $requiredUserLevel = array(1, 2); 
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php"); ?>
<?php
mysql_select_db($database_dbconn, $dbconn);
$query_pages = "SELECT id, cat, `path`, caption FROM content ORDER BY cat ASC";
$pages = mysql_query($query_pages, $dbconn) or die(mysql_error());
$row_pages = mysql_fetch_assoc($pages);
$totalRows_pages = mysql_num_rows($pages);
?>
<html>
<head>
<title>Вставка изображения</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<script language="JavaScript">
<!--
function CheckText(){
	if(window.opener.frames.message.document.selection.createRange().text==undefined){
		alert("Пожалуйста, выделите область для вставки изображения.");
		window.close();
	}
}


var itemsrc, kodht;
function prewiev(imgsrce) {
//itemfile=window.document.form1.select.options[window.document.form1.select.selectedIndex].text;
itemsrc="../images/"+imgsrce;
document.getElementById('prew').src=itemsrc;
imagePath=itemsrc;
document.form1.imgpath.value=itemsrc;
window.document.form1.sorce.value=itemsrc;
}
<?
if (isset($Submit)){
	if((empty($_POST['imagename']))&&(empty($file_name))){
	echo "alert('Вы не указали изображение!');
 window.location.href('/editor/addimage.php');";} 
?>
function createhtml(){
imgname="<? if(empty($_POST['imagename'])){echo $_FILES['file']['name'];}else{echo $_POST['imagename'];} ?>";
<? if (!empty($_FILES['file']['name'])) {?>
var itemsrc='http://<?=$_SERVER['SERVER_NAME'].'/images/'.$_FILES['file']['name']?>';
<? }else{ ?>
var itemsrc='http://'+imgname;
<? }?>
var kod;
<? if ($_POST['Target']==""){ ?>
kodht='<a href="javascript:void(0)" onClick="window.open(\'<?="http://".$_SERVER['SERVER_NAME'].$_POST['biglink']?>\',\'bigimage\',\'scrollbars=yes,resizable=yes,width=<?=$_POST['width']?>,height=<?=$_POST['height']?>\')"><img src="'+itemsrc+'" border=0 hspace=10 vspace=5 align="<?=$_POST['alselect']?>" alt="<?=$_POST['alttext']?>"></a>';
<? }else{
 if ($_POST['Path']=="") {?>
 adres='<?=$_POST['pagesurl']?>';
<? }else{?>
adres='<?=$_POST['Path']?>';
<? }?>
	AnCode = '<a href='+'<?=$_POST['Protocol']?>'+adres+'<?=$_POST['Target']?>'+'>';
kod='<img src='+itemsrc+' border=0 hspace=10 vspace=5 align='+'<?=$_POST['alselect']?>'+' alt=\"'+'<?=$_POST['alttext']?>'+'\">';
<? if (($_POST['Path']!="")||($_POST['pagesurl']!="")){?>
kodht=AnCode+kod+'</a>';
<? }else{?>
kodht=kod;
<? }}?>
window.opener.AddHTML(kodht); 
}
<? $uploaddir=$_SERVER['DOCUMENT_ROOT']."/images/";
if (!empty($_FILES['file']['name'])) {
$valid=stristr($_FILES['file']['name'],'.');
if(($valid==".gif")||($valid==".jpg")||($valid==".png")||($valid==".swf")){ 
if(!copy($_FILES['file']['tmp_name'], $uploaddir.$_FILES['file']['name'])){
	echo 'alert("Не могу закачать файл!");';
}
@unlink($_FILES['file']['tmp_name']);
echo "createhtml();window.close();</script>";
}else {
echo "alert('Это не графический файл!');
 window.location.href('/editor/addimage.php');
 </script>";} 
exit;
}
if(!empty($_POST['imagename'])){
echo "createhtml();window.close();";
}
else{
echo "alert('Вам  необходимо указать файл для закачки.');  
window.location.href('/editor/addimage.php');
</script>";}}?>

function MM_openBrWindow(theURL,winName,features, myWidth, myHeight, isCenter) { //v3.0
  if(window.screen)if(isCenter)if(isCenter=="true"){
    var myLeft = (screen.width-myWidth)/2;
    var myTop = (screen.height-myHeight)/2;
    features+=(features!='')?',':'';
    features+=',left='+myLeft+',top='+myTop;
  }
  window.open(theURL,winName,features+((features!='')?',':'')+'width='+myWidth+',height='+myHeight);
}
function viswin(){
if (document.getElementById("Target").value==""){
document.getElementById("Custom").style.display="block"}else{document.getElementById("Custom").style.display="none"}
}
function openform(){
if(document.getElementById("linkform").style.display=="none"){
document.getElementById("linkform").style.display="block"}else{
document.getElementById("linkform").style.display="none"}
}

function MM_displayStatusMsg(msgStr) { //v1.0
  status=msgStr;
  document.MM_returnValue = true;
}
//-->

</script>
<style type="text/css">
<!--
body {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 12px;
	text-decoration: none;
}
-->
</style>
<style type="text/css">
<!--
a {
	text-decoration: none;
}
-->
</style>
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body onLoad="CheckText()">
     <? el_showalert('warning','Пожалуйста, выберите из списка файл, который Вы хотите вставить на страницу. 
    Если кликнуть по названию файла, справа появится его изображение. Когда выберите, 
    жмите на кнопку &quot;Вставить&quot; и картинка появится на странице.<br>
    Если файл картинки на сервере пока отстуствует, то сначала закачайте 
    его.<br>Для перехода внутрь папок используйте двойной клик.') ?>
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr> 
      <td colspan="2" valign="top">
	  <? define('IMAGE',1);
		include $_SERVER['DOCUMENT_ROOT'].'/editor/upfile.php';
	  ?> 
	  <!--<iframe src="/editor/e_modules/filelist.php" width="700" height="245" frameborder="0" name="flist"></iframe>-->
	  </td>
    </tr>
	<form action="<?=$_SERVER['SCRIPT_NAME']?>" method="post" ENCTYPE="multipart/form-data" name="form1">
    <tr>
      <td colspan="2" valign="top">

  Вставить файл с локального компьютера:  
    <input type="file" size=40 name="file">
    <input name="imagename" type="hidden" id="imagename"></td>
    </tr>
    <tr>
      <td width="8%" valign="top">Выравнивание:<br>
        <select name="alselect" id="alselect" title="Выберите выравнивание картинки">
          <option value="left" selected>Слева</option>
          <option value="right">Справа</option>
          <option value="center">По середине</option>
          <option value="top">Сверху</option>
          <option value="bottom">Снизу</option>
        </select>
        <br>
        <br>      </td>
      <td width="92%" valign="top">&nbsp;&nbsp;Всплывающая подсказка:<br>
        &nbsp;&nbsp;
      <input name="alttext" type="text" id="alttext" size="50">
      <input name="imgpath" type="hidden"></td>
    </tr>
    <tr>
      <td colspan="2" valign="top"><div  class="divide" title="Показать/Скрыть форму" onClick="openform()" style="width:650px"><b><img src="img/up.gif" width="7" height="7">&nbsp;&nbsp;Если картинка должна быть ссылкой, кликните здесь. </b></div>
        <table width=100% bgcolor="#F4F4F4" id="linkform" style="display:none;">
        <tr>
          <td>Тип:</td>
          <td><select name=Protocol>
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
          </select></td>
        </tr>
        <tr>
          <td>URL:</td>
          <td><input type="text" name="Path" size="50">
              <br>
      Если ссылка ведет на внутреннюю страницу, то можно ее просто выбрать из списка ниже.<br>
      <select name="pagesurl" size="1" id="pagesurl" style="width:400px;">
        <option selected value="">Выберите страницу</option>
       <?php
do {  
?> <option value="<?php echo $_SERVER['SERVER_NAME'].$row_pages['path']?>"><?php echo $row_pages['caption']?></option>
        
        <?php
} while ($row_pages = mysql_fetch_assoc($pages));
  $rows = mysql_num_rows($pages);
  if($rows > 0) {
      mysql_data_seek($pages, 0);
	  $row_pages = mysql_fetch_assoc($pages);
  }
?>
      </select>          </td>
        </tr>
        <tr>
          <td valign="top">Открытие:</td>
          <td valign="top"><p>
              <select name=Target id="Target" onChange="viswin()">
                <option value=" target=_self">В этом же окне 
                <option value=" target=_blank">В новом обычном окне 
                <option value="">В специальном окне 
                </select>
              <br>
          </p>
            <div id="Custom" style="display:none; background-color: #FFFFFF; width:650px">Ширина окна:
              <input name="width" type="text" id="width" size="3">
            &nbsp;&nbsp;&nbsp;Высота окна: 
            <input name="height" type="text" id="height" size="3">
            &nbsp;&nbsp;<br>
            Что показать: 
            <input name="bigbutton" type="button" 
style="cursor: hand;"   onClick="MM_openBrWindow('biglink.php','biglink','status=yes,scrollbars=yes,resizable=yes','600','300','true')" value="Выбрать &gt;&gt;" title="Откроется новое окно для выбора источника">
            или вписать здесь: <input name="biglink" type="text" id="biglink">
            </div></td>
        </tr>
      </table></td>
    </tr>
    <tr align="center">
      <td height="40" colspan="2" valign="bottom"><input type="submit" name="Submit" value="Вставить"  style="background-color:#00FFCC;" > 
&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp; &nbsp; 
        <input name="close" type="button" style="background-color:#FF9900 " onClick="javascript:window.close()" value="Закрыть"></td>
    </tr> </form>
  </table>
    

</body>
</html>
<?php
mysql_free_result($pages);
?>
