<?php require_once($_SERVER['DOCUMENT_ROOT'].'/Connections/dbconn.php'); ?>
<?PHP $requiredUserLevel = array(1); 
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php"); 
switch ($_GET['mode']){
	case 'new'   :$mas='WHERE `master`<>1'; $title='Открытие готового шаблона'; $listTitle='Список существующих шаблонов'; break;
	case 'open'  :$mas='WHERE `master`=1'; $title='Создание готового шаблона'; $listTitle='Выберите HTML-страницу, как основу будущего шаблона'; break;
	default      :$mas='WHERE `master`<>1'; $title='Сохранение готового шаблона'; $listTitle='Список существующих шаблонов'; break;
}
$t=el_dbselect("SELECT * FROM template $mas", $t, 0);
$tr=mysql_fetch_assoc($t);
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<title><?=$title?></title>
<link href="/editor/style.css" rel="stylesheet" type="text/css">
<style>
body{margin:10}
.opt{ background-image:url(../img/page_green.gif); background-repeat:no-repeat; padding-left:25px}
</style>
<script language="javascript">
function setName(){
	tList.tName.value=tList.temp_list.options[tList.temp_list.selectedIndex].text;
	tList.tFile.value=tList.temp_list.options[tList.temp_list.selectedIndex].value;
}

function setTemp(){
	var flag=0;
	var file= new Array();
	<? if(!isset($_GET['mode'])){ ?>
	for(i=0; i<tList.temp_list.options.length; i++){
		if(tList.tFile.value==tList.temp_list.options[i].value && tList.tName.value==tList.temp_list.options[i].text){
			var OK=confirm("Вы уверены, что хотите перезаписать шаблон \""+tList.temp_list.options[i].text+"\"?");
			if(OK){flag=1}else{ return false;}
		}else if(tList.tFile.value==tList.temp_list.options[i].value){
			var OK=confirm("Файл с таким именем уже существует.\nЭто шаблон \""+tList.temp_list.options[i].text+"\"\nВы уверены, что хотите перезаписать его?");
			if(OK){flag=1}else{ return false;}
		}else if(tList.tName.value==tList.temp_list.options[i].text && tList.tFile.value!=tList.temp_list.options[i].value){
			alert("Дайте уникальное название шаблону!");
			return false;
		}else if(tList.tName.value=="" || tList.tFile.value==""){
			alert("Укажите название шаблона и имя файла!");
			return false;
		}else{
			flag=1;
		}
	}
	if(flag==1){
		file[0]=tList.tName.value;
		file[1]=tList.tFile.value;
		returnValue=file;
		window.close();
	}
	<? }else{?>
		file[0]=tList.tName.value;
		file[1]=tList.tFile.value;
		returnValue=file;
		window.close();
	<? }?>
}

function checkName(){
	if(tList.tName.value.length>0 && tList.tFile.value.length>0){
		return true;
	}else{
		return false;
	}
}

function enableSubmit(){
	var sb=document.getElementById("sButton");
	if(tList.tName.value.length>0 && tList.tFile.value.length>0){
		sb.disabled=false;
	}else{
		sb.disabled=true;
	}
}
</script>
</head>
<body>
<?=$listTitle?>

<form name="tList" onSubmit="return checkName()">
<div onDblClick="setTemp()">
<select style="width:320px" id="temp_list" name="temp_list" size="15" onChange="setName()" onClick="setName()">
<? do{ ?>
<option value="<?=(isset($_GET['mode']) && ($_GET['mode']=='new' || $_GET['mode']=='open'))?$tr['id']:$tr['path']?>" class="opt"><?=$tr['name']?> [<?=$tr['path']?>]</option>
<? }while($tr=mysql_fetch_assoc($t)); ?>
</select>
</div>
<? 
if(isset($_GET['mode']) && ($_GET['mode']=='new' || $_GET['mode']=='open')){ 
	echo '<div style="display:none">';
}
?>
<br><br>
Название шаблона:<br>
<input type="text" size="40" value="" id="tName" name="tName" onKeyUp="enableSubmit()" onChange="enableSubmit()"><br>
Имя файла:<br>
<input type="text" size="40" value="" id="tFile" name="tFile" onKeyUp="enableSubmit()" onChange="enableSubmit()">
</div>
</form>
<center>
<input type="button" id="sButton" name="submit" disabled="disabled" value="<?=(isset($_GET['mode']) && ($_GET['mode']=='new' || $_GET['mode']=='open'))?'Открыть':'Сохранить'?>" class="but" onClick="setTemp()">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="button" name="close" value="Отмена" class="but" onClick="window.close()">
</center>
</body>
</html>
