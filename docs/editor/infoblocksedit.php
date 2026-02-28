<?php
require_once('../Connections/dbconn.php');

if (!isset($_GET['new'])) {
	$colname_content = "1";
	if (isset($_GET['id'])) {
		$colname_content = (get_magic_quotes_gpc()) ? $_GET['id'] : addslashes($_GET['id']);
	}
	mysql_select_db($database_dbconn, $dbconn);
	$query_access = sprintf("SELECT edit FROM infoblocks WHERE id = %s", $colname_content);
	$access = mysql_query($query_access, $dbconn) or die(mysql_error());
	$row_access = mysql_fetch_assoc($access);
	if (strlen($row_access['edit']) > 0) {
		$accs = explode(",", $row_access['edit']);
	} else {
		$accs = array(1);
	}

	$requiredUserLevel = $accs;
	include($_SERVER['DOCUMENT_ROOT'] . "/editor/secure/secure.php");
	(isset($submit)) ? $work_mode = "write" : $work_mode = "read";
	el_reg_work($work_mode, $login, $_GET['id']) ?>
	<?php
	$editFormAction = $_SERVER['PHP_SELF'];
	if (isset($_SERVER['QUERY_STRING'])) {
		$editFormAction .= "?" . $_SERVER['QUERY_STRING'] . "&last_action=write";
	}

	if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "Add")) {
		$updateSQL = sprintf("UPDATE infoblocks SET name=%s, text=%s WHERE id=%s",
			GetSQLValueString($_POST['name'], "text"),
			GetSQLValueString($_POST['FCKeditor1'], "text"),
			GetSQLValueString($_POST['id'], "int"));

		mysql_select_db($database_dbconn, $dbconn);
		$Result1 = mysql_query($updateSQL, $dbconn) or die(mysql_error());
		el_clearcache($_POST['cat']);
		$saved = 1;
	}


	mysql_select_db($database_dbconn, $dbconn);
	$query_content = sprintf("SELECT * FROM infoblocks WHERE id = %s", $colname_content);
	$content = mysql_query($query_content, $dbconn) or die(mysql_error());
	$row_content = mysql_fetch_assoc($content);
} else {
	if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "Add")) {
		$updateSQL = sprintf("INSERT INTO infoblocks (name, text, ctime, author) VALUES (%s, %s, %s, %s)",
			GetSQLValueString($_POST['name'], "text"),
			GetSQLValueString($_POST['FCKeditor1'], "text"),
			GetSQLValueString(date("Y-m-d H:i:s"), "date"),
			GetSQLValueString($_SESSION['login'], "text"));

		mysql_select_db($database_dbconn, $dbconn);
		$Result1 = mysql_query($updateSQL, $dbconn) or die(mysql_error());
		el_clearcache($_POST['cat']);
		$saved = 2;

	}
}
?>

