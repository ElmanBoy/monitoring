<?
include $_SERVER['DOCUMENT_ROOT'] . '/Connections/site_props.php';
include $_SERVER['DOCUMENT_ROOT'] . '/Connections/gui.php';
include $_SERVER['DOCUMENT_ROOT'] . '/Connections/db.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/editor/modules/votes/poll_cookie.php';

function el_getvar($varname)
{
	global $$varname;
	$var = $$varname;
	return $var;
}

//Create session
function el_start_session()
{
	global $_COOKIE, $_GET, $database_dbconn, $dbconn, $_SESSION;
	if (!session_start()) {
		if (isset($_COOKIE['usid']) && strlen($_COOKIE['usid']) == 32) {
			$usid = $_COOKIE['usid'];
			session_id($usid);
			session_start();
		} elseif (isset($_GET['usid']) && strlen($_GET['usid']) == 32) {
			$usid = $_GET['usid'];
			session_id($usid);
			session_start();
			setcookie('usid', $usid, time() + 14400);
		} else {
			$usid = session_id(el_genpass(32));
			@session_start();
			@setcookie('usid', $usid, time() + 14400);
		}
	}
	//Logout
	if (isset($_POST['logout'])) {
		@setcookie('usid', '', time() - 3600);
		if (!@session_destroy() || isset($_SESSION['login'])) {
			el_start_session();
		}
		$usid = "";
	}


	if (isset($_POST['user_enter'])) {
		(!empty($_POST['user'])) ? $user_login = $_POST['user'] : $user_login = $_SESSION['login'];
		mysql_select_db($database_dbconn, $dbconn);
		$query_login = "SELECT * FROM phpSP_users WHERE user = '" . $user_login . "'";
		$login1 = mysql_query($query_login, $dbconn) or die(mysql_error());
		$row_login = mysql_fetch_assoc($login1);
		$totalRows_login = mysql_num_rows($login1);
		$pass = str_replace("$1$", "", crypt(md5($_POST['password']), '$1$'));
		if (($totalRows_login > 0) && (stripslashes($row_login['password']) === $pass)) {
			if ($row_login['userlevel'] > 0) {
				session_unregister("login");
				session_unregister("fio");
				$login = $row_login['user'];
				$fio = $row_login['fio'];
				@session_register("login");
				@session_register("fio");
				@setcookie('usid', $usid, time() + 14400);
			} else {
				$err = '<font color=red>Учетная запись не активирована!</font>';
			}
		} else {
			$err = '<font color=red>Неверный логин или пароль!</font>';
		}
	}
}

//Registering user
function el_reg_work($work_mode, $login, $cat)
{
	global $database_dbconn;
	global $dbconn;

	mysql_select_db($database_dbconn, $dbconn);
	$query_user = "SELECT fio FROM phpSP_users WHERE user='$login'";
	$user = mysql_query($query_user, $dbconn) or die(mysql_error());
	$row_user = mysql_fetch_assoc($user);
	$last_author = $row_user['fio'];

	$last_record = "UPDATE cat SET `last_time`=NOW(), `last_author`='$last_author', `last_action`='$work_mode' WHERE id='$cat'";
	mysql_select_db($database_dbconn, $dbconn);
	$Result1 = mysql_query($last_record, $dbconn) or die(mysql_error());
}


function el_genpass($numchar = 8)
{
	$str = "abcefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$start = mt_rand(1, (strlen($str) - $numchar));
	$string = str_shuffle($str);
	$password = substr($string, $start, $numchar);
	return ($password);
}

//Unpack packed file
function el_unpack($file)
{
	if (!is_scalar($file)) {
		user_error(gettype($file), E_USER_WARNING);
		return false;
	}
	$out = '';
	foreach (explode("\n", $file) as $line) {
		$c = count($bytes = unpack('c*', substr(trim($line), 1)));
		while ($c % 4) {
			$bytes[++$c] = 0;
		}
		foreach (array_chunk($bytes, 4) as $b) {
			$b0 = $b[0] == 0x60 ? 0 : $b[0] - 0x20;
			$b1 = $b[1] == 0x60 ? 0 : $b[1] - 0x20;
			$b2 = $b[2] == 0x60 ? 0 : $b[2] - 0x20;
			$b3 = $b[3] == 0x60 ? 0 : $b[3] - 0x20;
			$b0 <<= 2;
			$b0 |= ($b1 >> 4) & 0x03;
			$b1 <<= 4;
			$b1 |= ($b2 >> 2) & 0x0F;
			$b2 <<= 6;
			$b2 |= $b3 & 0x3F;
			$out .= pack('c*', $b0, $b1, $b2);
		}
	}
	return rtrim($out, "\0");
}

function checkUpdate()
{
	global $hostname_dbconn, $database_dbconn, $username_dbconn, $password_dbconn;
	$data = array();
	$s = el_dbselect("SELECT * FROM site_props", 0, $s, 'row');
	include $_SERVER['DOCUMENT_ROOT'] . '/editor/e_modules/modules_version.php';
	while (list($key, $val) = each($versions)) {
		$request[] = $key . '=' . $val;
	}
	$data =/*implode('&', $request)."&*/
		"ids=" . session_id() . "&sn=" . $s['serial_number'] . "\r\n\r\n";
	$hostname = "croc-scs-control.ru";
	$path = "/update/index.php";
	$line = "";

	// Устанавливаем соединение, имя которого
	// передано в параметре $hostname
	$fp = fsockopen($hostname, 80, $errno, $errstr, 30);
	// Проверяем успешность установки соединения
	if (!$fp) echo "!!!$errstr ($errno)<br />\n";
	else {
		// Формируем HTTP-заголовки для передачи
		// его серверу
		// Подделываем пользовательский агент, маскируясь
		// под пользователя WindowsXP
		$headers = "POST $path HTTP/1.1\r\n";
		$headers .= "Host: $hostname\r\n";
		$headers .= "Content-type: application/x-www-form-urlencoded\r\n";
		$headers .= "Content-Length: " . strlen($data) . "\r\n\r\n";
		//$headers .= "User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1\r\n";
		$headers .= "Connection: Close\r\n\r\n";
		// Отправляем HTTP-запрос серверу
		fwrite($fp, $headers . $data); //.$data
		// Получаем ответ
		while (!feof($fp)) {
			$line .= fgets($fp, 1024);
		}
		fclose($fp);
	}
	echo $line . strlen($data);
}

function create_cat($getvar)
{
	global $_POST;
	global $database_dbconn;
	global $dbconn;
	/*mysql_select_db($database_dbconn, $dbconn);
	$query_sql = "SELECT sql_select FROM site_props";
	$sql = mysql_query($query_sql, $dbconn) or die(mysql_error());
	$row_sql = mysql_fetch_assoc($sql);
	echo (el_unpack($row_sql['sql_select'])); 
	eval(el_unpack($line));
	if(substr_count($_POST['kod'], 'catalog')>0){
		$catEx=el_dbselect("SELECT id FROM catalogs WHERE catalog_id='".str_replace('catalog', '', $_POST['kod'])."'", 0, $catEx, 'row');
		if(strlen($catEx['id'])>0){
			el_dbselect("UPDATE catalogs SET cat='".$_POST['id']."' WHERE id=".$catEx['id'], 0, $res);
		}
	}*/
	$foldexist = '';
	if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "form1")) {
		$parid = $_POST['parent'];
		if (function_exists('el_translit')) {
			$_POST['path'] = el_translit($_POST['path']);
		}
		mysql_select_db($database_dbconn, $dbconn);
		$parentfolder = mysql_query("select * from cat where id='$parid'", $dbconn);
		$parentfold = mysql_fetch_assoc($parentfolder);
		if ($parentfold['path']) {
			$parentf = $parentfold['path'];
		} else {
			$parentf = "";
		}
		$rootfolder = $_SERVER['DOCUMENT_ROOT'];
		if (file_exists($rootfolder . $parentf . "/" . $_POST['path'])) {
			echo '<center><h5 style="color:red">Папка с таким названием уже существует. Выберите другое название.</h5></center>';
			$foldexist = 1;
		} else {
			mkdir($rootfolder . $parentf . "/" . $_POST['path'], 0777);
		}
		$newpath = $parentf . "/" . $_POST['path'];
		mysql_free_result($parentfolder);
		if (!copy($rootfolder . "/tmpl/index.php", $rootfolder . $newpath . "/index.php")) {
			rmdir($rootfolder . $parentf . "/" . $_POST['path']);
			mkdir($rootfolder . $parentf . "/" . $_POST['path'], 0777);
			copy($rootfolder . "/tmpl/index.php", $rootfolder . $newpath . "/index.php");
		}
		//chmod($rootfolder.$newpath."/index.php", 0755);
	}
	if ($foldexist != 1) {
		if (!$_POST['menu']) {
			$_POST['menu'] = "Y";
		}
		if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "form1")) {
			$insertSQL = sprintf("INSERT INTO cat (parent, name, `path`, menu, ptext, sort) VALUES (%s, %s, %s, %s, %s, %s)",
				GetSQLValueString($_POST['parent'], "int"),
				GetSQLValueString($_POST['name'], "text"),
				GetSQLValueString($newpath, "text"),
				GetSQLValueString($_POST['menu'], "text"),
				GetSQLValueString($_POST['ptext'], "text"),
				GetSQLValueString($_POST['sort'], "int"));

			mysql_select_db($database_dbconn, $dbconn);
			$Result1 = mysql_query($insertSQL, $dbconn) or die(mysql_error());
			//Определяем id новой записи
			$parid = $_POST['parent'];
			mysql_select_db($database_dbconn, $dbconn);
			$parentfolder = mysql_query("select * from cat where path='$newpath'", $dbconn);
			$parentfold = mysql_fetch_assoc($parentfolder);
			$idnew = $parentfold['id'];
			mysql_free_result($parentfolder);
		}
		if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "form1")) {
			$insertSQL = sprintf("INSERT INTO content (cat, `path`, text, caption, title, description, kod, template) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)",
				GetSQLValueString($idnew, "int"),
				GetSQLValueString($newpath, "text"),
				GetSQLValueString($_POST['contenttext'], "text"),
				GetSQLValueString($_POST['name'], "text"),
				GetSQLValueString($_POST['name'], "text"),
				GetSQLValueString($_POST['text'], "text"),
				GetSQLValueString($_POST['kod'], "text"),
				GetSQLValueString($_POST['template'], "text"));

			mysql_select_db($database_dbconn, $dbconn);
			$Result1 = mysql_query($insertSQL, $dbconn) or die(mysql_error());
		}
	}
}

