<?PHP $requiredUserLevel = array(1, 2); 
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php"); 

$blksize=stat($_SERVER['DOCUMENT_ROOT']);
$free_kb=0;
$free_mb=0;
$free=0;
$free_kb=diskfreespace($_SERVER['DOCUMENT_ROOT'])/$blksize['blksize'];
$free=$free_kb/$blksize['blksize'];
$free_mb=round($free, 2);
clearstatcache();

function readable_size($size) {
   if ($size < 1024) {
       return round($size, 2) . ' B';
   }
   $units = array("kB", "MB", "GB", "TB");
   foreach ($units as $unit) {
       $size = round($size / 1024, 2);
       if ($size < 1024) {
           break;
       }
   }
   return $size . ' ' . $unit;
}

function dskspace($dir) 
{ 
   $s = stat($dir); 
   $space = $s["blocks"]*512; 
   if (is_dir($dir)) 
   { 
     $dh = opendir($dir); 
     while (($file = readdir($dh)) !== false) 
       if ($file != "." and $file != "..") 
         $space += dskspace($dir."/".$file); 
     closedir($dh); 
   } 
   return $space; 
} 

?> 
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251" />
<title>Служебная информация</title>
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>


  <h4 align="center">Служебная информация</h4>
<table width="70%" border="1" align="center" cellpadding="3" cellspacing="0" class="el_tbl">
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>Свободное место на диске: </td>
    <td><? echo readable_size(diskfreespace($_SERVER['DOCUMENT_ROOT']."/")) ?></td>
  </tr>
  <tr>
    <td>Последний раз Административный раздел посещал: </td>
    <td><? 
	$user_query="SELECT * FROM cat ORDER BY last_time";
	$user = mysql_query($user_query, $dbconn) or die(mysql_error());
	$summ=mysql_num_rows($user)-1;
	mysql_data_seek($user, $summ);
	$row_user = mysql_fetch_assoc($user);
	echo $row_user['last_author'];
	 ?></td>
  </tr>
  <tr>
    <td>Время посещения: </td>
    <td><? echo $row_user['last_time']; ?></td>
  </tr>
  <tr>
    <td>Посещеннный раздел: </td>
    <td><? echo $row_user['name']?></td>
  </tr>
  <tr>
    <td>Что было сделано: </td>
    <td><?=($row_user['last_action']=="write")?"Внесены изменения":"Простое ознакомление"; ?></td>
  </tr>
</table>
</body>
</html>
