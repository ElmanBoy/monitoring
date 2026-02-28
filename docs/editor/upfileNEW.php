<?php
$requiredUserLevel = array(1, 2); 
error_reporting(E_ERROR);
if(LINK!=1 && IMAGE!=1){include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php");?>
 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
<head>
<title>Управление файлами</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<link href="style.css" rel="stylesheet" type="text/css"><?php }?>
<link href="upfile.css" rel="stylesheet" type="text/css">
<script type="text/JavaScript">
pszFont = "Tahoma,8,,BOLD";
</script>
<?php if(LINK!=1 && IMAGE!=1) {?></head><body><?php }?>
<?php 
if(!isset($_GET['mode'])){
	$mode="files";
}else{
	($_GET['mode']=="files")?$mode='files':$mode='images';
}
if(IMAGE==1){$mode='images';}
?>
<table width="100%" height="100%" border="0" cellpadding="5" cellspacing="0">
  <?php if(LINK!=1 && IMAGE!=1) {?><tr><td width="33%" align="right" valign="bottom">
  <?php if($mode=='files'){ ?>
  	  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="ftab">
      <tr><td><img src="/editor/img/tab_lside_active.gif" width="3" height="21"></td>
      <td width="100%" align="center">
		Файлы</td>
      <td><img src="/editor/img/tab_rside_active.gif" width="3" height="21"></td></tr></table>

      </td>
    <td width="15%" height="21" valign="bottom">   <table width="100%" border="0" cellspacing="0" cellpadding="0" class="stab">
      <tr>
         <td><img src="/editor/img/tab_lside_inactive.gif" width="3" height="19"></td><a href="upfileNEW.php?mode=images"><td width="100%" align="center" onMouseOver="MM_displayStatusMsg('Перейти к файлам');return document.MM_returnValue" onMouseOut="MM_displayStatusMsg('');return document.MM_returnValue">Изображения</td>
         </a><td><img src="/editor/img/tab_rside_inactive.gif" width="3" height="19"></td>
      </tr>
    </table>
<?php } else{ ?>
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="stab">
      <tr>
         <td><img src="/editor/img/tab_lside_inactive.gif" width="3" height="19"></td><a href="upfileNEW.php?mode=files"><td width="100%" align="center" onMouseOver="MM_displayStatusMsg('Перейти к файлам');return document.MM_returnValue" onMouseOut="MM_displayStatusMsg('');return document.MM_returnValue">Файлы</td>
         </a><td><img src="/editor/img/tab_rside_inactive.gif" width="3" height="19"></td>
      </tr>
    </table>
      </td>
    <td width="15%" height="21" valign="bottom">
	  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="ftab">
      <tr><td><img src="/editor/img/tab_lside_active.gif" width="3" height="21"></td>
      <td width="100%" align="center">
	Изображения	</td>
      <td><img src="/editor/img/tab_rside_active.gif" width="3" height="21"></td></tr></table>
	  <?php }?>
	  	</td>
    <td width="52%" colspan="2"></td>
  </tr><?php }?>
  <tr>
    <td align="center" class="leftline1"><table border="0" cellspacing="2" cellpadding="2">
      <tr><?php 
if(isset($_POST['pastefile'])){
  	$s=$_POST['pastefile'];
}else{
	$s=$_GET['sorce'];
}
if(isset($_GET['pdir'])){
	$stek=str_replace("/","",strrchr($_GET['pdir'],"/"));
}else{
	$stek=$mode;
}

if(isset($_GET['pdir'])){
	$puti=str_replace($_SERVER['DOCUMENT_ROOT'], "", $_GET['pdir']); 
}else{
	$puti='/'.$mode;
}
		
