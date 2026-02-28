 <?php
require_once('../Connections/dbconn.php');
 
$requiredUserLevel = array(1, 2); 
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php");

if($_POST['action']=='del'){
	$res=el_dbselect("DELETE FROM infoblocks WHERE id='".$_POST['id']."'", 0, $res);
	echo "<script language=javascript>alert('Инфоблок №".$_POST['id']." удален!')</script>";
}

$li=el_dbselect("SELECT id, name, ctime, author, edit FROM infoblocks", 0, $li);
$linf=mysql_fetch_assoc($li);
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<title>Список инфоблоков</title>
<script language="javascript">
function act1(mode, row){
	if (mode=="edit"){
		location.href="infoblocksedit.php?id="+row;
	}
	if (mode=="del"){
		var OK=confirm("Вы уверены, что хотите удалить инфоблок №"+row+" ?"); 
		if(OK){
			document.act.action.value=mode; document.act.id.value=row; document.act.submit();
		} 
	}
	if (mode=="link"){
		location.href="infoblockslink.php?id="+row;
	}
}
</script>
<link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>
<input type="button" value="Создать новый инфоблок" onClick="location.href='infoblocksedit.php?new'" class="but">
<? if(mysql_num_rows($li)>0){ ?>
<h4 align="center">Список инфоблоков</h4>
<form method="post" name="act"><input type="hidden" name="action"><input type="hidden" name="id"></form>

<? do{ ?>
<div id="<?=$linf['id']?>" class="row"><div id="left">ID<?=$linf['id']?>&nbsp;<?=$linf['name']?> <small>[автор: <?=$linf['author']?>, дата создания: <?=$linf['ctime']?>]</small></div>  <div id="right">
<img border="0" src="img/wlink.gif" title="Выбор разделов, где будет работать инфоблок" onClick="act1('link', <?=$linf['id']?>)">&nbsp;
<img border="0" src="img/menu_edit.gif" title="Редактировать содержимое инфоблока" onClick="act1('edit', <?=$linf['id']?>)">&nbsp;
<img border="0" src="img/menu_delete.gif" onClick="act1('del', <?=$linf['id']?>)" title="Удалить инфоблок"></div></div>
<? }while($linf=mysql_fetch_assoc($li));

}else{ ?>
<h4 align="center">Ни один инфоблок еще не создан.</h4>
<? }?>
</body>
</html>
