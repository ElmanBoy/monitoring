<? 
session_start();

function el_moveFiles($fileArray){
	reset($fileArray);
	$err=0;
	$errStr='';
	while(list($key, $val)= each($fileArray)){
		$from=$_SERVER['DOCUMENT_ROOT'].'/editor/e_modules/temp/'.$key;
		$to=$_SERVER['DOCUMENT_ROOT'].$val;
		if(file_exists($from)){
			chmod($from, 0755);
			if(file_exists($to)){
				$ext=explode('.', $to);
				$exten=$ext[count($ext)-1];
				$old=implode('.', array_splice($ext, 0, count($ext)-1));
				rename($to, $old.'_bakup_'.date('Y-m-d_H-i-s').'.'.$exten);  
			}
			if(copy($from, $to)){
				unlink($from);
			}else{
				$errStr.='<font color=red>Не удалось скопировать файл &laquo;'.$key.'&raquo;!</font><br>';
				$err++;
			}
		}else{
			$errStr.='<font color=red>Не найден файл &laquo;'.$key.'&raquo;!</font><br>';
			$err++;
		}
	}
	return ($err>0) ? $errStr : true;
}

function el_clearDir($dirName){
	$dir = dir($dirName);
	$err=0;
	$errStr='';
   while($file = $dir->read()) {
	   if($file != '.' && $file != '..') {
		   if(is_dir($dirName.'/'.$file)) {
			   el_delDir($dirName.'/'.$file);
		   }else{
			   if(!unlink($dirName.'/'.$file)){
					$errStr.= '<font color=red>Файл "'.$dirName.'/'.$file.'" не удалось удалить!';
					$err++;
			   }
		   }
	   }
   }
   return ($err>0) ? $errStr : true;
}


require_once($_SERVER['DOCUMENT_ROOT'].'/Connections/dbconn.php'); 

if(count($_GET)==0){
	$requiredUserLevel = array(1); 
	include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php");
if(file_exists($_SERVER['DOCUMENT_ROOT'].'/editor/e_modules/install_update.php')){
	unlink($_SERVER['DOCUMENT_ROOT'].'/editor/e_modules/install_update.php');
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<title>Обновление системы</title>
<link href="/editor/style.css" rel="stylesheet" type="text/css">
<style type="text/css">
<!--
body {
	margin-left: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
}
-->
</style></head>

<body>
<h4 align="center">Обновление системы </h4>
<?
mysql_select_db($database_dbconn, $dbconn);
$query_sn = "SELECT * FROM site_props";
$sn = mysql_query($query_sn, $dbconn) or die(mysql_error());
$row_sn = mysql_fetch_assoc($sn);
$el_snumber=$row_sn['serial_number'];
session_register('ussid');
el_2ini('session_update', session_id());
if(strlen($el_snumber)>0)
{?>

<center><script language=javascript> var  dw=document.body.clientWidth; var docH=document.body.clientHeight-53; document.write('<iframe align="middle" frameborder="0" height="'+docH+'" scrolling="auto" width="'+dw+'" src="http://croc-scs-control.ru/update/update.php?sn=<?=$el_snumber?>&ids=<?=session_id()?>&update"></iframe>')</script></center>

<? }else{
echo "<h4 align='center' style='color:red'>К сожалению, ваша копия не зарегистрирована. Поэтому обновления для вас не доступны.</h4>";}
?>
</body>
</html>
<?
}elseif(isset($_GET['update'])){
	echo $site_property['session_update'];
}elseif(isset($_GET['versions'])){
	include $_SERVER['DOCUMENT_ROOT'].'/editor/e_modules/modules_version.php';
	while(list($key, $val)=each($versions)){
		echo $key."=".$val."\n";
	}
}elseif(isset($_GET['src'])){
	while(list($key, $val)=each($_GET)){
		if($key!='src'){$fnm=$key;}
	}
	$line = "";
	$fp = fopen("http://imonster.ru/update/temp/".$fnm.".zip", "rb");
	while (!feof($fp)){ 
	  $line.=fread($fp, 1024); 
	} 
	fclose($fp);
	
	
	$tempdir=$_SERVER['DOCUMENT_ROOT'].'/editor/e_modules/temp';
	if(!is_dir($tempdir)){
		mkdir($tempdir, 0777);
	}
	$nfp=fopen($tempdir.'/'.$fnm.'.zip', 'wb');
	fwrite($nfp, $line);
	fclose($nfp);
	if(file_exists($tempdir.'/'.$fnm.'.zip')){
		$fp = fopen("http://imonster.ru/update/update.php?complite&".$fnm, "rb");
	}
	//Остается распоковать, найти файл install_update.php и инклюдить его на выполнение, потом удалить все временные файлы
	require_once($_SERVER['DOCUMENT_ROOT']."/editor/e_modules/zip.lib.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/editor/e_modules/pclzip.lib.php");
	$archive = new PclZip($tempdir.'/'.$fnm.'.zip');
	if ($archive->extract($p_path="temp/") == 0) {
		die("Ошибка распаковки архива : ".$archive->errorInfo(true));
	}
	
	$finstall=$_SERVER['DOCUMENT_ROOT'].'/editor/e_modules/temp/install_update.php';
	if(file_exists($finstall)){
		include_once($finstall);
		$install_result=el_moveFiles($fileArray);
		$clear_result=el_clearDir($tempdir);
		if($install_result && $clear_result){
			echo '<font color=green>Обновление &laquo;'.$updateName.' '.$updateVersion.'&raquo; прошло успешно!</font><br><br>';
			el_2modulesVer($updateName, $updateVersion);
			unlink($_SERVER['DOCUMENT_ROOT'].'/editor/e_modules/install_update.php');
		}else{
			echo $install_result.$clear_result;
		}
	}else{
		echo '<font color=red>Не найден файл инсталлятора &laquo;install_update.php&raquo;!</font><br>';
	}
	 
}
?>