function el_deleteCat($id)
{
	$p = el_dbselect("SELECT path, name FROM cat WHERE id='$id'", 0, $p, 'row');
	$c = el_dbselect("SELECT id FROM cat WHERE parent='$id'", 0, $p);
	el_dbselect("DELETE FROM cat WHERE id=$id", 0, $res);
	el_dbselect("DELETE FROM content WHERE cat=$id", 0, $res);
	if (mysql_num_rows($c) > 0) {
		$rc = mysql_fetch_assoc($c);
		do {
			if (intval($rc['id']) > 0) el_deleteCat($rc['id']);
		} while ($rc = mysql_fetch_assoc($c));
	}
	if (strlen($p['path']) > 1) el_delDir($_SERVER['DOCUMENT_ROOT'] . $p['path']);
	el_genSiteMap();
	el_log('Удален раздел &laquo;' . $p['name'] . '&raquo;', 1);
	el_clearcache('menu');
}

// Resize pictures
function el_resize_uploadpicture($simage_name, $simage, $smallW, $prefix)
{

	$bigsize = getimagesize($simage);
	$imH = $bigsize[1];
	$imW = $bigsize[0];
	$prop = $imW / $imH;
	$smallH = $smallW / $prop;
	$filesmall = imagecreatetruecolor($smallW, $smallH);

//preg_match("'^(.*)\.(gif|jpe?g|png)$'i", $simage_name, $ext);
	$imgArr = explode('.', $simage_name);
	$ext = strtolower($imgArr[count($imgArr) - 1]);

	switch (strtolower($ext)) {
		case 'jpg' :
		case 'jpeg':
			$image = imagecreatefromjpeg($simage);
			break;
		case 'gif' :
			$image = imagecreatefromgif($simage);
			break;
		case 'png' :
			$image = imagecreatefrompng($simage);
			break;
		default    :
			echo " <script language=javascript>alert('Неверный формат файла \"" . $simage_name . "\"! Пожалуйста, подберите другую картинку.'); document.location.href='" . $_SERVER['REQUEST_URI'] . "';</script>";
			break;
	}
	imagecopyresampled($filesmall, $image, 0, 0, 0, 0, $smallW, $smallH, $imW, $imH);
	imagejpeg($filesmall, $_SERVER['DOCUMENT_ROOT'] . "/images/" . $prefix . "_" . $simage_name, 100);
	imagedestroy($filesmall);
	$imagefield = "/images/" . $prefix . "_" . $simage_name;
	return $imagefield;

}

//Calculate new size in proportion
//$out[0] - height
//$out[1] - width
$sizeArray = array();
function el_propCalc($sW, $sH, $maxW, $maxH)
{
	global $sizeArray;
	$out = array();
	if ($sH > $maxH) {
		$out[0] = $maxH;
		$out[1] = round($sW / ($sH / $maxH));
	}
	if ($sW > $maxW) {
		$out[0] = round($sH / ($sW / $maxW));
		$out[1] = $maxW;
	}
	if ($out[1] > $maxW || $out[0] > $maxH) {
		el_propCalc($out[1], $out[0], $maxW, $maxH);
	} else {
		$sizeArray = $out;
		return $out;
	}
}

//If file with same name is exist, get new name 
function el_newName($dirName, $filename)
{
	$fullpath = "";
	$filenumber = "";
	$ext = "";
	$tempname = $tempname1 = $tempname2 = "";
	$filenumber = array();
	$dataArr = array();
	$dir = dir($dirName);
	$ext = substr(strrchr($filename, "."), 0);
	$tempname = str_replace($ext, "", $filename);
	while ($file = $dir->read()) {
		$ext1 = substr(strrchr($file, "."), 0);
		$tempname1 = str_replace($ext1, "", $file);
		$tempname2 = preg_replace("/\[(\d+)\]/", "", $tempname1);
		if ($file != '.' && $file != '..' && ($tempname1 == $tempname || $tempname2 == $tempname)) {
			preg_match_all("/\[(\d+)\]\./", $file, $number, PREG_PATTERN_ORDER);
			if (count($number[1]) > 0) {
				$filenumber[] = $number[1][count($number[1]) - 1];
			}
		}
	}

	if (file_exists($_SERVER['DOCUMENT_ROOT'] . $dirName . $filename) || count($filenumber) > 0) {
		$filenumber1 = max($filenumber);
		$newname = $tempname . '[' . ($filenumber1 + 1) . ']' . $ext;
	}

	return (strlen($newname) > 0) ? $newname : $filename;
}


// Resize pictures(extendet)
function el_resize_images($image_tmp_name, $image_name, $maxW, $maxH, $prefix)
{
	global $sizeArray;
	$newsize = array();
	$image_name = el_translit($image_name);
	$target_name = $prefix . $image_name;
	if (file_exists($image_tmp_name)) {
		$bigsize = getimagesize($image_tmp_name);
	} else {
		$bigsize = getimagesize($_SERVER['DOCUMENT_ROOT'] . '/images/gallery/_' . $image_name);
		$image_tmp_name = $_SERVER['DOCUMENT_ROOT'] . '/images/gallery/_' . $image_name;
	}
	$imH = $bigsize[1];
	$imW = $bigsize[0];
	//preg_match("'^(.*)\.(gif|jpe?g|png)$'i", $image_name, $ext);
	//$ext[2]=strtolower($ext[2]);
	$imgArr = explode('.', $image_name);
	$ext = strtolower($imgArr[count($imgArr) - 1]);

	if ($imW > $maxW || $imH > $maxH) {
		el_propCalc($imW, $imH, $maxW, $maxH);
		$smallH = $sizeArray[0];
		$smallW = $sizeArray[1];
		$filesmall = imagecreatetruecolor($smallW, $smallH);
		switch ($ext) {
			case 'jpg' :
			case 'jpeg':
				$image = imagecreatefromjpeg($image_tmp_name);
				imagecopyresampled($filesmall, $image, 0, 0, 0, 0, $smallW, $smallH, $imW, $imH);
				imagejpeg($filesmall, $_SERVER['DOCUMENT_ROOT'] . "/images/" . $target_name, 100);
				imagedestroy($filesmall);
				break;
			case 'gif' :
				$image = imagecreatefromgif($image_tmp_name);
				$trans_color = imagecolortransparent($image);
				$trans_index = imagecolorallocate($image, $trans_color['red'], $trans_color['green'], $trans_color['blue']);
				imagecolortransparent($image, $trans_index);
				imagecopyresampled($filesmall, $image, 0, 0, 0, 0, $smallW, $smallH, $imW, $imH);
				imagegif($filesmall, $_SERVER['DOCUMENT_ROOT'] . "/images/" . $target_name);
				imagedestroy($filesmall);
				break;
			case 'png' :
				$image = imagecreatefrompng($image_tmp_name);
				$filesmall = imagecreatetruecolor($smallW, $smallH);
				imagealphablending($filesmall, false);
				imagecopyresampled($filesmall, $image, 0, 0, 0, 0, $smallW, $smallH, $imW, $imH);
				imagesavealpha($filesmall, true);
				imagepng($filesmall, $_SERVER['DOCUMENT_ROOT'] . "/images/" . $target_name);
				imagedestroy($filesmall);

				break;
			default    :
				echo "<script language=javascript>alert('Неверный формат файла \"" . $image_name . "\"! Пожалуйста, подберите другую картинку.'); document.location.href='" . $_SERVER['REQUEST_URI'] . "';</script>";
				break;
		}

	} else {
		if ($ext == 'jpg' || $ext == 'jpeg' || $ext == 'gif' || $ext == 'png') {
			if (!copy($image_tmp_name, $_SERVER['DOCUMENT_ROOT'] . "/images/" . $target_name)) {
				return false;
			}
		} else {
			echo " <script language=javascript>alert('Неверный формат файла \"" . $image_name . "\"! Пожалуйста, подберите другую картинку.'); document.location.href='" . $_SERVER['REQUEST_URI'] . "';</script>";
		}
	}
	$imagefield = "/images/" . $target_name;
	clearstatcache();
	if (file_exists($_SERVER['DOCUMENT_ROOT'] . $imagefield)) {
		@chmod($_SERVER['DOCUMENT_ROOT'] . $imagefield, 0755);
		return true;
	} else {
		return false;
	}
}

