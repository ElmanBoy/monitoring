<?PHP $requiredUserLevel = array(1, 2); 
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php"); ?>
<html>
<head>
<title>Список файлов</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<link href="/editor/style.css" rel="stylesheet" type="text/css">
<style>
TD{border-bottom:1px solid #DDDDDD;}
.vline{border-right:1px solid #DDDDDD;border-bottom:1px solid #DDDDDD}
body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
}
</style>
<script language="JavaScript" type="text/JavaScript">
<!--
function MM_goToURL() { //v3.0
  var i, args=MM_goToURL.arguments; document.MM_returnValue = false;
  for (i=0; i<(args.length-1); i+=2) eval(args[i]+".location='"+args[i+1]+"'");
}
function hlight(id){
document.getElementById(id).style.backgroundColor="#E7E7E7";
}
function uligh(id){
document.getElementById(id).style.backgroundColor="transparent";
}
function prewiev(imgsrce, width, height) {
itemsrc=imgsrce;
var w=document.getElementById('prew');
w.src="";
w.src="../../images/"+itemsrc;
w.width="";
w.height="";
	if(width>200){
		wk="";
		wk=width/200;
		w.width=200;
		w.height=height/wk;
	}else{
		w.width=width;
	}
	if(height>160){
		hk="";
		hk=height/160;
		w.height=160;
		w.width=width/hk;
	}else{
		w.height=height;
	}

document.getElementById('info').innerHTML="<small>Размер рисунка: "+width+"x"+height+" px</small>";
document.getElementById('prew').style.visibility="visible";
imagePath=itemsrc;
document.getElementById("sorce").value=itemsrc;
document.getElementById("Submit").style.visibility="visible";
}
function check(obj){
var OK=confirm('Вы действительно хотите удалить файл "'+obj+'" ?');
if (OK) {return true} else {return false}
}
//-->
</script>
</head>

<body>
 <?
 if(isset($_POST['delete'])){
 	if(file_exists($_SERVER['DOCUMENT_ROOT']."/images/".$_POST['sorce']) && !is_dir($_SERVER['DOCUMENT_ROOT']."/images/".$_POST['sorce'])){
		if(unlink($_SERVER['DOCUMENT_ROOT']."/images/".$_POST['sorce'])){
			echo "<script language=javascript>alert('Файл ".$_POST['sorce']." удален!')</script>";
		}else{
			echo "<script language=javascript>alert('Файл ".$_SERVER['DOCUMENT_ROOT'].$_POST['sorce']." не удалось удалить.')</script>";
		}
	}else{
		echo "<script language=javascript>alert('Файл ".$_SERVER['DOCUMENT_ROOT'].$_POST['sorce']." уже удален или является директорией!')</script>";
	}
 }
 
 
if(!empty($_GET['pdir'])){$filepath=$_GET['pdir'];}else{$filepath="../../images/";}
$arricons=array();
$filelist=array();
$arrsizes=array();
  $d=dir($filepath);
  while($entry=$d->read()) {
  if ($entry!="." && $entry!=".htpasswds" && $entry!=".." && $entry!="") {
  switch (filetype($filepath.$entry)){
	case 'dir': $icon='/editor/img/folder.gif'; break;
  	case 'file': $icon='/editor/img/f_image.gif';break;
	default: $icon="/editor/img/image.gif";break;}
	$fsize=filesize($filepath.$entry);
	array_push($arricons, $icon);
	array_push($filelist, $entry);
	array_push($arrsizes, $fsize);
	clearstatcache();
   } 
}
  $d->close();
  ?>
<table width="100%" border="0" cellpadding="5" cellspacing="0">
  <tr><td>
<div style="overflow:auto; width:400px; height:230px; border:2px inset">
<table width="100%"  border="0" cellpadding="2" cellspacing="0">
   <tr> <td bgcolor="#CCCCCC" style="border:2px outset #EEEEEE">&nbsp;</td>
    <td bgcolor="#CCCCCC" style="border:2px outset #EEEEEE">Название</td>
    <td bgcolor="#CCCCCC" style="border:2px outset #EEEEEE">Размер файла </td>
   </tr>
<?
natcasesort($filelist);
reset($filelist);
do{ 
$key=0;
$key=key($filelist);
if(strlen($filelist[$key])>0){
unset($fsize);
$fsize=getimagesize($_SERVER['DOCUMENT_ROOT']."/images/".$filelist[$key]);
?>
  <tr <? if($arricons[$key]=="/editor/img/folder.gif"){echo "onClick=\"MM_goToURL('self','/editor/e_modules/filelist.php?pdir=".$filepath.$filelist[$key]."');return document.MM_returnValue\"";} ?> onMouseOver="hlight(<?=$key?>)" onMouseOut="uligh(<?=$key?>)" id="<?=$key?>"  onClick='prewiev("<?=$filelist[$key] ?>", "<?=$fsize[0]?>", "<?=$fsize[1]?>")'>
    <td width="25"><img src="<?=$arricons[$key]?>" align=left ></td>
    <td width="20%" class="vline"><?=$filelist[$key] ?></td>
    <td><?=($arrsizes[$key]<1024)?$arrsizes[$key]." bite":round($arrsizes[$key]/1024,2)." kb" ?></td>
  </tr>
<? }
}while(each($filelist)) ?>
</table>
</div>
</td><td width="100%" valign="top"><!--<iframe src="/editor/img/spacer.gif" name="prew" id="prew" width="200" height="175" frameborder="0" scrolling="auto" style="visibility:hidden"></iframe>-->
<img src="/editor/img/spacer.gif" name="prew" id="prew" border="0">
 <br>
<div id="info"></div>
 <form method="post" name="del_image" id="del_image" onSubmit="return check(document.getElementById('sorce').value)"> 
  <input name="sorce" type="hidden" id="sorce">
    <input type="submit" name="Submit" value="Удалить картинку" class="but" style="visibility:hidden">
    <input name="delete" type="hidden" id="delete">
 </form>
  </td>
  </tr></table>

</body>
</html>