if($stek!=$mode && $stek!=$mode."/" && $stek!=""){
	$updir=str_replace("/".$stek, "", $_GET['pdir']); 
			?>
        <td width="30" align="center"  class="i_con" onMouseOver="b(this)" onMouseOut="a(this)">
			<input type="image" src="img/folderup.gif" width="20" height="25" alt="Переход на верхний уровень" onClick="MM_goToURL('self','?pdir=<?=$updir?>&mode=<?=$mode?>&sorce=<?=$s?>');return document.MM_returnValue">		</td><?	}	($stek!=$mode && $stek!=$mode."/" && $stek!="")?$stek=$mode."/".$stek:$stek=$stek;?>

		
        <td width="30" align="center" valign="middle" class="i_con" onMouseOver="b(this)" onMouseOut="a(this)" id="icut" style="visibility:hidden"><input type="image" src="img/cut.gif" onClick="movethis('cut')"></td>
        <td width="30" align="center" valign="middle" class="i_con" onMouseOver="b(this)" onMouseOut="a(this)" id="icopy" style="visibility:hidden"><input type="image" src="img/copy.gif" onClick="movethis('copy')"></td>
        <td width="14" align="center" valign="middle" class="i_con" onMouseOver="b(this)" onMouseOut="a(this)" id="ipaste" style="visibility:hidden"><input type="image" src="img/paste.gif" onClick="movethis('paste')"></td>
        <td width="14" align="center" class="i_con" onMouseOver="b(this)" onMouseOut="a(this)"><input name="refresh" type="image" id="refresh" onClick="document.location.href('<?=$_SERVER['REQUEST_URI']?>')" src="img/icon_refresh.gif" alt="Обновить" align="middle"></td>
        <td width="30" align="center" class="i_con" onMouseOver="b(this)" onMouseOut="a(this)"><input name="image" type="image" onClick="new_fold()" src="img/foldernew.gif" alt="Создать новую папку" width="20" height="25" ></td>
        <td width="30" align="center" class="i_con" onMouseOver="b(this)" onMouseOut="a(this)" id="foldrn" style="visibility:hidden"><input type="image"  src="img/folderrename.gif" width="20" height="25" onClick="rn_fold()" ></td>
        <td width="30" align="center" class="i_con" onMouseOver="b(this)" onMouseOut="a(this)" id="folddel" style="visibility:hidden"><input type="image"  src="img/folderdel.gif" width="20" height="25" align="left" onClick="del_fold()" ></td>
        <td width="30" align="center" class="i_con" onMouseOver="b(this)" onMouseOut="a(this)" id="rn_button" style="visibility:hidden"><input type="image"  src="img/filerename.gif" width="20" height="25" onClick="rn_file()" ></td>
        <td width="30" align="center" class="i_con" onMouseOver="b(this)" onMouseOut="a(this)" style="visibility:hidden" id="del_button"><input type="image" onClick="if(check(document.getElementById('sorce').value)){document.del_image.submit()}" src="img/filedel.gif" width="20" height="25"></td>
      </tr>
    </table></td>
    <td align="center"><form name="act_form" id="act_form" method="post">
		<input type="hidden" id="act" name="act">
		<input type="hidden" id="cutcopy" name="cutcopy" value="<?=$_POST['cutcopy']?>">
        <input type="hidden" id="name" name="name">
        <input type="hidden" id="newname" name="newname">
		<input type="hidden" id="anchorl" name="anchorl">
		<input type="hidden" id="pastefile" name="pastefile" value="<?=$s?>">
    </form></td>
    <td align="center" class="rightline">Путь к текущей папке  &quot;<b><?=$_SERVER['SERVER_NAME'].$puti?></b>&quot;.</td>
  </tr>
  <tr>
    <td height="100%" colspan="4" align="center" valign="top" class="bodyline">

<script language="javascript1.2" src="upfile.js.php?puti=<?=$puti?>&stek=<?=$stek?>&image=<?=IMAGE?>&link=<?=LINK?>"></script>
  
  
 <table  height="100%" border="0" cellpadding="5" cellspacing="0" style="height:100%">
  <tr><td height="100%" valign="top">
   
<div class="scroll-table" id="tblFileList" style="overflow:auto; width:420px; height:95%; border:2px solid #CCDCE6; cursor:default; position: relative;"></div>

<script>
if(document.getElementById('tblFileList').innerHTML==''){
	getFileList('files', 'type', '<?=$_SERVER['DOCUMENT_ROOT']?>/files/', '<?=$s?>');
}

var anc=location.href.split("#");
if(!isNaN(anc[1])){
	hlightc(anc[1], '');
}
</script>
</td><td width="100%" align="center" valign="top">
<fieldset>
<legend>Сведения о файле </legend>
<div id="prewframe" style="width:200px; height:200px"></div>
 <br>
<div id="info"></div>

 <form method="post" name="del_image" id="del_image" onSubmit="return check(document.getElementById('sorce').value)">
   <input name="imwidth" type="hidden" id="imwidth">
   <input name="imheight" type="hidden" id="imheight"> 
  <input name="sorce" type="hidden" id="sorce">
  <input name="delete" type="hidden" id="delete">
 </form>

</fieldset> 
<?php if(IMAGE!=1){ ?>
 <fieldset>
 <legend>Закачка новых файлов <img src="/editor/img/help_button.gif" alt="Кликните для получения справки" width="12" height="12" style="cursor:pointer" onClick="test.TextPopup ('Файл будет помещен в директорию <?=$mode?>.\n Внимание! Название файла должно состоять только\nиз английских букв и символов!\n Выберите файл , нажав на кнопку <<Обзор>>,\nа затем нажмите кнопку <<Закачать>>',pszFont,10,10,-1,-1)"></legend>

 <input type="button" name="Submit2" value="Добавить поле" class="but" onClick="return addline();"> 

      <form method="post" ENCTYPE="multipart/form-data" onSubmit="return check_name()">
<span id="table">
<table border=0 cellspacing=0 cellpadding=3 id="upFileTbl">
  <tr id="newline" nomer="_0">
  <td>
    <input name="file[]" type="file" id="file_0" size=20></td>
    </tr>
</table>
</span>
<input name="submit" type="submit" class="but" value="Закачать">
      <input name="upfile" type="hidden" id="upfile" value="0">
      </form>
<OBJECT height="1" width="1" id=test type="application/x-oleobject"
  classid="clsid:adb880a6-d8ff-11cf-9377-00aa003b7a11"> 
</OBJECT>
  </fieldset>
  <?php }?>
 </td>
  </tr></table></td>
  </tr>
</table>
<?php if(LINK!=1 && IMAGE!=1) {?></body></html><?php }?>