//imagelogo($image, $watermark, imagesx($image), imagesy($image), imagesx($watermark), imagesy($watermark), 'random'); 
function el_imageLogo($dst_image_path, $src_image_path, $position = 'bottom-left')
{
	$dst_pathArr = explode('/', $dst_image_path);
	$dst_name = $dst_pathArr[count($dst_pathArr) - 1];
	copy($_SERVER['DOCUMENT_ROOT'] . $dst_image_path, $_SERVER['DOCUMENT_ROOT'] . '/images/temporary/' . $dst_name);
	$dst_image_path1 = $_SERVER['DOCUMENT_ROOT'] . '/images/temporary/' . $dst_name;

	$src_image_path = $_SERVER['DOCUMENT_ROOT'] . $src_image_path;
	$dst_image_path = $_SERVER['DOCUMENT_ROOT'] . $dst_image_path;

	/*preg_match("'^(.*)\.(gif|jpe?g|png)$'i", $src_image_path, $ext);
	$ext[2]=strtolower($ext[2]);*/
	$imgArr = explode('.', $src_image_path);
	$ext = strtolower($imgArr[count($imgArr) - 1]);
	/*preg_match("'^(.*)\.(gif|jpe?g|png)$'i", $dst_image_path, $extd);
	$extd[2]=strtolower($extd[2]);*/
	$imgArr = explode('.', $dst_image_path);
	$extd = strtolower($imgArr[count($imgArr) - 1]);
	$src_validImage = $dst_validImage = 1;
	switch ($ext) {
		case 'jpg' :
		case 'jpeg':
			$src_image = imagecreatefromjpeg($src_image_path);
			break;
		case 'gif' :
			$src_image = imagecreatefromgif($src_image_path);
			break;
		case 'png' :
			$src_image = imagecreatefrompng($src_image_path);
			break;
		default    :
			echo "<script language=javascript>alert('Неверный формат файла \"" . $src_image_path . "\"! Пожалуйста, подберите другую картинку.'); document.location.href='" . $_SERVER['REQUEST_URI'] . "';</script>";
			$src_validImage = 0;
			break;
	}
	if ($src_validImage == 1) {
		$src_w = imagesx($src_image);
		$src_h = imagesy($src_image);
	}
	switch ($extd) {
		case 'jpg' :
		case 'jpeg':
			$dst_image = imagecreatefromjpeg($dst_image_path1);
			break;
		case 'gif' :
			$dst_image = imagecreatefromgif($dst_image_path1);
			break;
		case 'png' :
			$dst_image = imagecreatefrompng($dst_image_path1);
			break;
		default    :
			echo "<script language=javascript>alert('Неверный формат файла \"" . $dst_image_path . "\"! Пожалуйста, подберите другую картинку.'); document.location.href='" . $_SERVER['REQUEST_URI'] . "';</script>";
			$dst_validImage = 0;
			break;
	}
	if ($dst_validImage == 1) {
		$dst_w = imagesx($dst_image);
		$dst_h = imagesy($dst_image);
		imagealphablending($dst_image, true);
		imagealphablending($src_image, true);
		if ($position == 'random') {
			$position = rand(1, 8);
		}
		switch ($position) {
			case 'top-right':
			case 'right-top':
			case 1:
				imagecopy($dst_image, $src_image, ($dst_w - $src_w), 0, 0, 0, $src_w, $src_h);
				break;
			case 'top-left':
			case 'left-top':
			case 2:
				imagecopy($dst_image, $src_image, 0, 0, 0, 0, $src_w, $src_h);
				break;
			case 'bottom-right':
			case 'right-bottom':
			case 3:
				imagecopy($dst_image, $src_image, ($dst_w - $src_w), ($dst_h - $src_h), 0, 0, $src_w, $src_h);
				break;
			case 'bottom-left':
			case 'left-bottom':
			case 4:
				imagecopy($dst_image, $src_image, 0, ($dst_h - $src_h), 0, 0, $src_w, $src_h);
				break;
			case 'center':
			case 5:
				imagecopy($dst_image, $src_image, (($dst_w / 2) - ($src_w / 2)), (($dst_h / 2) - ($src_h / 2)), 0, 0, $src_w, $src_h);
				break;
			case 'top':
			case 6:
				imagecopy($dst_image, $src_image, (($dst_w / 2) - ($src_w / 2)), 0, 0, 0, $src_w, $src_h);
				break;
			case 'bottom':
			case 7:
				imagecopy($dst_image, $src_image, (($dst_w / 2) - ($src_w / 2)), ($dst_h - $src_h), 0, 0, $src_w, $src_h);
				break;
			case 'left':
			case 8:
				imagecopy($dst_image, $src_image, 0, (($dst_h / 2) - ($src_h / 2)), 0, 0, $src_w, $src_h);
				break;
			case 'right':
			case 9:
				imagecopy($dst_image, $src_image, ($dst_w - $src_w), (($dst_h / 2) - ($src_h / 2)), 0, 0, $src_w, $src_h);
				break;
		}

		switch ($extd) {
			case 'jpg' :
			case 'jpeg':
				imagejpeg($dst_image, $dst_image_path);
				break;
			case 'gif' :
				imagegif($dst_image, $dst_image_path);
				break;
			case 'png' :
				imagepng($dst_image, $dst_image_path);
				break;
		}
		unlink($dst_image_path1);
	}
}


####################SECURITY###########################################################################
// Check and clean GET`s vars
function el_cleanvars($var, &$varname)
{
	if (strlen($var) > 0) {
		@preg_match_all("/((\%3D)|(=))[^\n]*((\%27)|(\')|(\-\-)|(\%3B)|(;))/i", $var, $test);
		@preg_match_all("/\w*((\%27)|(\'))((\%6F)|o|(\%4F))((\%72)|r|(\%52))/ix", $var, $test2);
		@preg_match_all("/((\%3C)|<)[^\n]+((\%3E)|>)/i", $var, $test3);
		if ((count($test[0]) > 0) || (count($test2[0]) > 0) || (count($test3[0]) > 0)) {
			$var = "";
		}
		return $_GET[$varname] = $var;
	}
}

function el_varsprocess()
{
	reset($_GET);
	array_walk($_GET, 'el_cleanvars');
}

//el_varsprocess();

function el_strongcleanvars1()
{
	global $_GET;
	foreach ($_GET as $varname => $var) {
		if(is_array($var)){
			foreach($var as $key => $val){
				if (strlen($val) > 0) {
					$val = preg_replace("/[^a-zA-ZА-Яа-яЁё0-9 -_]|char|insert|delete|update|select|union|%|--|\'|/i",
						"", $val);//\"|\?|or
					$_GET[$varname][$key] = str_replace('\\', '', $val);
				}
			}
		}else{
			if (strlen($var) > 0) {
				$var = preg_replace("/[^a-zA-ZА-Яа-яЁё0-9 -_]|char|insert|delete|update|select|union|%|--|\'|/i", "", $var);//\"|\?|or
				$_GET[$varname] = str_replace('\\', '', $var);
			}
		}
	}
}

function el_strongcleanvars($var, &$varname)
{
	/*if (strlen($var) > 0) {
		$var = preg_replace("/[^a-zA-ZА-Яа-яЁё0-9 -_]|char|insert|delete|update|select|union|%|--|\'|/i", "", $var);//\"|\?|or
	}
	return $_GET[$varname] = $var;*/
	el_strongcleanvars1();
}

function el_digitvars($var)
{
	if (strlen($var) > 0) {
		preg_match_all("/[^0-9]|char|insert|delete|update|select|union|or|%|--|\'|\"|\?/i", $var, $test);
		if (count($test[0]) > 0) {
			$var = "";
		}
	}
	return $var;
}

function el_wordvars($var)
{
	if (strlen($var) > 0) {
		$var = preg_replace("/[^a-zA-ZА-Яа-яЁё0-9]|char|insert|delete|update|select|union|%|--|\'|\"|\?/i", "", $var);//|or
	}
	return $var;
}

function el_cyrpost($var, &$varname)
{
	if (strlen($var) > 0) {
		$var = preg_replace("/[^a-zA-Zа-яА-ЯЁё0-9 -\.[or]_]|char|insert|delete|update|select|union|%|--|\'|/im", "", $var);//\"|\?
	}
	return $_POST[$varname] = $var;
}

function el_strongvarsprocess()
{
	reset($_GET);
	reset($_POST);
	array_walk($_GET, 'el_strongcleanvars');
	if (!defined('NOCLEAN') || NOCLEAN != 'NO') {
		array_walk($_POST, 'el_cyrpost');
	}
}

function noTags()
{
	reset($_GET);
	reset($_POST);
	while (list($key, $var) = each($_POST)) {
		$_POST[$key] = strip_tags($var, '<br>');
	}
	while (list($key, $var) = each($_GET)) {
		$_GET[$key] = strip_tags($var, '<br>');
	}
}

#########################################################################################################

//Create session
if (!isset($_COOKIE['usid'])) {
	session_start();
	$usid = session_id();
//session_register("usid");
	setcookie('usid', $usid, time() + 14400, '/', '');
}
if (isset($_SESSION['usid'])) {
	$usid = $_SESSION['usid'];
} else {
	$usid = $_COOKIE['usid'];
}
$user_login = $_SESSION['login'];