<html>
<head>
	<title>Редактор</title>
	<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
	<meta http-equiv="cache-control" content="no-cache">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<script type="text/javascript" src="/js/jquery-1.8.3.min.js"></script>
	<script type="text/javascript" src="/editor/e_modules/ckeditor2/ckeditor.js"></script>
	<script language="JavaScript" type="text/JavaScript">
		function MM_openBrWindow(theURL, winName, features, myWidth, myHeight, isCenter) { //v3.0
			if (window.screen)if (isCenter)if (isCenter == "true") {
				var myLeft = (screen.width - myWidth) / 2;
				var myTop = (screen.height - myHeight) / 2;
				features += (features != '') ? ',' : '';
				features += ',left=' + myLeft + ',top=' + myTop;
			}
			window.open(theURL, winName, features + ((features != '') ? ',' : '') + 'width=' + myWidth + ',height=' + myHeight);
		}
	</script>

	<style>
		input, select {
			FONT-FAMILY: MS Sans Serif;
			FONT-SIZE: 12px;
		}

		body, td {
			FONT-FAMILY: Tahoma;
			FONT-SIZE: 12px
		}

		a:hover {
			color: #86869B
		}

		a:visited {
			color: navy
		}

		a {
			color: navy
		}

		a:active {
			color: #ff0000
		}

		.st {
			FONT-FAMILY: MS Sans Serif;
			FONT-SIZE: 12px;
		}

		.MenuFile {
			position: absolute;
			top: 27;
		}

		body {
			margin-left: 0px;
			margin-top: 10px;
			margin-right: 0px;
			margin-bottom: 0px;
		}

		#message {
			border: 1px #C3C3C3 inset
		}
	</style>
	<link href="style.css" rel="stylesheet" type="text/css">
	<style type="text/css">
		<!--
		.style1 {
			font-size: 9pt;
			color: #FFFFFF;
		}

		-->
	</style>
	<script>
		var CKEDITOR_BASEPATH = 'editor/e_modules/ckeditor2/';
		if (CKEDITOR.env.ie && CKEDITOR.env.version < 9)
			CKEDITOR.tools.enableHtml5Elements(document);
		CKEDITOR.config.height = window.innerHeight - 300;
		CKEDITOR.config.width = '98%';


		var initEditor = (function () {
			var wysiwygareaAvailable = isWysiwygareaAvailable(),
				isBBCodeBuiltIn = !!CKEDITOR.plugins.get('bbcode');

			return function () {
				var editorElement = CKEDITOR.document.getById('FCKeditor1');

				// Depending on the wysiwygare plugin availability initialize classic or inline editor.
				if (wysiwygareaAvailable) {
					CKEDITOR.replace('FCKeditor1', {
						extraPlugins: 'imageuploader,video,html5video,widget,widgetselection,clipboard,lineutils',
						filebrowserImageBrowseUrl: '/editor/e_modules/ckeditor2/plugins/imageuploader/main.php',
						filebrowserImageUploadUrl: '/editor/e_modules/ckeditor2/plugins/imageuploader/imgupload.php',
						filebrowserUploadUrl: '/editor/e_modules/ckeditor2/plugins/imageuploader/imgupload.php',
						filebrowserBrowseUrl: '/editor/e_modules/ckeditor2/plugins/imageuploader/main.php'
					});
				} else {
					editorElement.setAttribute('contenteditable', 'true');
					CKEDITOR.inline('FCKeditor1');
				}
			};

			function isWysiwygareaAvailable() {
				// If in development mode, then the wysiwygarea must be available.
				// Split REV into two strings so builder does not replace it :D.
				if (CKEDITOR.revision == ( '%RE' + 'V%' )) {
					return true;
				}

				return !!CKEDITOR.plugins.get('wysiwygarea');
			}
		})();
	</script>
</head>

<body>
<h1>Инфоблок: "<?= $row_content['name'] ?>"</h1>
<p><input type="button" value="&laquo; К списку инфоблоков" onClick="location.href='infoblocks.php'" class="but">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input type="button" value="К списку разделов &raquo;" title="Список разделов, где работает этот инфоблок"
		   onClick="location.href='infoblockslink.php?id=<?= $_GET['id'] ?>'" class="but"></p>


<form action="<?php echo $editFormAction; ?>" method="post" name="Add" id="Add" style="padding:0; margin:0">

		Название: <input type="text" name="name" size="70" value="<?= $row_content['name'] ?>" style="margin-bottom:10px">

		<textarea name="FCKeditor1" cols="65" rows="10" id="FCKeditor1"><?= $row_content['text'] ?></textarea>

		<? /*input name="ButtonHTML" type="button" class="but"
					   onClick="MM_openBrWindow('/editor/newseditor.php?field=FCKeditor1&form=Add','editor','','590','600','true')"
					   value="Визуальный редактор"*/ ?>

		<input name="Submit" type="Submit" class="but" value=" Сохранить ">


		<input type="hidden" name="MM_update" value="Add">
		<input name="last_action" type="hidden" id="last_action" value="write">
		<input name="id" type="hidden" id="id" value="<?= $row_content['id']; ?>">
</form>

</div>
<?
if ($saved == 1) {
	echo "<script>alert('Изменения сохранены!');</script>";
} elseif ($saved == 2) {
	echo "<script>alert('Новый инфоблок создан!');</script>";
}
?>
</div>
<script>initEditor();</script>
</body>
</html>


