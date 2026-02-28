<?
require_once $_SERVER['DOCUMENT_ROOT']."/js/JsHttpRequest/lib/JsHttpRequest/JsHttpRequest.php";
$JsHttpRequest =& new JsHttpRequest("windows-1251");

include $_SERVER['DOCUMENT_ROOT'].'/Connections/dbconn.php';
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php");

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
 (isset($_REQUEST['pdir']))?$curr_dir=$_REQUEST['pdir']:$curr_dir=$_SERVER['DOCUMENT_ROOT']."/".$mode;
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
$file=$_FILES['file']['tmp_name'][$num];
$file_name=$_FILES['file']['name'][$num];
$uploaddir=$_REQUEST['pdir']."/";
if(!file_exists($uploaddir)){
	mkdir($uploaddir, 0777);
}

if ($file == "") {
	$mess.="\\nВам  необходимо указать файл для закачки.";
} else { 
	if($mode=='images'){
		if(getimagesize($file)){
			if($_ENV['upload_max_filesize']<$_FILES['file']['size'][$num]){
				if(move_uploaded_file($file, $uploaddir.$file_name)){
					@unlink($file);
					$mess.="\\nФайл \"".$file_name."\" успешно закачан в папку \"".$_REQUEST['pdir']."\" !";
				}else{
					$mess.="\\nНе удалось закачать файл!";
				}
	  		}else{
	    		$mess.="\\nРазмер файла \"".$_FILES['file']['name'][$num]."\" превышает максимально разрешенный сервером!";
	  		}	
		}else{
	    	$mess.="\\nЭтот файл не является изображением!\\nВ папку \"images\" и все внутренние папки можно закачивать только изображения.";
	 	}
	}elseif($mode=='files'){
	 	if($_ENV['upload_max_filesize']<$_FILES['file']['size'][$num]){
			if(move_uploaded_file($file, $uploaddir.$file_name)){
				@unlink($file);
				$mess.="\\nФайл \"".$file_name."\" успешно закачан в папку \"".$_REQUEST['pdir']."\" !";
			}else{
				$mess.="\\nНе удалось закачать файл!";
			}
		}else{
	    	$mess.="\\nРазмер файла \"".$_FILES['file']['name'][$num]."\" превышает максимально разрешенный сервером!";
		}	

	}
}
if($mess!=""){echo "<script>alert('$mess')</script>";}
}

if(count($_FILES['file'])>0){
	for($num=0; $num<count($_FILES['file']); $num++){
		if(strlen($_FILES['file']['name'][$num])>0)upimage($num); 
	}
}
 
if(!empty($_REQUEST['pdir'])){$filepath=$_REQUEST['pdir']."/";}else{$filepath=$_SERVER['DOCUMENT_ROOT']."/".$mode."/";}
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
<table width="100%"  border="0" cellpadding="0" cellspacing="0">
<thead>
<tr class="fixed">
<td style="width:30px" onMouseOver="abut('fdtype')" onMouseOut="but('fdtype')" class="upbutton" id="fdtype" title="Сортировать по типу" onClick="getFileList('<?=$mode?>', 'type', '<?=(isset($_REQUEST['pdir']))?$_REQUEST['pdir']:""?>', '<?=$s?>')"> Тип </td>

<td style="width:270px" class="upbutton" id="fdname" onMouseOver="abut('fdname')" onMouseOut="but('fdname')" title="Сортировать по названию" onClick="getFileList('<?=$mode?>', 'name', '<?=(isset($_REQUEST['pdir']))?$_REQUEST['pdir']:""?>', '<?=$s?>')"> Название </td>

<td class="upbutton" id="fdsize" onMouseOver="abut('fdsize')" onMouseOut="but('fdsize')" title="Сортировать по размеру файла" onClick="getFileList('<?=$mode?>', 'size', '<?=(isset($_REQUEST['pdir']))?$_REQUEST['pdir']:""?>', '<?=$s?>')"> Размер файла </td>
</tr> 
</thead> 

<tbody id="list">
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

if(!isset($_REQUEST['sort'])||$_REQUEST['sort']=="name"){
	@usort($filelist, "acmp");
}else{
	@usort($filelist, "cmp");
}
if(!isset($_REQUEST['sort'])||$_REQUEST['sort']=="type"){
	@usort($filelist, "bcmp");
	//@usort($filelist, "cmp");
}
//reset($filelist);
for($key=0; $key<count($filelist); $key++){
	if(strlen($filelist[$key]['name'])>0){
		unset($fsize);
		$fsizeim=@getimagesize($_SERVER['DOCUMENT_ROOT'].$puti."/".$filelist[$key]['name']);
?>
  <tr 
  <?php if($filelist[$key]['icon']=="/editor/img/folder.gif"){  	
	echo "onClick=\" hlightcf('".$key."', '".$filelist[$key]['name']."')\" 
	onfocus=\"this.blur()\"
	onDblClick=\"this.blur();getFileList('$mode', 'name', '".$filepath.$filelist[$key]['name']."', '$s');\"";
  }else{
  	echo "onClick=\"prewiev('".$filelist[$key]['name']."', '".$fsizeim[0]."', '".$fsizeim[1]."', '".$filelist[$key]['time']."', '".$filelist[$key]['type']."', ''); hlightc('".$key."', '".$filelist[$key]['name']."')\" onDblClick=\"openPictureWindow_Fever('".$filelist[$key]['type']."','/files/CIMG0070.jpg','10','10','Просмотр','200','100');prewiev('".$filelist[$key]['name']."', '".$fsizeim[0]."', '".$fsizeim[1]."', '".$filelist[$key]['time']."', '".$filelist[$key]['type']."', ''); hlightc('".$key."', '".$filelist[$key]['name']."')\"";
  }?> onMouseOver="hlight(<?=$key?>)" onMouseOut="uligh(<?=$key?>)" id="<?=$key?>"  >
    <td id="fdtype1">
    <img src="<?=$filelist[$key]['icon']?>" <?=($filelist[$key]['name']==$_POST['name'])?"style='filter: Alpha(Opacity=50)'":""?>>
    </td>
    <td class="vline">
    <a name=<?=$key?>></a><div id="fdname1_<?=$key?>"><?=$filelist[$key]['name'] ?></div>
    </td>
    <td class="vline" id="fdsize1">
	<?=($filelist[$key]['size']<1024)?$filelist[$key]['size']." bite":round($filelist[$key]['size']/1024,2)." kb" ?>
    </td>
  </tr>
<?php } 
} ?></tbody>
</table>
<? 
$GLOBALS['_RESULT']['answer']=str_replace('//', '/', $_REQUEST['pdir']);
?>