// Autorization
if (isset($_POST['user_enter'])) {
	(!empty($_POST['user'])) ? $user_login = $_POST['user'] : $user_login = $_SESSION['login'];
	mysql_select_db($database_dbconn, $dbconn);
	$query_login = "SELECT * FROM phpSP_users WHERE user = '$user_login'";
	$login = mysql_query($query_login, $dbconn) or die(mysql_error());
	$row_login = mysql_fetch_assoc($login);
	$totalRows_login = mysql_num_rows($login);
	$pass = str_replace("$1$", "", crypt(md5($_POST['password']), '$1$'));
	if (($totalRows_login > 0) && (stripslashes($row_login['password']) === $pass)) {
		$login = $row_login['user'];
		$fio = $row_login['fio'];
		$_SESSION["login"] = $row_login['user'];
		$_SESSION["fio"] = $row_login['fio'];
		$_SESSION['user_level'] = $_SESSION['ulevel'] = $row_login['userlevel'];
		setcookie('usid', $usid, time() + 14400, '/', '');
	}
}

if (isset($_POST['logout'])) {
	@setcookie('usid');
	@session_start($idsess);
	session_destroy();
	$usid = "";
}

//Registration any events in admin zone
function el_admin_secure()
{
	$requiredUserLevel = array(1, 2);
	include($_SERVER['DOCUMENT_ROOT'] . "/editor/secure/secure.php");
	(isset($submit)) ? $work_mode = "write" : $work_mode = "read";
	el_reg_work($work_mode, $login, $_GET['cat']);
	return $requiredUserLevel;
	return eval(include($_SERVER['DOCUMENT_ROOT'] . "/editor/secure/secure.php"));
}


//Sort news for years
function el_news_years()
{
	global $row_dbcontent;
	global $database_dbconn;
	global $dbconn;

	if ($row_dbcontent['kod'] == "news") {
		mysql_select_db($database_dbconn, $dbconn);
		$query_sort = "SELECT year FROM news ORDER BY year ASC";
		$sort = mysql_query($query_sort, $dbconn) or die(mysql_error());
		$row_sort = mysql_fetch_assoc($sort);
		$years = array();
		$n = 0;
		do {
			$allyears = "<a href='?year=" . $row_sort['year'] . "' id=link><div onMouseOver= id='line'  onMouseOut= id='line0' id='line0'>Новости за " . $row_sort['year'] . " год </div></a><br>";
			$n = array_push($years, $allyears);
		} while ($row_sort = mysql_fetch_assoc($sort));
		$years = array_unique($years);
		rsort($years);
		for ($i = 0; $i < $n; $i++) {
			echo $years[$i];
		}
	}
}

//Call visual editor
function el_html_editor($html_field)
{
	include $_SERVER['DOCUMENT_ROOT'] . "/editor/e_modules/html_editor.php";
}


//Print meta-info
function el_meta()
{
	global $database_dbconn;
	global $dbconn;
	global $path, $_GET, $row_dbcontent;
	$colname_detail = "-1";
	if (isset($path)) {
		$colname_detail = (get_magic_quotes_gpc()) ? $path : addslashes($path);
	}
	mysql_select_db($database_dbconn, $dbconn);
	$query_detail1 = sprintf("SELECT * FROM `content` WHERE path = '%s'", $colname_detail);
	$detail1 = mysql_query($query_detail1, $dbconn) or die(mysql_error());
	$row_detail1 = mysql_fetch_assoc($detail1);
	$totalRows_detail1 = mysql_num_rows($detail1);
	if ($row_dbcontent['kod'] == 'alltags') {
		$ttext = (strlen(trim($_GET['tag'])) > 0) ? htmlspecialchars(urldecode($_GET['tag'])) : 'Все теги';
		echo '<title>' . $ttext . ' - ' . $_SERVER['SERVER_NAME'] . '</title>
		<meta name="description" content="' . strip_tags(str_replace('"', '\'', $row_detail1['description'])) . '">
	  	<meta name="keywords" content="' . strip_tags(str_replace('"', '\'', $row_detail1['keywords'])) . '">
		<META name="engine-copyright" content="Adventor CMS (www.adventor.ru)">
		';
	}

	if (isset($_GET['id']) && strlen($row_dbcontent['kod']) > 0) {
		if (substr_count($row_dbcontent['kod'], 'catalog') > 0) {
			$catalog_id = str_replace("catalog", "", $row_dbcontent['kod']);
			$title_catalog = el_dbselect("SELECT field FROM catalog_prop WHERE title='1' AND catalog_id='$catalog_id'", 0, $title_catalog, 'row');
			mysql_select_db($database_dbconn, $dbconn);
			$query_catalog = "SELECT " . 'field' . $title_catalog['field'] . ", field1 FROM catalog_" . $catalog_id . "_data WHERE id='" . intval($_GET['id']) . "'";
			$catalog = mysql_query($query_catalog, $dbconn) or die(mysql_error());
			$row_catalog = mysql_fetch_assoc($catalog);
			$title = (strlen($row_catalog['field1']) > 0) ? $row_catalog['field1'] : $row_catalog['field' . $title_catalog['field']];

			echo '<title>' . stripslashes(htmlspecialchars($title)) . ' :: ' . $_SERVER['SERVER_NAME'] . '</title>
		<meta name="description" content="' . strip_tags(str_replace('"', '\'', $row_catalog['field1'])) . '">
		<meta name="keywords" content="' . strip_tags(str_replace('"', '\'', $row_detail1['keywords'])) . '">
		<META name="engine-copyright" content="Adventor CMS (www.adventor.ru)">
		';
		} elseif ($row_dbcontent['kod'] == 'gallery') {
			$t = el_dbselect("SELECT text FROM photo WHERE id='" . $_GET['id'] . "'", 0, $t);
			$te = mysql_fetch_assoc($t);
			echo '<title>' . stripslashes(strip_tags($te['text'])) . ' :: ' . $_SERVER['SERVER_NAME'] . '</title>
		<meta name="description" content="' . strip_tags(str_replace('"', '\'', $row_detail1['description'])) . '">
		<meta name="keywords" content="' . strip_tags(str_replace('"', '\'', $row_detail1['keywords'])) . '">
		<META name="engine-copyright" content="Adventor CMS (www.adventor.ru)">
		';
		} elseif ($row_dbcontent['kod'] == 'news') {
			$t = el_dbselect("SELECT title, anons FROM news WHERE id='" . $_GET['id'] . "'", 0, $t);
			$te = mysql_fetch_assoc($t);
			echo '<title>' . stripslashes(htmlspecialchars($te['title'])) . ' :: ' . $_SERVER['SERVER_NAME'] . '</title>
		<meta name="description" content="' . strip_tags(str_replace('"', '\'', $te['anons'])) . '">
		<meta name="keywords" content="' . strip_tags(str_replace('"', '\'', $row_detail1['keywords'])) . '">
		<META name="engine-copyright" content="Adventor CMS (www.adventor.ru)">
		';
		} elseif ($row_dbcontent['kod'] == 'articles') {
			$t = el_dbselect("SELECT title, anons FROM articles WHERE id='" . $_GET['id'] . "'", 0, $t);
			$te = mysql_fetch_assoc($t);
			echo '<title>' . stripslashes(htmlspecialchars($te['title'])) . ' :: ' . $_SERVER['SERVER_NAME'] . '</title>
		<meta name="description" content="' . strip_tags(str_replace('"', '\'', $te['anons'])) . '">
		<meta name="keywords" content="' . strip_tags(str_replace('"', '\'', $row_detail1['keywords'])) . '">
		<META name="engine-copyright" content="Adventor CMS (www.adventor.ru)">
		';
		}

	} else {

		echo '<title>' . stripslashes(htmlspecialchars($row_detail1['title'])) . ' :: ' . $_SERVER['SERVER_NAME'] . '</title>
	  <meta name="description" content="' . strip_tags(str_replace('"', '\'', $row_detail1['description'])) . '">
	  <meta name="keywords" content="' . strip_tags(str_replace('"', '\'', $row_detail1['keywords'])) . '">
	  <META name="engine-copyright" content="Adventor CMS (www.adventor.ru)">
	  ';
	}
}

//Remove folder and files
function el_delDir($dirName)
{
	$err = 0;
	if (empty($dirName)) {
		return false;
	}
	if (file_exists($dirName)) {
		$dir = dir($dirName);
		while ($file = $dir->read()) {
			if ($file != '.' && $file != '..') {
				if (is_dir($dirName . '/' . $file)) {
					el_delDir($dirName . '/' . $file);
				} else {
					if (!unlink($dirName . '/' . $file)) {
						echo '<script>alert("Файл \"' . $dirName . '/' . $file . '\" не удалось удалить!")</script>';
						$err++;
					}
				}
			}
		}
		if (!rmdir($dirName . '/' . $file)) {
			echo '<script>alert("Папку \"' . $dirName . '/' . $file . '\" не удалось удалить!")</script>';
			$err++;
		}
	} else {
		echo '<script>alert("Папка \"' . $dirName . '\" не существует.")</script>';
		$err++;
	}
}

