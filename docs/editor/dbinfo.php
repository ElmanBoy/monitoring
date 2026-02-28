<?
include $_SERVER['DOCUMENT_ROOT'].'/Connections/dbconn.php';
$i=el_dbselect("SELECT name, catalog_id FROM catalogs", 0, $i);
$ri=mysql_fetch_assoc($i);
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<title>Информация о каталогах</title>
</head>
<body>
<?
do{
	echo '<b>'.$ri['name'].' - таблица catalog_'.$ri['catalog_id'].'_data</b><br>';
	$f=el_dbselect("SELECT name, field FROM catalog_prop WHERE catalog_id='".$ri['catalog_id']."'", 0, $f);
	$rf=mysql_fetch_assoc($f);
	do{
		echo $rf['name'].' - field'.$rf['field'].'<br>';
	}while($rf=mysql_fetch_assoc($f));
	echo '<br><br>';
}while($ri=mysql_fetch_assoc($i));
?>

</body>
</html>
