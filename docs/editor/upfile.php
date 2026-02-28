<?php
$requiredUserLevel = array(1, 2); 
error_reporting(E_ERROR);
if(LINK!=1 && IMAGE!=1){include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php");?>
 
<html>
<head>
<title>Управление файлами</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<link href="style.css" rel="stylesheet" type="text/css"><?php }?>
<style type="text/css">
<!--
.notetable {background-color:#FFFFEC;}
.style1 {color: #FF0000}
.text1 {font-size:12px; color:#000000;}
FIELDSET{padding:10px 10px 10px 10px;}
.line{border-bottom:1px solid #DDDDDD;}
.vline{border-right:1px solid #DDDDDD;border-bottom:1px solid #DDDDDD}
.downbutton{border:2px inset #EEEEEE}
.upbutton{border:2px outset #EEEEEE; filter:progid:DXImageTransform.Microsoft.Gradient(0, startColorStr=#FFFFFF, endColorStr=#BEBFD8); cursor:pointer; height:20px; font-size:11px }
.i_con{width:30px; height:30px; border:1px Solid #D2DDEE;}
-->
</style>
<script type="text/JavaScript">
<!--
function MM_displayStatusMsg(msgStr) { 
  status=msgStr;
  document.MM_returnValue = true;
}

function a(obj){
obj.style.backgroundColor = "";
}

function b(obj){
obj.style.backgroundColor = "#E0EBF5";
}

var codeid;
function MM_goToURL() { 
  var i, args=MM_goToURL.arguments; document.MM_returnValue = false;
  for (i=0; i<(args.length-1); i+=2) eval(args[i]+".location='"+args[i+1]+"'");
}
function hlight(id){
if(codeid!=id){
	document.getElementById(id).style.backgroundColor="#E7E7E7";
	document.getElementById(id).style.color="#000000";
	}
}
function hlightc(id, name){
if(codeid!=id){
	for(i=0; i<document.getElementById("list").getElementsByTagName("tr").length; i++){
		document.getElementById(i).style.backgroundColor="";
		document.getElementById(i).style.color="#000000";
	}
	if(document.getElementById("list").getElementsByTagName("tr").length>0){	
	document.getElementById(id).style.backgroundColor="#436173";
	document.getElementById(id).style.color="#ffffff";
	document.getElementById("del_button").title="Удалить файл \""+name+"\"";
	document.getElementById("rn_button").title="Переименовать файл \""+name+"\"";
	document.getElementById("icut").style.visibility="visible";
	document.getElementById("icut").title="Вырезать файл \""+name+"\"";
	document.getElementById("icopy").style.visibility="visible";
	document.getElementById("icopy").title="Копировать файл \""+name+"\"";
	document.getElementById("ipaste").style.visibility="hidden";
	document.act_form.name.value=name;
	document.act_form.action="#"+id;
	codeid=id;
	}
}else{
	if(document.getElementById("list").getElementsByTagName("tr").length>0){	
	document.getElementById(id).style.backgroundColor="";
	document.getElementById(id).style.color="#000000";
	document.getElementById("icut").style.visibility="hidden";
	document.getElementById("icopy").style.visibility="hidden";
	document.getElementById("ipaste").style.visibility="hidden";
	document.getElementById("del_button").style.visibility="hidden";
	document.getElementById("rn_button").style.visibility="hidden";
	codeid="";
	}
}
	document.getElementById("foldrn").style.visibility="hidden";
	document.getElementById("folddel").style.visibility="hidden";
}

function hlightcf(id, name){
if(codeid!=id){
	for(i=0; i<document.getElementById("list").getElementsByTagName("tr").length; i++){
		document.getElementById(i).style.backgroundColor="";
		document.getElementById(i).style.color="#000000";
	}
	if(document.getElementById("list").getElementsByTagName("tr").length>0){	
	document.getElementById(id).style.backgroundColor="#436173";
	document.getElementById(id).style.color="#ffffff";
	document.getElementById("foldrn").style.visibility="visible";
	document.getElementById("foldrn").title="Переименовать папку \""+name+"\"";
	document.getElementById("folddel").style.visibility="visible";
	document.getElementById("folddel").title="Удалить папку \""+name+"\"";
	document.getElementById("icut").style.visibility="visible";
	document.getElementById("icut").title="Вырезать папку \""+name+"\"";
	document.getElementById("icopy").style.visibility="visible";
	document.getElementById("icopy").title="Копировать папку \""+name+"\"";
	if(document.act_form.pastefile.value!=''){
	document.getElementById("ipaste").style.visibility="visible";}
	document.act_form.name.value=name;
	document.act_form.action="#"+id;
	codeid=id;
	}
}else{
	if(document.getElementById("list").getElementsByTagName("tr").length>0){	
	document.getElementById(id).style.backgroundColor="";
	document.getElementById(id).style.color="#000000";
	document.getElementById("foldrn").style.visibility="hidden";
	document.getElementById("folddel").style.visibility="hidden";
	document.getElementById("icut").style.visibility="hidden";
	document.getElementById("icopy").style.visibility="hidden";
	document.getElementById("ipaste").style.visibility="hidden";
	codeid="";
	}
}
	document.getElementById("del_button").style.visibility="hidden";
	document.getElementById("rn_button").style.visibility="hidden";

}


function uligh(id){
if(codeid!=id){
	document.getElementById(id).style.backgroundColor="";
	document.getElementById(id).style.color="#000000";
	}
}

function check(obj){
var OK=confirm('Вы действительно хотите удалить файл "'+obj+'" ?');
if (OK) {return true} else {return false}
}
function abut(td){
document.getElementById(td).style.border='2px inset #EEEEEE';
}

var acf=document.act_form;

function new_fold(){
if(document.act_form.newname.value=prompt('Введите название будущей папки', '')){
	document.act_form.act.value="new_folder";
	document.act_form.submit();
}
}

function rn_fold(){
var oldname=document.act_form.name.value;
if(document.act_form.newname.value=prompt('Введите новое название папки '+oldname, oldname)){
	document.act_form.act.value="rename_folder";
	document.act_form.submit();
}
}

function del_fold(){
var name=document.act_form.name.value;
var OK=confirm('Вы действительно хотите удалить папку "'+name+'" и все ее содержимое ?');
if (OK) {
	document.act_form.act.value="delete_folder";
	document.act_form.submit();
}
}

function rn_file(){
var oldname=document.act_form.name.value;
if(document.act_form.newname.value=prompt('Введите новое название файла '+oldname, oldname)){
	document.act_form.act.value="rename_file";
	document.act_form.submit();
}
}

function check_name(){
var count=document.getElementById("upfile").value+1;

for(c=0; c<count; c++){
file_name=document.getElementById("file_"+c).value.split('\\');
file_c=file_name.length;
compare=0;
	for(i=0; i<document.getElementById("list").getElementsByTagName("tr").length; i++){
		if(file_name[file_c-1]==document.getElementById("fdname1_"+i).innerText){
			if(confirm('Файл с названием '+file_name[file_c-1]+' уже есть!\nХотите перезаписать его?')){
				compare=0;
			}else{
				compare++;
			}
		}
	}
	if(compare>0){
		return false;
	}else{
		return true;
	}
}
}


pszFont = "Tahoma,8,,BOLD";
//-->
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
         <td><img src="/editor/img/tab_lside_inactive.gif" width="3" height="19"></td><a href="upfile.php?mode=images&pdir=<?=$_SERVER['DOCUMENT_ROOT']?>/images"><td width="100%" align="center" onMouseOver="MM_displayStatusMsg('Перейти к файлам');return document.MM_returnValue" onMouseOut="MM_displayStatusMsg('');return document.MM_returnValue">Изображения</td>
         </a><td><img src="/editor/img/tab_rside_inactive.gif" width="3" height="19"></td>
      </tr>
    </table>
<?php } else{ ?>
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="stab">
      <tr>
         <td><img src="/editor/img/tab_lside_inactive.gif" width="3" height="19"></td><a href="upfile.php?mode=files&pdir=<?=$_SERVER['DOCUMENT_ROOT']?>/files"><td width="100%" align="center" onMouseOver="MM_displayStatusMsg('Перейти к файлам');return document.MM_returnValue" onMouseOut="MM_displayStatusMsg('');return document.MM_returnValue">Файлы</td>
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
	$upArr=explode('/', $_GET['pdir']);
	array_pop($upArr);
	$updir=implode('/', $upArr);

			?>
        <td width="30" align="center"  class="i_con" onMouseOver="b(this)" onMouseOut="a(this)">
			<input type="image" src="img/folderup.gif" width="20" height="25" alt="Переход на верхний уровень" onClick="MM_goToURL('self','?pdir=<?=$updir?>&mode=<?=$mode?>&sorce=<?=$s?>');return document.MM_returnValue">		</td><?	}	($stek!=$mode && $stek!=$mode."/" && $stek!="")?$stek=$mode."/".$stek:$stek=$stek;?>
<script language="javascript">
function movethis(action){
var i_paste=document.getElementById("ipaste");
var actionn;
switch (action){
	case 'cut'  :	document.act_form.pastefile.value='<?=$_SERVER['DOCUMENT_ROOT'].'/'.$stek.'/'?>'+document.act_form.name.value;
					document.act_form.cutcopy.value='cut';
					document.act_form.submit();
					break;
	case 'copy' :	document.act_form.pastefile.value='<?=$_SERVER['DOCUMENT_ROOT'].'/'.$stek.'/'?>'+document.act_form.name.value;
					document.act_form.cutcopy.value='copy';
					document.act_form.submit();
					break;
	case 'paste':	if(document.act_form.cutcopy.value=='cut'){document.act_form.act.value="cut_file"}else{document.act_form.act.value="copy_file"}
					document.act_form.submit();
					break;
}
}

</script>
		
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
<style>
.line{border-bottom:1px solid #DDDDDD;}
.vline{border-right:1px solid #DDDDDD;border-bottom:1px solid #DDDDDD}
.downbutton{border:2px inset #EEEEEE}
.upbutton{border:2px outset #EEEEEE}
</style>
<script language="JavaScript" type="text/JavaScript">

function prewiev(imgsrce, width, height, ftime, type, repeat) {
var f=document.getElementById('prewframe');
switch (type){
case "image":
f.innerHTML='';
w='';
h='';
	if(width>200){
		wk="";
		wk=width/200;
		w=width/wk;
		h=height/wk;
	}else if(repeat!=1){
		w=width;
	}
	if(height>160){
		hk="";
		hk=height/160;
		h=height/hk;
		w=width/hk;
	}else if(repeat!=1){
		h=height;
	}
	if(w>200||h>160){
		prewiev(imgsrce, w, h, ftime, type, repeat=1);
	}
	
f.innerHTML='<img src="<?=$puti?>/'+imgsrce+'" width="'+w+'" height="'+h+'" name="prew" border="0" id="prew" style="cursor:pointer" onClick="openPictureWindow_Fever(\''+type+'\',\'/images/CIMG0070.jpg\',\'300\',\'300\',\'Просмотр\',\'200\',\'100\')" title="Кликните по картинке для полноразмерного просмотра">';
break;
case "file":
f.innerHTML='';
f.innerHTML='<iframe src="/editor/img/spacer.gif" name="prew" frameborder="0" id="prew" width="100%" height="160" style="cursor:pointer"></iframe>';
break;
case "txt":
f.innerHTML='';
f.innerHTML='<iframe src="<?=$puti?>/'+imgsrce+'" name="prew" frameborder="0" id="prew" width="100%" height="160" style="cursor:pointer"></iframe>';
break;

case "swf":
f.innerHTML='';
w='';
h='';
	if(width>200){
		wk="";
		wk=width/200;
		w=width/wk;
		h=height/wk;
	}else if(repeat!=1){
		w=width;
	}
	if(height>160){
		hk="";
		hk=height/160;
		h=height/hk;
		w=width/hk;
	}else if(repeat!=1){
		h=height;
	}
	if(w>200||h>160){
		prewiev(imgsrce, w, h, ftime, type, repeat=1);
	}

f.innerHTML='<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0\" width=\"'+w+'\" height=\"'+h+'\"><param name=movie value="<?=$puti?>/'+imgsrce+'"><param name=quality value=high><embed src="<?=$puti?>/'+imgsrce+'" quality=high pluginspage=\"http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash\" type=\"application/x-shockwave-flash\" width=\"'+w+'\" height=\"'+h+'\"></embed></object>';
break;
}
if(type=='image'||type=='swf'){
	pixel="Размер изображения: "+width+"x"+height+" пикселей<br>";
}else{
	pixel="";
}
document.getElementById('info').innerHTML="<small>Файл: "+imgsrce+"<br>"+pixel+"Время последнего изменения: "+ftime+"</small>";
document.getElementById("sorce").value="<?=$puti?>/"+imgsrce;
document.getElementById("imwidth").value=width;
document.getElementById("imheight").value=height;
document.getElementById("del_button").style.visibility="visible";
document.getElementById("rn_button").style.visibility="visible";
<?php if(LINK==1){?>
document.getElementById("urltext").value="<?=$_SERVER['SERVER_NAME'].$puti?>/"+imgsrce;
<?php }
if(IMAGE==1){ ?>
document.getElementById("imagename").value="<?=$_SERVER['SERVER_NAME'].$puti?>/"+imgsrce;
<?php }?>
}

function openPictureWindow_Fever(imageType,imageName,imageWidth,imageHeight,alt,posLeft,posTop) {  
var imageName=document.getElementById("sorce").value;
var imageWidth=document.getElementById("imwidth").value;
var imageHeight=document.getElementById("imheight").value;
if(imageWidth==''){imageWidth=600}
if(imageHeight==''){imageHeight=500}
	newWindow = window.open("","newWindow","width="+imageWidth+",height="+imageHeight+",scrollbars=no,left="+posLeft+",top="+posTop);
	newWindow.document.open();
switch (imageType){
	case "swf":
	newWindow.document.write('<html><title>'+imageName+'</title><body bgcolor="#FFFFFF" style="margin: 0px 0px 0px 0px">'); 
	newWindow.document.write('<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0\" width=\"'+imageWidth+'\" height=\"'+imageHeight+'\">');
	newWindow.document.write('<param name=movie value="'+imageName+'"><param name=quality value=high>');
	newWindow.document.write('<embed src="'+imageName+'" quality=high pluginspage=\"http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash\" type=\"application/x-shockwave-flash\" width=\"'+imageWidth+'\" height=\"'+imageHeight+'\">');
	newWindow.document.write('</embed></object>');	
	break;
	
	case "image":
	newWindow.document.write('<html><title>'+imageName+'</title><body bgcolor="#FFFFFF" style="margin: 0px 0px 0px 0px;padding: 0px 0px 0px 0px;">'); 
	newWindow.document.write('<img src="'+imageName+'"  alt="Кликните по картинке, чтобы закрыть ее" style="cursor:pointer" onClick="self.close()">'); 
	break;
	
	case "file":
	case "txt" :
	newWindow.document.write('<html><body onload="window.close()"></body></html>');
	newWindow.location.href=imageName;
	break;
}
	newWindow.document.write('</body></html>');
	newWindow.document.close();
	newWindow.focus();
}
<?php if(IMAGE!=1){ ?>
var c=0; 
function addline(){
	c++; 
	s=document.getElementById('table').innerHTML;
	s=s.replace(/[\r\n]/g,'');
	re=/(.*)(<tr id=.*>)(<\/table>)/gi; 
	s1=s.replace(re,'$2');
	s2=s1.replace(/_\d+/gi,'_'+c); 
	s2=s2.replace(/(rmline\()(\d+\))/gi,'$1'+c+')');
	s=s.replace(re,'$1$2'+s2+'$3');
	document.getElementById('table').innerHTML=s;
	document.getElementById('upfile').value=c;
	return false; 
}

function rmline(q){
                 if (c==0) return false; else c--;
	s=document.getElementById('table').innerHTML;
	s=s.replace(/[\r\n]/g,'');
	re=new RegExp('<tr id="?newline"? nomer="?_'+q+'.*?<\\/tr>','gi');
	s=s.replace(re,'');
	document.getElementById('table').innerHTML=s;
	document.getElementById('upfile').value=c;
	return false;
}
<?php }?>
</script>
 <?
//Функция удаления папок и их содержимого
 function delDir($dirName) {
   if(empty($dirName)) {
       return;
   }
   if(file_exists($dirName)) {
       $dir = dir($dirName);
       while($file = $dir->read()) {
           if($file != '.' && $file != '..') {
               if(is_dir($dirName.'/'.$file)) {
                   delDir($dirName.'/'.$file);
               } else {
                   @unlink($dirName.'/'.$file) or die('<script language=javascript>alert("Файл '.$dirName.'/'.$file.' не удалось удалить!")</script>');
               }
           }
       }
       @rmdir($dirName.'/'.$file) or die('Папку '.$dirName.'/'.$file.' не удалось удалить!');
   } else {
       echo '<script language=javascript>alert("Папка "<b>'.$dirName.'</b>" не существует.")</script>';
   }
}

//Функция копирования папок и их содержимого
 function copyDir($dirSorce, $dirTarget, $mode) {
 global $newname_folder;
   if(empty($dirSorce)) {
       return;
   }
   if(!mkdir($dirTarget, 0777)){
   		echo "<script>alert('Не удается скопировать папку \"".$newname_folder."\".\\nВозможно, в пункте назначения уже есть папка с таким названием.')</script>";
	}
   if(file_exists($dirSorce)) {
       $dir = dir($dirSorce);
       while($file = $dir->read()) {
           if($file != '.' && $file != '..') {
               if(is_dir($dirSorce.'/'.$file)) {
                   copyDir($dirSorce.'/'.$file, $dirTarget.'/'.$file, $mode);
               } else {
                   copy($dirSorce.'/'.$file, $dirTarget.'/'.$file); 
               }
           }
       }
       if($mode=='cut'){delDir($dirSorce.'/'.$file);}
   } else {
       echo '<script language=javascript>alert("Папка "<b>'.$dirSorce.'</b>" не существует.")</script>';
   }
}


//Действия с файлами и папками по клику на иконках 
 if(isset($_POST['act'])){
 (isset($_GET['pdir']))?$curr_dir=$_GET['pdir']:$curr_dir=$_SERVER['DOCUMENT_ROOT']."/".$mode;
 $newname_folder=str_replace("/","",strrchr($_POST['pastefile'],"/"));
 if(is_dir($_POST['pastefile'])){
	$newpath=$curr_dir."/".$_POST['name']."/".$newname_folder;
}else{
	$newpath=$curr_dir."/".$_POST['name'];
}

 	switch($_POST['act']){
		case "new_folder"   : 
			if(file_exists($curr_dir."/".$_POST['newname'])){
				echo "<script language=javascript>alert('Папка ".$_POST['newname']." уже существует. Подберите другое название.')</script>";
				}else{
					if(!mkdir($curr_dir."/".$_POST['newname'], 0777)){
						echo "<script language=javascript>alert('Папку ".$_POST['newname']." не удалось создать.')</script>";
					}
				}
		 break;
		case "rename_folder": 
			if(file_exists($curr_dir."/".$_POST['newname'])){
				echo "<script language=javascript>alert('Папка ".$_POST['newname']." уже существует. Подберите другое название.')</script>";
			}else{
				if(!rename($curr_dir."/".$_POST['name'],$curr_dir."/".$_POST['newname'])){
					echo "<script language=javascript>alert('Папку ".$_POST['name']." не удалось переименовать.')</script>";
				}
			}
		break;
		case "delete_folder": 
			delDir($curr_dir."/".$_POST['name']);
		break;
		case "rename_file": 
			if(file_exists($curr_dir."/".$_POST['newname'])){
				echo "<script language=javascript>alert('Файл ".$_POST['newname']." уже существует. Подберите другое название.')</script>";
			}else{
				if(!rename($curr_dir."/".$_POST['name'],$curr_dir."/".$_POST['newname'])){
					echo "<script language=javascript>alert('Файл ".$_POST['name']." не удалось переименовать.')</script>";
				}
			}
		break;
		case "copy_file":
			if(is_dir($_POST['pastefile'])){
				copyDir($_POST['pastefile'],$newpath, "copy");
			}else{
				if(!copy($_POST['pastefile'], $newpath.strrchr($_POST['pastefile'],"/"))){
					echo "<script language=javascript>alert('Не удается скопировать файл.')</script>";
				}
			}
		break;
		case "cut_file":
			if(is_dir($_POST['pastefile'])){
				copyDir($_POST['pastefile'],$newpath, "cut");
			}else{
				if(!copy($_POST['pastefile'], $newpath.strrchr($_POST['pastefile'],"/"))){
					echo "<script language=javascript>alert('Не удается переместить файл.')</script>";
				}else{
					unlink($_POST['pastefile']);
				}
			}
		break;

	}
 }
 
 
 if(isset($_POST['delete'])){
 	if(file_exists($_SERVER['DOCUMENT_ROOT'].$_POST['sorce']) && !is_dir($_SERVER['DOCUMENT_ROOT'].$_POST['sorce'])){
		if(unlink($_SERVER['DOCUMENT_ROOT'].$_POST['sorce'])){
			echo "<script language=javascript>alert('Файл ".str_replace("/","",strrchr($_POST['sorce'],"/"))." удален!')</script>";
		}else{
			echo "<script language=javascript>alert('Файл ".str_replace("/","",strrchr($_POST['sorce'],"/"))." не удалось удалить.')</script>";
		}
	}else{
		echo "<script language=javascript>alert('Файл ".str_replace("/","",strrchr($_POST['sorce'],"/"))." уже удален или является директорией!')</script>";
	}
 }
function upimage($num, $key=0){ 
global $_POST, $stek, $mode;
$mess="";
$file=$_FILES['file_'.$num]['tmp_name'];
$file_name=$_FILES['file_'.$num]['name'];
$uploaddir=(isset($_GET['pdir']))?$_GET['pdir']."/":$_SERVER['DOCUMENT_ROOT'].'/files/';
if(!file_exists($uploaddir)){
	mkdir($uploaddir, 0777);
}

if ($file == "") {
	$mess.="\\nВам  необходимо указать файл для закачки.";
} else { 
	if($mode=='images'){
		if(getimagesize($file)){
			if($_ENV['upload_max_filesize']<$_FILES['file_'.$num]['size']){
				if(copy($file, $uploaddir.$file_name)){
					@unlink($file);
					$mess.="\\nФайл \"".$file_name."\" успешно закачан в папку \"".$_GET['pdir']."\" !";
				}else{
					$mess.="\\nНе удалось закачать файл $uploaddir.$file_name!";
				}
	  		}else{
	    		$mess.="\\nРазмер файла \"".$_FILES['file']['name']."\" превышает максимально разрешенный сервером!";
	  		}	
		}else{
	    	$mess.="\\nЭтот файл не является изображением!\\nВ папку \"images\" и все внутренние папки можно закачивать только изображения.";
	 	}
	}elseif($mode=='files'){
	 	if($_ENV['upload_max_filesize']<$_FILES['file_'.$num]['size']){
			if(copy($file, $uploaddir.$file_name)){
				@unlink($file);
				$mess.="\\nФайл \"".$file_name."\" успешно закачан в папку \"".$uploaddir."\" !";
			}else{
				$mess.="\\nНе удалось закачать файл ".$uploaddir.$file_name."!";
			}
		}else{
	    	$mess.="\\nРазмер файла \"".$_FILES['file']['name']."\" превышает максимально разрешенный сервером!";
		}	

	}
}
if($mess!=""){echo "<script>alert('$mess')</script>";}
}

if(isset($_POST['upfile'])){
	for($num=0; $num<$_POST['upfile']+1; $num++){
		upimage($num); 
	}
}
 
if(!empty($_GET['pdir'])){$filepath=$_GET['pdir']."/";}else{$filepath=$_SERVER['DOCUMENT_ROOT']."/".$mode."/";}
  $d=dir($filepath);
  $co=0;
  while($entry=$d->read()) {
  	if ($entry!="." && $entry!=".htpasswds" && $entry!=".." && $entry!="") {
switch (filetype($filepath.$entry)){
	case 'dir': $icon='/editor/img/folder.gif'; 
	   			$type='dir';
				break;
  	case 'file': 
	
		preg_match("'^(.*)\.(.*)$'i", $entry, $ext);
   switch (strtolower($ext[2])) {
       case 'jpg' : 
	   case 'gif' :
	   case 'png' :
	   case 'jpeg':$icon='/editor/img/f_image.gif';
	   			   $type='image';
                     break;
	   case 'doc' :
	   case 'rtf' :
	   case 'wri' :$icon='/editor/img/icon_word.gif';
	   			   $type='file';
                     break;
	   case 'log' :
	   case 'txt' :$icon='/editor/img/icon_txt.gif';
	   			   $type='txt';
                     break;
	   case 'psd' :$icon='/editor/img/icon_psd.gif';
	   			   $type='file';
                     break;
	   case 'pdf' :$icon='/editor/img/icon_pdf.gif';
	   			   $type='file';
                     break;
	   case 'zip' :$icon='/editor/img/icon_zip.gif';
	   			   $type='file';
                     break;
	   case 'rar' :$icon='/editor/img/icon_rar.gif';
	   			   $type='file';
                     break;
	   case 'xls' :
	   case 'csv' :$icon='/editor/img/icon_excel.gif';
	   			   $type='file';
                     break;
	   case 'html':
	   case 'htm' :
	   case 'shtml':
	   case 'shtm':$icon='/editor/img/icon_html.gif';
	   			   $type='txt';
                     break;
	   case 'wav' :
	   case 'wmv' :
	   case 'mpg' :
	   case 'mpeg':
	   case 'mp3' :
	   case 'avi' :$icon='/editor/img/icon_media.gif';
	   			   $type='file';
                     break;
	   case 'swf' :$icon='/editor/img/image.gif';
	   			   $type='swf';
                     break;
	   case 'com' :
	   case 'bat' :
	   case 'exe' :$icon='/editor/img/icon_exe.gif';
	   			   $type='file';
                     break;
       default    :$icon='/editor/img/icon_app.gif';
	   			   $type='file';
                     break;
   }
	break;
	}	
		$fsize=filesize($filepath.$entry);
		$ftime=el_date1(date("Y-m-d",filectime($filepath.$entry)));
		$filelist[$co]['icon']=$icon;
		$filelist[$co]['name']=$entry;
		$filelist[$co]['size']=$fsize;
		$filelist[$co]['time']=$ftime;
		$filelist[$co]['type']=$type;
		clearstatcache();
		$co++;
   } 
 }
  $d->close();
  ?>
 <table width="100%" height="100%" border="0" cellpadding="5" cellspacing="0" style="height:100%">
  <tr><td height="100%" valign="top">
   <table width="100%" height="20"  border="0" cellpadding="0" cellspacing="0">
   <tr>
    <a href="?mode=<?=$mode?>&sort=type<?=(isset($_GET['pdir']))?"&pdir=".$_GET['pdir']:""?>&sorce=<?=$s?>"><td width="25" bgcolor="#CCCCCC" class="upbutton" id="fdtype" title="Сортировать по типу" onMouseOver="MM_displayStatusMsg('Сортировать по типу');return document.MM_returnValue" onMouseOut="MM_displayStatusMsg('');return document.MM_returnValue" onMouseDown="abut('fdtype')"> Тип&nbsp;</td></a>
    <a href="?mode=<?=$mode?>&sort=name<?=(isset($_GET['pdir']))?"&pdir=".$_GET['pdir']:""?>&sorce=<?=$s?>"><td width="47%" bgcolor="#CCCCCC" class="upbutton" id="fdname" title="Сортировать по названию" onMouseOver="MM_displayStatusMsg('Сортировать по названию');return document.MM_returnValue" onMouseOut="MM_displayStatusMsg('');return document.MM_returnValue" onMouseDown="abut('fdname')">&nbsp;Название</td>
    </a>
	
    <a href="?mode=<?=$mode?>&sort=size<?=(isset($_GET['pdir']))?"&pdir=".$_GET['pdir']:""?>&sorce=<?=$s?>"><td width="53%" bgcolor="#CCCCCC" id="fdsize" class="upbutton" title="Сортировать по размеру файла" onMouseOver="MM_displayStatusMsg('Сортировать по размеру файла');return document.MM_returnValue" onMouseOut="MM_displayStatusMsg('');return document.MM_returnValue" onMouseDown="abut('fdsize')">&nbsp;Размер файла </td>
    </a>   </tr></table>
   <div style="overflow:auto; width:420px; height:95%; border:2px inset; cursor:default">
   <table width="100%"  border="0" cellpadding="2" cellspacing="0" id="list">
     
<?
function cmp($a, $b){
	if($a['size']==$b['size']) return 0;
	return ($a['size']<$b['size'])?-1:1;
}
function acmp($c, $d){
return strnatcasecmp($c['name'],$d['name']);
}
function bcmp($c, $d){
return strnatcasecmp($c['icon'],$d['icon']);
}

if(!isset($_GET['sort'])||$_GET['sort']=="name"){
	@usort($filelist, "acmp");
}else{
	@usort($filelist, "cmp");
}
if(!isset($_GET['sort'])||$_GET['sort']=="type"){
	@usort($filelist, "bcmp");
	//@usort($filelist, "cmp");
}
//reset($filelist);
for($key=0; $key<count($filelist); $key++){
	if(strlen($filelist[$key]['name'])>0){
		unset($fsize);
		$fsizeim=@getimagesize($_SERVER['DOCUMENT_ROOT'].$puti."/".$filelist[$key]['name']);
?>
  <tr <?php if($filelist[$key]['icon']=="/editor/img/folder.gif")
  {  	
	echo "onClick=\" hlightcf('".$key."', '".$filelist[$key]['name']."')\" onDblClick=\"MM_goToURL('self','?pdir=".$filepath.$filelist[$key]['name']."&mode=$mode&sorce=$s');return document.MM_returnValue\"";
  }else{
  	echo "onClick=\"prewiev('".$filelist[$key]['name']."', '".$fsizeim[0]."', '".$fsizeim[1]."', '".$filelist[$key]['time']."', '".$filelist[$key]['type']."', ''); hlightc('".$key."', '".$filelist[$key]['name']."')\" onDblClick=\"openPictureWindow_Fever('".$filelist[$key]['type']."','/files/CIMG0070.jpg','10','10','Просмотр','200','100');prewiev('".$filelist[$key]['name']."', '".$fsizeim[0]."', '".$fsizeim[1]."', '".$filelist[$key]['time']."', '".$filelist[$key]['type']."', ''); hlightc('".$key."', '".$filelist[$key]['name']."')\"";
  }?> onMouseOver="hlight(<?=$key?>)" onMouseOut="uligh(<?=$key?>)" id="<?=$key?>"  >
    <td width="25" id="fdtype1"><img src="<?=$filelist[$key]['icon']?>" <?=($filelist[$key]['name']==$_POST['name'])?"style='filter: Alpha(Opacity=50)'":""?>></td>
    <td width="50%" class="vline"><a name=<?=$key?>></a><div id="fdname1_<?=$key?>"><?=$filelist[$key]['name'] ?></div></td>
    <td width="50%" class="vline" id="fdsize1"><?=($filelist[$key]['size']<1024)?$filelist[$key]['size']." bite":round($filelist[$key]['size']/1024,2)." kb" ?></td>
  </tr>
<?php } } ?>
</table></div>
<script>
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
<table border=0 cellspacing=0 cellpadding=3>
  <tr id="newline" nomer="_0">
  <td>
    <input name="file_0" type="file" id="file_0" size=20></td><td valign="top"><input type="button" name="del" value="x" onClick="return rmline(0);" class="but" style="font-size:10px; height:16px" title="Удалить">
      </td></tr>
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