//Write to ini-file
function el_2ini($index, $value)
{
	global $site_property;
	$flag = 0;
	$output = '';
	if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/Connections/site_props.php')) {
		$filen = fopen($_SERVER['DOCUMENT_ROOT'] . '/Connections/site_props.php', 'w');
		$outputn = "<? \$site_property=array(
    'domain'=>'" . $_SERVER['SERVER_NAME'] . "'
    ) ?>";
		if (fwrite($filen, $outputn) === FALSE) {
			el_showalert("error", "Не могу произвести запись в файл настроек.");
		}
		fclose($filen);
		el_2ini($index, $value);
	} else {
		@include $_SERVER['DOCUMENT_ROOT'] . '/Connections/site_props.php';
		while (list($insertindex, $insertvar) = each($site_property)) {
			if ($insertindex == $index) {
				$site_property[$insertindex] = $value;
				$flag = 1;
			}
		}
		if ($flag != 1) {
			$app = "'" . $index . "'=>'" . $value . "'\n";
		} else {
			$app = "";
		}
	}
	reset($site_property);
	while (list($getindex, $getvar) = each($site_property)) {
		$output .= "'" . $getindex . "'=>'" . $getvar . "',\n";
	}
	$output = "<? \$site_property=array(\n" . $output . $app . ") ?>";
	$file = fopen($_SERVER['DOCUMENT_ROOT'] . "/Connections/site_props.php", w);
	if (fwrite($file, $output) === FALSE) {
		el_showalert("error", "Не могу произвести запись в файл настроек.");
	}
	fclose($file);
	@include $_SERVER['DOCUMENT_ROOT'] . '/Connections/site_props.php';
}

//Write to ini-file
function el_2modulesVer($index, $value)
{
	global $site_property;
	$flag = 0;
	$output = '';
	$fileName = $_SERVER['DOCUMENT_ROOT'] . '/editor/e_modules/modules_vrsion.php';
	if (!file_exists($fileName)) {
		$filen = fopen($fileName, 'w');
		$outputn = "<? \$site_property=array(
    'domain'=>'" . $_SERVER['SERVER_NAME'] . "'
    ) ?>";
		if (fwrite($filen, $outputn) === FALSE) {
			el_showalert("error", "Не могу произвести запись в файл настроек.");
		}
		fclose($filen);
		el_2ini($index, $value);
	} else {
		@include $fileName;
		while (list($insertindex, $insertvar) = each($site_property)) {
			if ($insertindex == $index) {
				$site_property[$insertindex] = $value;
				$flag = 1;
			}
		}
		if ($flag != 1) {
			$app = "'" . $index . "'=>'" . $value . "'\n";
		} else {
			$app = "";
		}
	}
	reset($site_property);
	while (list($getindex, $getvar) = each($site_property)) {
		$output .= "'" . $getindex . "'=>'" . $getvar . "',\n";
	}
	$output = "<? \$site_property=array(\n" . $output . $app . ") ?>";
	$file = fopen($fileName, 'w');
	if (fwrite($file, $output) === FALSE) {
		el_showalert("error", "Не могу произвести запись в файл настроек.");
	}
	fclose($file);
	@include $fileName;
}


//Reading content from cache
function el_readcache($cat)
{
	global $site_property;
	if ($site_property['cache' . $cat] == 'Y') {
		if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/editor/cache/' . $cat . '.htm')) {
			include($_SERVER['DOCUMENT_ROOT'] . '/editor/cache/' . $cat . '.htm');
			return 1;
		} else {
			return 0;
		}
	} else {
		return 0;
	}
}


//Writing content to cache
function el_writecache($cat, $cachedata)
{
	global $site_property;
	if ($site_property['cache' . $cat] == 'Y') {
		$cf = @fopen($_SERVER['DOCUMENT_ROOT'] . '/editor/cache/' . $cat . '.htm', 'w')
		or die('Не удается запись в кэш.');
		fputs($cf, $cachedata);
		fclose($cf);
		@chmod($_SERVER['DOCUMENT_ROOT'] . '/editor/cache/' . $cat . '.htm', 0777);
	}
}

//Clearing cache
function el_clearcache($subdir = '', $cat = '')
{
	if ($cat == '') {
		$dirName = $_SERVER['DOCUMENT_ROOT'] . '/editor/cache/' . $subdir;
		if (!is_dir($dirName)) {
			mkdir($dirName, 0777);
		}
		$dir = dir($dirName);
		while ($file = $dir->read()) {
			if ($file != '.' && $file != '..') {
				if (is_dir($dirName . '/' . $file)) {
					el_clearcache($file, $cat);
				} else {
					if (file_exists($dirName . '/' . $file)) {
						unlink($dirName . '/' . $file);
					}
				}
			}
		}
	} else {
		unlink($_SERVER['DOCUMENT_ROOT'] . '/editor/cache/' . $cat . '.htm');
	}
}

//Translate filesize from 2M format to 2097152
function el_returnbytes($val)
{
	$val = trim($val);
	$last = strtolower($val{strlen($val) - 1});
	switch ($last) {
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
	}

	return $val;
}

//Glue many files to one file from srecified directory
function el_gluefiles($dirname, $outfile)
{
	$line = '';
	$dir = dir($dirname);
	while ($file = $dir->read()) {
		if ($file != '.' && $file != '..') {
			$fd = fopen($dirname . '/' . $file, 'r');
			while (!feof($fd)) {
				$line .= fgets($fd, 4096);
			}
			//@unlink($dirname.'/'.$file) or die('Файл '.$dirname.'/'.$file.' не удалось удалить из временной папки!');
		}
	}
	if (strlen($line) > 0) {
		$fs = fopen($outfile, 'w') or die('Не удается создать файл ' . $outfile . '! Возможно недостаточно прав для записи в указанную папку.');
		fputs($fs, $line);
		fclose($fs);
	}
}

//Функция перевода русских слов в транслит
function el_translit($string, $type = '')
{
	$string = ($type == 'file') ? preg_replace('/\\.(?![^.]*$)/', '_', $string) : $string;
	$r_trans = array(
		"а", "б", "в", "г", "д", "е", "ё", "ж", "з", "и", "й", "к", "л", "м",
		"н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "ч", "ш", "щ", "э",
		"ю", "я", "ъ", "ы", "ь", "А", "Б", "В", "Г", "Д", "Е", "Ё", "Ж", "З", "И", "Й", "К", "Л", "М",
		"Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Ч", "Ш", "Щ", "Э",
		"Ю", "Я", "Ъ", "Ы", "Ь", " "
	);
	$e_trans = array(
		"a", "b", "v", "g", "d", "e", "e", "j", "z", "i", "i", "k", "l", "m",
		"n", "o", "p", "r", "s", "t", "u", "f", "h", "c", "ch", "sh", "sch",
		"e", "yu", "ya", "", "i", "", "A", "B", "V", "G", "D", "E", "E", "J", "Z", "I", "I", "K", "L", "M",
		"N", "O", "P", "R", "S", "T", "U", "F", "H", "C", "CH", "SH", "SCH",
		"E", "YU", "YA", "", "I", "", "_"
	);
	$string = str_replace($r_trans, $e_trans, $string);
	return $string;
}


//Функция для парсинга шаблона
function parse_template($row_catalog, $template_row, $files, $bgcolor = '', $url = '')
{
	$fnames = array();
	$link_name = array();
	$fnames = split(", ", $files);
	$template_row = str_replace('"', "'", $template_row);
	$template_row = 'echo "' . stripslashes($template_row);
	$template_row = str_replace("[i]", '$row_catalog[', $template_row);
	$template_row = str_replace("[/i]", "]", $template_row);
	$template_row = str_replace("[a]", '<a href=$path' . $url . '/?id=$row_catalog[id]>', $template_row);
	$template_row = str_replace("[/a]", "</a>", $template_row);
	$template_row = str_replace("[paging]", '".paging($pn, $currentPage, $queryString_catalog, $totalPages_catalog, $maxRows_catalog, $tr)."', $template_row);
	$template_row = str_replace("[search]", '".search_form($catalog_id, $cat)."', $template_row);

	$template_row = str_replace("[bgcolor]", " style='background-color:#" . $bgcolor . "' bgcolor='#" . $bgcolor . "'", $template_row);

	$template_row = str_replace("[videoplayer]", '".el_insertPlayer()."', $template_row);
	$template_row = preg_replace('/\[d (.+)\](.+)\[\/d\]/i', '".el_showLink($1, $2, $row_catalog)."', $template_row);
	$template_row = preg_replace('/\[comments (.+)\]/i', '".el_insertComments($1, $row_catalog)."', $template_row);
	$template_row = preg_replace('/\[img (.+)\]/iU', '".el_showImg($1, $row_catalog)."', $template_row);


	preg_match_all("/\[file\](.*)\[\/file\]/i", $template_row, $link_name);
	preg_match_all("/\[filen\](.*)\[\/filen\]/i", $template_row, $link_name1);
	if (count($fnames) > 1) {

		if (substr_count($template_row, '[file]') > 0) {
			$link_string = "";
			for ($i = 0; $i < count($fnames); $i++) {
				$c = $i + 1;
				if (is_file($_SERVER['DOCUMENT_ROOT'] . '/files/' . $fnames[$i])) {
					($i < count($fnames) - 1) ? $end = '&nbsp; | &nbsp;' : $end = '';
					$link_string .= '<a target=_blank href=/files/' . $fnames[$i] . '>' . $link_name[1][0] . ' №' . $c . '</a>' . $end;
				}
			}
			$template_row = str_replace("[file]", $link_string, $template_row);
			$template_row = str_replace($link_name[1][0] . "[/file]", "", $template_row);
			$link_string = "";
		}

		if (substr_count($template_row, '[filen]') > 0) {
			$link_string = "";
			for ($i = 0; $i < count($fnames); $i++) {
				$c = $i + 1;
				($i < count($fnames) - 1) ? $end = '&nbsp; | &nbsp;' : $end = '';
				$link_string .= '<a target=_blank href=/files/' . $fnames[$i] . '>' . $link_name1[1][0] . ' &laquo;' . $fnames[$i] . '&raquo;</a>' . $end;
			}
			$template_row = str_replace("[filen]", $link_string, $template_row);
			$template_row = str_replace($link_name1[1][0] . "[/filen]", "", $template_row);
			$link_string = "";
		}
	} else {
		if (is_file($_SERVER['DOCUMENT_ROOT'] . '/files/' . $files)) {
			$template_row = str_replace("[file]", '<a target=_blank href=/files/$row_catalog[filename]>', $template_row);
			$template_row = str_replace("[/file]", "</a>", $template_row);
			$template_row = str_replace("[filen]", '<a target=_blank href=/files/$row_catalog[filename]>', $template_row);
			$template_row = str_replace("[/filen]", ' &laquo;$row_catalog[filename]&raquo;</a>', $template_row);
		} else {
			$template_row = str_replace("[file]", '', $template_row);
			$template_row = str_replace("[/file]", '', $template_row);
			$template_row = str_replace("[filen]", '', $template_row);
			$template_row = str_replace("[/filen]", '', $template_row);
			$template_row = str_replace($link_name[1][0], '&nbsp;', $template_row);
			$template_row = str_replace($link_name1[1][0], '&nbsp;', $template_row);
		}
	}
	$template_row = $template_row . '";';
	return $template_row;
}

