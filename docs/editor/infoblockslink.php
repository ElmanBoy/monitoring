<?php 
require_once('../Connections/dbconn.php');
$requiredUserLevel = array(1, 2); 
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php");
$_GET['id']=intval($_GET['id']);
if($_GET['id']>0){
	if(isset($_POST['Submit'])){
		$pag=(is_array($_POST['pages']))?implode(',', $_POST['pages']):$_POST['pages'];
		el_dbselect("UPDATE infoblocks SET pages='$pag', permanent='".intval($_POST['permanent'])."' WHERE id='".$_GET['id']."'", 0, $res);
		echo '<script>alert("Изменения сохранены!")</script>';
	}
	$curr=el_dbselect("SELECT name, pages, permanent FROM infoblocks WHERE id='".$_GET['id']."'", 0, $curr, 'row');
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<title>Список разделов для инфоблока</title>
<link href="style.css" rel="stylesheet" type="text/css">
<script language="javascript">
function selectAll(mode, enable){
	var s=document.pageForm;
	for(var i=0; i<s.length; i++){
		if(s[i].type=='checkbox'){
			s[i].checked=mode;
			s[i].disabled=enable;
		}
	}
}
</script>
</head>
<body>
<center>
<h4>Список разделов, где будет работать инфоблок &laquo;<?=$curr['name']?>&raquo;</h4>
 <input type="button" value="&laquo; К списку инфоблоков" onClick="location.href='infoblocks.php'" class="but" />&nbsp;&nbsp;&nbsp;
  <input type="button" value="Редактировать этот инфоблок &raquo;" onClick="location.href='infoblocksedit.php?id=<?=$_GET['id']?>'" class="but" />
 <form method="post" name="pageForm">
<br />
  <div style="overflow:auto; width:500px; height:300px; text-align:left">
  <? el_allPages('', explode(',', $curr['pages']), $curr['permanent']) ?>
  </div>
  <br />
  
  <input type="hidden" name="permanent" value="0">
 <input type="button" class="but" value="Работать везде" onClick="selectAll(true, true); permanent.value=1;">&nbsp;&nbsp;&nbsp;
 <input type="button" class="but" value="Выделить все" onClick="selectAll(true, false); permanent.value=0;">&nbsp;&nbsp;&nbsp;
 <input type="button" class="but" value="Снять выделение" onClick="selectAll(false, false); permanent.value=0;">&nbsp;&nbsp;&nbsp;
 <input type="submit" name="Submit" value="Сохранить" class="but" />
  </form>
  </center>
</body>
</html>
