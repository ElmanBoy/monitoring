<?php
require_once($_SERVER['DOCUMENT_ROOT']."/editor/e_modules/zip.lib.php");
include_once($_SERVER['DOCUMENT_ROOT']."/editor/e_modules/pclzip.lib.php");
  
  if(isset($_POST['create'])){
  	$archive = new PclZip($_SERVER['DOCUMENT_ROOT'].'/editor/director.zip');
  	$v_list = $archive->create($_SERVER['DOCUMENT_ROOT'].'/editor,'.$_SERVER['DOCUMENT_ROOT'].'/ban,'.$_SERVER['DOCUMENT_ROOT'].'/Connections,'.$_SERVER['DOCUMENT_ROOT'].'/tmpl,'.$_SERVER['DOCUMENT_ROOT'].'/modules,'.$_SERVER['DOCUMENT_ROOT'].'/images,'.$_SERVER['DOCUMENT_ROOT'].'/index.php,');
  		if ($v_list == 0) {
     		die("Error : ".$archive->errorInfo(true));
  		}
  }
  
  if(isset($_POST['extract'])){
  	$archive = new PclZip($_SERVER['DOCUMENT_ROOT'].'/editor/director.zip');
     if ($archive->extract() == 0) {
        die("Error : ".$archive->errorInfo(true));
     }
  }

?> 
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<title>Упаковка новой сборки</title>
</head>

<body>
<form name="form1" method="post" action="">
  <input type="submit" name="Submit" value="Создать">
  <input name="create" type="hidden" id="create">
</form>
<form name="form2" method="post" action="">
  <input type="submit" name="Submit2" value="Распаковать">
  <input name="extract" type="hidden" id="extract">
</form>
<p>&nbsp;</p>
</body>
</html>