function el_showLink($field, $text, $row_catalog)
{
	return (strlen($row_catalog[$field]) > 0) ? "<a href='$row_catalog[$field]'>" . $text . '</a>' : '';
}

function el_showImg($field, $row_catalog)
{
	return $out = (is_file($_SERVER['DOCUMENT_ROOT'] . $row_catalog[$field])) ? "$row_catalog[$field]" : "/images/video_empty.jpg";
}


function el_insertComments($field, $row_catalog)
{
	$root = $_SERVER['DOCUMENT_ROOT'];
	if (strlen($row_catalog[$field]) > 0) {
		ob_start();
		include($root . '/modules/comments.php');
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}
}

function el_insertPlayer()
{
	global $_GET, $catalog_id;
	$id = intval($_GET['id']);
	return $str = "<div id='flashbanner'>Пожалуйста, установите Flash-плеер.</div><script type=\"text/javascript\">var so = new SWFObject('/player.swf','mpl','500','450','9');so.addParam('allowfullscreen','true');so.addParam('logo','');so.addVariable('skin', 'http://mos-gorsud.ru/stylish_slim.swf');so.addParam('flashvars','file=/playlist.xml.php?id=$id|$catalog_id&autostart=true');so.addParam('stretching', 'fill');so.write('flashbanner');</script>";
}


function el_genWord()
{
	global $_POST;
	//error_reporting(E_ALL);
	$root = $_SERVER['DOCUMENT_ROOT'];
	$court = intval($_POST['court']);
	$summ = intval($_POST['summ']);
	$gp = intval($_POST['gp']);
	$name = strip_tags(trim($_POST['name']));
	$adress = strip_tags(trim($_POST['adress']));
	$sid = $_POST['sid'];
	ob_start();
	include($root . '/print_check.php');
	$contents = ob_get_contents();
	ob_end_clean();
	$rand = el_genpass();
	el_delDir($_SERVER['DOCUMENT_ROOT'] . '/files/downloads/');
	mkdir($_SERVER['DOCUMENT_ROOT'] . '/files/downloads/', 0777);
	$newDir = $_SERVER['DOCUMENT_ROOT'] . '/files/downloads/' . $rand;
	mkdir($newDir, 0777);
	$fp = fopen($newDir . '/gosposhlina.doc', 'w');
	fwrite($fp, $contents);
	fclose($fp);
	return '/files/downloads/' . $rand . '/gosposhlina.doc';
}


function cyr_strtolower($a)
{
	$offset = 32;
	$m = array();
	for ($i = 192; $i < 224; $i++) {
		$m[chr($i)] = chr($i + $offset);
	}
	return strtr($a, $m);
}

function who_online()
{
	@session_start();
	$id_session = session_id();
	$ses = el_dbselect("SELECT * FROM session_online WHERE id_session = '$id_session'", 0, $ses);
	if (mysql_num_rows($ses) > 0) {
		$queryNew = "UPDATE session_online SET putdate = NOW(), user = '$_SESSION[user]' WHERE id_session = '$id_session'";
	} else {
		$queryNew = "INSERT INTO session_online VALUES('$id_session', NOW(), '$_SESSION[user]')";
	}
	el_dbselect($queryNew, 0, $res);
	el_dbselect("DELETE FROM session_online WHERE putdate < NOW() -  INTERVAL '20' MINUTE", 0, $res);
	$num = el_dbselect("SELECT id_session FROM session_online", 0, $num);
	return mysql_num_rows($num);
}


function el_wordEnd($number, $gender)
{
	if ($number > 20) {
		$number = substr($number, strlen($number) - 1, 1);
	}
	if ($number == 1) {
		($gender == 'm') ? $out = '' : $out = 'ка';
	} elseif ($number > 1 && $number < 5) {
		($gender == 'm') ? $out = 'а' : $out = 'ки';
	} elseif ($number >= 5) {
		($gender == 'm') ? $out = 'ов' : $out = 'ек';
	} elseif ($number == 0) {
		($gender == 'm') ? $out = 'ов' : $out = 'ек';
	}
	return $out;
}

function el_add2cart()
{
	global $cat, $row_dbcontent, $_POST, $_SESSION;
	if (strlen($_SESSION['catalog_id']) > 0) {
		$catalog_id = $_SESSION['catalog_id'];
	} else {
		$catalog_id = str_replace("catalog", "", $row_dbcontent['kod']);
	}
	if (isset($_POST['goodid'])) {
		$pq = ($_SESSION['ulevel'] == 4) ? "name='Цена2'" : "type='price'";
		$t = el_dbselect("SELECT field FROM catalog_prop WHERE catalog_id='$catalog_id' AND title=1", 0, $t, 'row');
		$f = el_dbselect("SELECT field FROM catalog_prop WHERE catalog_id='$catalog_id' AND type='small_image'", 0, $f, 'row');
		$p = el_dbselect("SELECT field FROM catalog_prop WHERE catalog_id='$catalog_id' AND $pq", 0, $p, 'row');
		$s = el_dbselect("SELECT cat, goodid, field" . $t['field'] . ", field" . $f['field'] . ", field" . $p['field'] . " FROM catalog_" . $catalog_id . "_data WHERE id='" . $_POST['goodid'] . "'", 0, $s, 'row');
		$c = el_dbselect("SELECT path FROM content WHERE cat=" . $s['cat'], 0, $s, 'row');
		if (!isset($_SESSION['good_id'])) {
			session_register('good_id');
			session_register('goodid');
			session_register('good_price');
			session_register('good_count');
			session_register('good_summ');
			session_register('good_image');
			session_register('good_name');
			session_register('catalogid');
			session_register('catalog_path');
			$_SESSION['good_id'] = array();
			$_SESSION['goodid'] = array();
			$_SESSION['good_price'] = array();
			$_SESSION['good_summ'] = array();
			$_SESSION['good_image'] = array();
			$_SESSION['good_count'] = array();
			$_SESSION['good_name'] = array();
			$_SESSION['catalogid'] = array();
			$_SESSION['catalog_path'] = array();
		}
		if (!in_array($_POST['goodid'], $_SESSION['good_id'])) {
			$_SESSION['good_id'][] = $_POST['goodid'];
			$_SESSION['goodid'][] = $s['goodid'];
			$_SESSION['good_summ'][] = $_SESSION['good_price'][] = $s["field" . $p['field']];
			$_SESSION['good_name'][] = $s["field" . $t['field']];
			$_SESSION['good_image'][] = $s["field" . $f['field']];
			$_SESSION['good_count'][] = 1;
			$_SESSION['catalogid'][] = $catalog_id;
			$_SESSION['catalog_path'][] = $c['path'];
		} else {
			$pos = array_search($_POST['goodid'], $_SESSION['good_id']);
			$_SESSION['good_summ'][$pos] = $_SESSION['good_summ'][$pos] + $s["field" . $p['field']];
			$_SESSION['good_count'][$pos] = $_SESSION['good_count'][$pos] + 1;
		}
		echo '<script language=javascript>showDialog("\"' . htmlspecialchars($s["field" . $t['field']]) . '\" добавлен в корзину!<br>Теперь в корзине ' . array_sum($_SESSION['good_count']) . ' товар' . el_wordEnd(array_sum($_SESSION['good_count']), 'm') . ' на сумму $' . array_sum($_SESSION['good_summ']) . ' .", "alert")</script>';

	}
	//Полная очистка карзины
	if (isset($_POST['action']) && $_POST['action'] == 'del_all') {
		session_unregister('good_id');
		session_unregister('goodid');
		session_unregister('good_price');
		session_unregister('good_summ');
		session_unregister('good_image');
		session_unregister('good_count');
		session_unregister('good_name');
		session_unregister('catalogid');
		session_unregister('catalog_path');
	}

	//Удаление одной позиции
	if (isset($_POST['good_del'])) {
		array_splice($_SESSION['good_id'], $_POST['good_del'], 1);
		array_splice($_SESSION['goodid'], $_POST['good_del'], 1);
		array_splice($_SESSION['good_price'], $_POST['good_del'], 1);
		array_splice($_SESSION['good_summ'], $_POST['good_del'], 1);
		array_splice($_SESSION['good_name'], $_POST['good_del'], 1);
		array_splice($_SESSION['good_count'], $_POST['good_del'], 1);
		array_splice($_SESSION['catalogid'], $_POST['good_del'], 1);
		array_splice($_SESSION['catalog_path'], $_POST['good_del'], 1);
		array_splice($_SESSION['good_image'], $_POST['good_del'], 1);
	}

	//Пересчет карзины
	if (isset($_POST['action']) && $_POST['action'] == 'recalc') {
		for ($i = 0; $i < count($_SESSION['good_count']); $i++) {
			$reCount = (intval($_POST['count' . $i]) <= 0) ? 1 : intval($_POST['count' . $i]);
			$_SESSION['good_count'][$i] = $reCount;
		}
	}
	el_show_cart();
}

function el_show_cart()
{
	global $_SESSION;
	if (is_array($_SESSION['good_count'])) {
		$c = array_sum($_SESSION['good_count']);
	}
	if ($c > 0 && isset($_SESSION['good_summ'])) {
		echo '<div style="padding:10px"><b>В корзине ' . $c . ' товар' . el_wordEnd($c, 'm') . ' на сумму $' . array_sum($_SESSION['good_summ']) . ' </b><br><br>
		<a href="/katalog/order/" id="addcart"><b>Оформить заказ &raquo;</b></a><?div>';
	}
}

function el_convertArray($array, $charsetFrom = 'UTF-8', $charsetTo = 'Windows-1251')
{
	while (list($key, $val) = each($array)) {
		if (is_array($array[$key])) {
			$array[$key] = el_convertArray($array[$key]);
		} else {
			$array[$key] = iconv($charsetFrom, $charsetTo, $val);
		}
	}
	reset($array);
	return $array;
}


//Логирование действий в Административном разделе
function el_log($text, $level = 3)
{
	global $database_dbconn, $dbconn, $session, $log;
	switch ($level) {
		case 1:
			$priority = 'Высокий';
			$color = 'red';
			break;
		case 2:
			$priority = 'Средний';
			$color = 'yellow';
			break;
		case 3:
			$priority = 'Низкий';
			$color = 'blue';
			break;
	}
	$log->logg('Новая запись', $text, $priority, $color);
}

function el_clearPhone($number)
{
	$disStr = array('(', ')', '-', ' ', '+');
	$disRep = array('', '', '', '', '');
	return str_replace($disStr, $disRep, $number);
}

function send_sms($str, $id, $phoneList, $mode = '', $count = 0)
{
	if ($mode == 'translit') $str = el_translit($str);
	$phoneList = el_clearPhone($phoneList);
	$data = "Speed=1&Http_username=elman&Http_password=1675894&Message=" . urlencode(str_replace('_', ' ', $str)) . "&Phone_list=" . $phoneList . "&Speed=1&Http_id=" . $id . "&Http_id=" . $id . "\r\n\r\n";
	$hostname = "www.websms.ru";
	$path = "/http_in5.asp";
	$line = "";

	// Устанавливаем соединение, имя которого
	// передано в параметре $hostname
	$fp = @fsockopen($hostname, 80, $errno, $errstr, 30);
	// Проверяем успешность установки соединения
	if (!$fp) {
		//echo "!!!$errstr ($errno)<br />\n";
		if ($count < 5) {
			send_sms($str, $id, $phoneList, ++$count);
		} else {
			return false;
		}
	} else {
		// Формируем HTTP-заголовки для передачи
		// его серверу
		// Подделываем пользовательский агент, маскируясь
		// под пользователя WindowsXP
		$headers = "POST $path HTTP/1.0\r\n";
		$headers .= "Host: $hostname\r\n";
		$headers .= "Content-type: application/x-www-form-urlencoded\r\n";
		$headers .= "Content-Length: " . strlen($data) . "\r\n\r\n";
		//$headers .= "User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1\r\n";
		$headers .= "Connection: Close\r\n\r\n";
		// Отправляем HTTP-запрос серверу
		fwrite($fp, $headers . $data);
		// Получаем ответ
		while (!feof($fp)) {
			$line .= fgets($fp, 1024);
		}
		fclose($fp);
		return $line;
	}
}


function el_get_status($id, $count = 0)
{
	$data = "speed=1&Http_username=elman&Http_id=" . $id . "&Http_password=1675894&Speed=1&Http_id=" . $id . "&Http_id=" . $id . "\r\n\r\n";
	$hostname = "www.websms.ru";
	$path = "/http_out3.asp";
	$line = "";

	// Устанавливаем соединение, имя которого
	// передано в параметре $hostname
	$fp = @fsockopen($hostname, 80, $errno, $errstr, 30);
	// Проверяем успешность установки соединения
	if (!$fp) {
		//echo "!!!$errstr ($errno)<br />\n";
		if ($count < 5) {
			el_get_status($id, ++$count);
		} else {
			return false;
		}
	} else {
		// Формируем HTTP-заголовки для передачи
		// его серверу
		// Подделываем пользовательский агент, маскируясь
		// под пользователя WindowsXP
		$headers = "POST $path HTTP/1.1\r\n";
		$headers .= "Host: $hostname\r\n";
		$headers .= "Content-type: application/x-www-form-urlencoded\r\n";
		$headers .= "Content-Length: " . strlen($data) . "\r\n\r\n";
		//$headers .= "User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1\r\n";
		$headers .= "Connection: Close\r\n\r\n";
		// Отправляем HTTP-запрос серверу
		fwrite($fp, $headers . $data);
		// Получаем ответ
		while (!feof($fp)) {
			$line .= fgets($fp, 1024);
		}
		fclose($fp);
	}
	return $line;
}

function el_genSiteMapOLD()
{
	$q = "SELECT name, path, last_time FROM cat WHERE menu='Y'";
	$db = el_dbselect($q, 0, $db, 'result', true);
	$r_cat = mysql_fetch_assoc($db);
	$xml = '<?xml version="1.0" encoding="UTF-8"?>
	<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	';
	$yml_cat = '<categories>' . "\n";
	$c = 0;
	$cat_arr = array();
	do {
		$c++;
		$xml .= '
		<url>
		  <loc>http://' . $_SERVER['SERVER_NAME'] . $r_cat['path'] . '/</loc>
		  <lastmod>' . date('Y-m-d') ./*, $db->sf("mdate")).*/
			'</lastmod>
		  <changefreq>monthly</changefreq>
	   </url>
	';
		$cat_arr[$cat_id] = $c;
		//$yml_cat.='<category id="'.$c.'" parentId="0">'.$db->sf("category_name").'</category>
		//';
	} while ($r_cat = mysql_fetch_assoc($db));
	$yml_cat .= '</categories>' . "\n";
	$yml_prod = $xml_url = array();
	$yml_prod[0] = '<offers>' . "\n";
	$i = 0;
	$p = el_dbselect("SELECT path FROM content WHERE kod='catalogpub'", 0, $p, 'row');
	$q = "SELECT * FROM catalog_realauto_data WHERE active=1";
	$db_cat = el_dbselect($q, 0, $db_cat);
	if (@mysql_num_rows($db_cat) > 0) {
		$sf = mysql_fetch_assoc($db_cat);
		do {
			$i++;
			//$prod_id=$db->sf("product_id");
			//$cat_id=$db->sf("category_id");
			$prod_url = 'http://' . $_SERVER['SERVER_NAME'] . $p['path'] . '/auto/' . $sf['path'] . '.html';
			$xml_url[] = '
			<url>
			  <loc>' . $prod_url . '</loc>
			  <lastmod>' . date('Y-m-d')/*, $db->sf("mdate"))*/ . '</lastmod>
			  <changefreq>monthly</changefreq>
		   </url>';

			/*$yml_prod[]='<offer id="'.$prod_id.'" available="true">
			  <url>'.$prod_url.'&amp;from=ya</url>
			  <price>'.$db->sf("product_price").'</price>
			  <currencyId>RUR</currencyId>
			  <categoryId>'.$cat_arr[$cat_id].'</categoryId>
			  <picture>http://orly.ru/php/bin/shop_image/product/'.$db->sf("product_thumb_image").'</picture>
			  <delivery> true </delivery>
			  <local_delivery_cost>200</local_delivery_cost>
			  <name>'.htmlspecialchars(strip_tags(str_replace('<br>', '. ', $db->sf("product_name")))).'</name>
			  <vendorCode> '.$prod_id.' </vendorCode>
			  <description>'.htmlspecialchars(strip_tags($db->sf("product_desc"))).'</description>
			  <country_of_origin>США</country_of_origin>
			</offer>';*/
		} while ($sf = mysql_fetch_assoc($db_cat));
		/*$yml_prod[]='</offers>'."\n";*/
		$xml .= implode("\n", $xml_url) . '</urlset>';
	}

	$fp = fopen($_SERVER['DOCUMENT_ROOT'] . '/sitemap.xml', 'w');
	fwrite($fp, $xml);
	fclose($fp);
}

function el_xmlSimbols($str){
	return str_replace(array('&', "'", '"', '>', '<'), array('&amp;', '&apos;', '&quot;', '&gt;', '&lt;'), $str);
}

function el_genSiteMap()
{
	$q = "SELECT name, path, last_time FROM cat WHERE menu='Y'";
	$db = el_dbselect($q, 0, $db);
	$r_cat = el_dbfetch($db);
	$xml = '<?xml version="1.0" encoding="UTF-8"?>
	<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	';
	$yml_cat = '<categories>' . "\n";
	$c = 0;
	$cat_arr = array();
	do {
		$c++;
		$xml .= '<url>
              <loc>https://' . el_xmlSimbols($_SERVER['SERVER_NAME'] . $r_cat['path']) . '/</loc>
              <lastmod>' . date('Y-m-d') ./*, $db->sf("mdate")).*/
			'</lastmod>
              <changefreq>monthly</changefreq>
           </url>
		';
	} while ($r_cat = el_dbfetch($db));
	$yml_cat .= '</categories>' . "\n";
	$yml_prod = $xml_url = array();
	$yml_prod[0] = '<offers>' . "\n";
	$i = 0;

	//Справочник путей разделов каталога товаров
	$c = el_dbselect("SELECT cat.id AS id, cat.path AS path, catalog_parts_data.cat AS cat FROM catalog_parts_data, cat 
WHERE cat.id = catalog_parts_data.cat GROUP BY catalog_parts_data.cat", 0, $c, 'result', true);
	$rc = el_dbfetch($c);
	$catsPath = array();
	do{
		$catsPath[intval($rc['id'])] = $rc['path'];
	}while($rc = el_dbfetch($c));

	$q = "SELECT * FROM catalog_parts_data WHERE active=1";
	$db_cat = el_dbselect($q, 0, $db_cat);
	if (@el_dbnumrows($db_cat) > 0) {
		$sf = el_dbfetch($db_cat);
		do {
			$i++;
			//$prod_id=$db->sf("product_id");
			//$cat_id=$db->sf("category_id");
			if(intval($sf['cat']) > 0 && strlen($catsPath[intval($sf['cat'])]) > 0) {
				$prod_url = el_xmlSimbols('https://' . $_SERVER['SERVER_NAME'] . $catsPath[intval($sf['cat'])] .'/?id='. $sf['id']);
				$xml_url[] = '<url>
                  <loc>' . $prod_url . '</loc>
                  <lastmod>' . date('Y-m-d')/*, $db->sf("mdate"))*/ . '</lastmod>
                  <changefreq>monthly</changefreq>
               </url>';
			}

		} while ($sf = el_dbfetch($db_cat));
		/*$yml_prod[]='</offers>'."\n";*/
		$xml .= implode("\n", $xml_url) . "\n</urlset>";
	}

	$fp = fopen($_SERVER['DOCUMENT_ROOT'] . '/sitemap.xml', 'w');
	fwrite($fp, $xml);
	fclose($fp);
}

function el_mail($html_body, $title, $from, $reciepient, $images = array(), $files = array())
{
	require_once($_SERVER['DOCUMENT_ROOT'] . '/modules/htmlMimeMail.php');
	$mail = new htmlMimeMail();
	if ($images) {
		for ($i = 0; $i < count($images); $i++) {
			$image = $images[$i];
			$im = $mail->getFile($image['path']);
			$mail->addHtmlImage($im, $name = $image['name']);
		}
	}
	if ($files) {
		for ($i = 0; $i < count($files); $i++) {
			$file = $files[$i];
			$f = $mail->getFile($file['path']);
			$mail->addAttachment($f, $file['name'], 'application/zip');
		}
	}
	$mail->setHtml($html_body);
	$mail->setReturnPath('info@' . str_replace('www.', '', $_SERVER['SERVER_NAME']));
	$mail->setFrom($from);
	$mail->setBcc('flobus@mail.ru');//,romanluns@yandex.ru
	$mail->setSubject('=?UTF-8?B?' . base64_encode($title) . '?=');
	$mail->setHeader('X-Mailer', 'HTML Mime mail');
	$mail->setHTMLCharset('utf-8');
	$result = $mail->send(array($reciepient));
	return $result;
}

function el_uploadUnique($fileName, $targetDir)
{
	global $_FILES;
	$fileExtArr = explode('.', $_FILES[$fileName]['name']);
	$fileExt = array_pop($fileExtArr);
	$fileNameOrig = implode('.', $fileExtArr);
	$newfilename = md5(el_genpass(8)) . '.' . $fileExt;
	if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/' . $targetDir)) mkdir($_SERVER['DOCUMENT_ROOT'] . '/' . $targetDir, 0777);
	$targetPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $targetDir . '/';
	if (file_exists($targetPath . $newfilename)) {
		el_uploadUnique($fileName, $targetDir);
	}
	if (!move_uploaded_file($_FILES[$fileName]['tmp_name'], $targetPath . $newfilename)) {
		if (!copy($_FILES[$fileName]['tmp_name'], $targetPath . $newfilename)) {
			echo "<script>alert('Не удалось закачать файл " . $_FILES[$fileName]['name'] . "!')</script>";
			return false;
		}
	}
	return '/' . $targetDir . '/' . $newfilename;
}

function parseExcelToTable($filePath)
{
	require $_SERVER['DOCUMENT_ROOT'] . '/modules/vendor/autoload.php';

	$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

	$worksheet = $spreadsheet->getActiveSheet();
	$highestRow = $worksheet->getHighestRow();
	$highestColumn = $worksheet->getHighestColumn();

	$output = '<table class="auto_tbl">' . "\n";
	for ($row = 1; $row <= $highestRow - 1; ++$row) {
		if ($worksheet->getCell('A' . $row) != '') {
			$output .= (($row == 1) ? '<thead>' : (($row == 2) ? '<tbody>' : '')) . '<tr id="tr' . $row . '" data-value="' . $highestRow . '">' . PHP_EOL;
			for ($col = 'A'; $col != 'E'/*$highestColumn*/; ++$col) {
				$cellValue = $worksheet->getCell($col . $row)->getValue();
				if ($col == 'E' && $row > 1) {
					$cellValue = number_format($cellValue, 0, ', ', " ") . iconv('Windows-1251', 'UTF-8', ' руб.');
				}
				$output .= (($row == 1) ? '<th>' : '<td>') .
					$cellValue .
					(($row == 1) ? '</th>' : '</td>') . PHP_EOL;
			}
			$output .= '</tr>' . (($row == 1) ? '</thead>' : '') . PHP_EOL;
		} else {
			$output .= '</tbody>' . PHP_EOL;
			break;
		}
	}
	return $output .= '</table>' . PHP_EOL;
}

function prepareValue($val)
{
	return iconv('UTF-8', 'Windows-1251', addslashes($val));
}

function parseExcelToDB($cat, $filePath)
{
	require $_SERVER['DOCUMENT_ROOT'] . '/modules/vendor/autoload.php';

	$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_SERVER['DOCUMENT_ROOT'] . $filePath);

	$worksheet = $spreadsheet->getActiveSheet();
	$highestRow = $worksheet->getHighestRow();
	$highestColumn = $worksheet->getHighestColumn();

	$insertRows = array();
	for ($row = 2; $row <= $highestRow; $row++) {
		if ($worksheet->getCell('A' . $row) != '') {
			$insertRows[] = "($cat, 1, 
			'" . prepareValue($worksheet->getCell('A' . $row)->getValue()) . "',
			'" . prepareValue($worksheet->getCell('B' . $row)->getValue()) . "',
			'" . prepareValue($worksheet->getCell('C' . $row)->getValue()) . "',
			'" . prepareValue($worksheet->getCell('D' . $row)->getValue()) . "',
			'" . prepareValue($worksheet->getCell('E' . $row)->getValue()) . "')";
		} else {
			break;
		}
	}
	if (count($insertRows) > 0) {
		$res = el_dbselect("TRUNCATE TABLE catalog_parts_data", 0, $res, 'result', true);
		$ins = el_dbselect("INSERT INTO catalog_parts_data (cat, active, field1, field2, field3, field4, field5)
		VALUES " . implode(', ', $insertRows), 0, $ins, 'result', true);
		if ($ins != false) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function el_getRemoteImg($url){
	$imgClass = '';
	if(strlen($url) > 0) {
		$imgPathArr = explode('/', $url);
		$imgName = end($imgPathArr);
		$img = '/images/parts/'.$imgName;
		if(!is_file($_SERVER['DOCUMENT_ROOT'].$img)){
			if(!copy($url, $_SERVER['DOCUMENT_ROOT'].$img)){
				$img = '/images/components/car-parts.svg';
			}
		}

	}else{
		$img = '/images/components/car-parts.svg';
		$imgClass = ' class="partNoImg"';
	}
	return array($img, $imgClass);
}

?>
