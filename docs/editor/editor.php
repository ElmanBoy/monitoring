<?php require_once('../Connections/dbconn.php');
$colname_content = "1";
if (isset($_GET['cat'])) {
	$colname_content = (get_magic_quotes_gpc()) ? $_GET['cat'] : addslashes($_GET['cat']);
}
$cat = intval($_GET['cat']);

$query_access = sprintf("SELECT edit FROM content WHERE cat = %s", $colname_content);
$access = el_dbselect($query_access, 0, $access, 'result', true);
$row_access = mysql_fetch_assoc($access);
if (strlen($row_access['edit']) > 0) {
	$accs = explode(",", $row_access['edit']);
} else {
	$accs = array(1);
}

$requiredUserLevel = $accs;
include("secure/secure.php");
(isset($submit)) ? $work_mode = "write" : $work_mode = "read";
el_reg_work($work_mode, $login, $_GET['cat']);
$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
	$editFormAction .= "?" . $_SERVER['QUERY_STRING'] . "&last_action=write";
}

$query_content = sprintf("SELECT * FROM content WHERE cat = %s", $colname_content);
$content = el_dbselect($query_content, 0, $res, 'result', true);
$row_content = mysql_fetch_assoc($content);

$page_name = (strlen($row_content['caption']) > 0) ? $row_content['caption'] : $row_content['title'];
$page_url = "http://" . $_SERVER['SERVER_NAME'] . $row_content['path'];

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "Add")) {
	$updateSQL = sprintf("UPDATE content SET text=%s WHERE cat=%s",
		GetSQLValueString(str_replace('В', '', str_replace('&#160;', ' ', $_POST['editor1'])), "text"),
		GetSQLValueString($_GET['cat'], "int"));
	$Result1 = el_dbselect($updateSQL, 0, $Result1);
	el_dbselect("OPTIMIZE TABLE `content`", 0, $res);
	el_clearcache($_POST['cat']);
	el_log('Редактирование раздела &laquo;' . $page_name . '&raquo;', 2);
	$saved = 1;
} else {
	el_log('Ознакомление с разделом &laquo;' . $page_name . '&raquo;');
}

$query_content = sprintf("SELECT * FROM content WHERE cat = %s", $colname_content);
$content = el_dbselect($query_content, 0, $content);
$row_content = mysql_fetch_assoc($content);
$totalRows_content = mysql_num_rows($content);

$page_name = (strlen($row_content['caption']) > 0) ? $row_content['caption'] : $row_content['title'];
$page_url = "http://" . $_SERVER['SERVER_NAME'] . $row_content['path'];

$query_modules = "SELECT * FROM modules WHERE type='" . $row_content['kod'] . "'";
$modules = el_dbselect($query_modules, 0, $modules);
$row_modules = mysql_fetch_assoc($modules);
$totalRows_modules = mysql_num_rows($modules);

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "Add")) {
	if ($row_content['kod'] == '') {
		el_2ini('cache' . $_GET['cat'], 'Y');
	} else {
		el_2ini('cache' . $_GET['cat'], 'N');
	}
}

$query_tmpl = "SELECT * FROM template";
$tmpl = el_dbselect($query_tmpl, 0, $tmpl);
$row_tmpl = mysql_fetch_assoc($tmpl);
$totalRows_tmpl = mysql_num_rows($tmpl);

if (strlen($row_content['caption']) > 0) {
	$page_name = $row_content['caption'];
} else {
	$page_name = $row_content['title'];
}
$page_url = "http://" . $_SERVER['SERVER_NAME'] . $row_content['path'];
?>

<html>
<head>
	<title>Редактор</title>
	<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
	<meta http-equiv="cache-control" content="no-cache">
	<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
	<link rel="stylesheet" type="text/css" href="/js/popupmenu/flexdropdown.css"/>
	<script type="text/javascript" src="/js/jquery-1.8.3.min.js"></script>
	<script type="text/javascript" src="/js/popupmenu/flexdropdown.js"></script>
	<script type="text/javascript" src="/editor/e_modules/ckeditor2/ckeditor.js"></script>
	<!--<script type="text/javascript" src="/editor/e_modules/ckfinder/ckfinder.js"></script>-->
	<script language="JavaScript">
		var pretext = '<div>';
		var data = '<?php // $str = preg_replace("/(\n|\r)/","",$row_content['text']);  echo $str; ?>';

		function sendtext() {
//var htmlpage=remoteImportWordHTML(document.Add.NMH.value);
			document.Add.text.value = document.Add.NMH.value;
		}//htmlpage;}

		function MM_reloadPage(init) {  //reloads the window if Nav4 resized
			if (init == true) with (navigator) {
				if ((appName == "Netscape") && (parseInt(appVersion) == 4)) {
					document.MM_pgW = innerWidth;
					document.MM_pgH = innerHeight;
					onresize = MM_reloadPage;
				}
			}
			else if (innerWidth != document.MM_pgW || innerHeight != document.MM_pgH) location.reload();
		}
		MM_reloadPage(true);

		function MM_openBrWindow(theURL, winName, features, myWidth, myHeight, isCenter) { //v3.0
			if (window.screen)if (isCenter)if (isCenter == "true") {
				var myLeft = (screen.width - myWidth) / 2;
				var myTop = (screen.height - myHeight) / 2;
				features += (features != '') ? ',' : '';
				features += ',left=' + myLeft + ',top=' + myTop;
			}
			window.open(theURL, winName, features + ((features != '') ? ',' : '') + 'width=' + myWidth + ',height=' + myHeight);
		}

		function MM_preloadImages() { //v3.0
			var d = document;
			if (d.images) {
				if (!d.MM_p) d.MM_p = new Array();
				var i, j = d.MM_p.length, a = MM_preloadImages.arguments;
				for (i = 0; i < a.length; i++)
					if (a[i].indexOf("#") != 0) {
						d.MM_p[j] = new Image;
						d.MM_p[j++].src = a[i];
					}
			}
		}

		function MM_goToURL() { //v3.0
			var i, args = MM_goToURL.arguments;
			document.MM_returnValue = false;
			for (i = 0; i < (args.length - 1); i += 2) eval(args[i] + ".location='" + args[i + 1] + "'");
		}
		function docframe() {
			if (top == self) {
				var parent_url = "index.html";
				var orphan_url = self.location.href;
				var reframe_url = parent_url + "?" + orphan_url
				location.href = reframe_url
			}
//top.location="index.html"
		}

		function showiframe() {
			w = document.body.clientWidth - 50;
			h =<? if (strlen($row_content['kod']) > 0) {
				echo "150;";
			} else {
				echo "document.body.clientHeight-200;";
			}?>
				document.getElementById("message").height = h;
			document.getElementById("message").width = w;
			document.getElementById("NMH").styleHeight = h;
			document.getElementById("NMH").styleWidth = w;
		}

		function opcloseEdit(id) {
			if (document.getElementById(id).style.display == "none") {
				document.cookie = "idshow[" + id + "]=Y; expires=Thu, 31 Dec 2120 23:59:59 GMT; path=/editor/;";
				document.getElementById(id).style.display = "block";
			} else {
				document.cookie = "idshow[" + id + "]=N; expires=Thu, 31 Dec 2120 23:59:59 GMT; path=/editor/;";
				document.getElementById(id).style.display = "none";
			}
			;
		}

		function opcloseFilter(id) {
			if (document.getElementById(id).style.display == "none") {
				document.cookie = "idshow[" + id + "]=Y; expires=Thu, 31 Dec 2120 23:59:59 GMT; path=/editor/;";
				document.getElementById(id).style.display = "block";
			} else {
				document.cookie = "idshow[" + id + "]=N; expires=Thu, 31 Dec 2120 23:59:59 GMT; path=/editor/;";
				document.getElementById(id).style.display = "none";
			}
		}

		function scroll_area() {
			window.scrollBy(0, window.innerHeight ? window.innerHeight : document.body.clientHeight);
		}

		function ctr_save() {
			if (event.ctrlKey && event.keyCode == 83) {
				SaveHTML();
				sendtext();
				document.Add.submit();
			}
		}

		var CKEDITOR_BASEPATH = 'editor/e_modules/ckeditor2/';
		if (CKEDITOR.env.ie && CKEDITOR.env.version < 9)
			CKEDITOR.tools.enableHtml5Elements(document);
		CKEDITOR.config.height = window.innerHeight - <?=($row_content['kod'] != '') ? '300' : '220';?>;
		CKEDITOR.config.width = 'auto';


		var initEditor = (function () {
			var wysiwygareaAvailable = isWysiwygareaAvailable(),
				isBBCodeBuiltIn = !!CKEDITOR.plugins.get('bbcode');

			return function () {
				var editorElement = CKEDITOR.document.getById('editor1');

				// Depending on the wysiwygare plugin availability initialize classic or inline editor.
				if (wysiwygareaAvailable) {
					CKEDITOR.replace('editor1', {
						extraPlugins: 'imageuploader,video,html5video,widget,widgetselection,clipboard,lineutils',
						filebrowserImageBrowseUrl: '/editor/e_modules/ckeditor2/plugins/imageuploader/main.php',
						filebrowserImageUploadUrl: '/editor/e_modules/ckeditor2/plugins/imageuploader/imgupload.php',
						filebrowserUploadUrl: '/editor/e_modules/ckeditor2/plugins/imageuploader/imgupload.php',
						filebrowserBrowseUrl: '/editor/e_modules/ckeditor2/plugins/imageuploader/main.php'
					});
				} else {
					editorElement.setAttribute('contenteditable', 'true');
					CKEDITOR.inline('editor1');
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
		<? /*
var editor;
window.onload = function()
	{
		editor = CKEDITOR.replace( 'editor1' , {
			toolbar : 
			[
				['Source','-','Save','NewPage','Preview','-','Templates'],
				['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print', 'SpellChecker', 'Scayt'],
				['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
				['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'],
				'/',
				['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
				['NumberedList','BulletedList','-','Outdent','Indent','Blockquote','CreateDiv'],
				['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
				['Link','Unlink','Anchor'],
				['Image','Gallery','Flash','Video','Table','HorizontalRule','Smiley','SpecialChar','PageBreak'],
				'/',
				['Styles','Format','Font','FontSize'],
				['TextColor','BGColor'],
				['Maximize', 'ShowBlocks']
			], 
			skin: 'office2003', 
			height: '<?=($row_content['kod']!='')?'79%':'81%'?>' 
		});
		
		CKFinder.SetupCKEditor( editor, { BasePath : '/editor/e_modules/veditor/ckfinder/', RememberLastFolder : true, enable:true } );
		
		var ckePath='/editor/e_modules/veditor/';
		editor.on( 'pluginsLoaded', function( ev ){
					if ( !CKEDITOR.dialog.exists( 'galleryImage' ) )
						CKEDITOR.dialog.add( 'galleryImage', ckePath+'ckeditor/plugins/galleryImage/dialogs/galleryImage.js' );
					editor.addCommand( 'myDialogCmd', new CKEDITOR.dialogCommand( 'galleryImage' ) );
					editor.ui.addButton( 'Gallery',	{label : 'Вставить изображение в галерею', command : 'myDialogCmd'} );
					if ( !CKEDITOR.dialog.exists( 'flashVideo' ) )
						CKEDITOR.dialog.add( 'flashVideo', ckePath+'ckeditor/plugins/flashVideo/dialogs/flashVideo.js' );
					editor.addCommand( 'myDialogCmd1', new CKEDITOR.dialogCommand( 'flashVideo' ) );
					editor.ui.addButton( 'Video',	{label : 'Flash видео-плеер', command : 'myDialogCmd1'} );
				}); 
	}*/?>
	</SCRIPT>
	<link href="style.css" rel="stylesheet" type="text/css">
	<style type="text/css">
		<!--
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

		.style1 {
			font-size: 9pt;
			color: #FFFFFF;
		}

		.dropdown {
			font-weight: bold;
			cursor: pointer;
			color: #39F
		}

		-->
	</style>
</head>

<body>
<input name="metaform" type="button" class="but" id="metaform"
	   title="Заголовок, Описание, Ключевые слова, настройка доступа"
	   onClick="MM_openBrWindow('metainfo.php?id=<?php echo $row_content['cat']; ?>','metainfo','scrollbars=yes,resizable=yes','650','500','true')"
	   value="Свойства раздела">&nbsp;&nbsp;&nbsp;&nbsp;<span data-flexmenu="popupmenutop" class="dropdown">Быстрый переход</span><? el_getMenuCache('popupmenutop', 'flexdropdownmenu') ?>
&nbsp;&nbsp;&nbsp;&nbsp;<b>Путь к разделу: <?= el_broadcramble_editor($_GET['cat']) ?></b><br><br>
<center><? if (strlen($row_content['kod']) > 0){ ?>
	<table width="100%" border="0" cellpadding="3" cellspacing="0" class="el_tbl"
		   title="Редактирование вводного текста раздела, задание типа и шаблона страницы">
		<tr>
			<td align="right" valign="middle" style="cursor:pointer; color:#003399; font-weight:bold"
				onClick="opcloseEdit('editor')"><img src="/editor/img/up.gif" width="7" height="7" align="left">
				<div id="editor_button" style="width:98%; text-align:left">Вводный текст</div>
			</td>
		</tr>
	</table>
	<div onKeyDown="ctr_save()" id="editor" style="display:<? if ($_COOKIE['idshow']['editor'] != "Y") {
		echo "none";
	} else {
		echo "block";
	}; ?>; border:2px solid #CCDCE6;"><? } ?>
		<form action="<?php echo $editFormAction; ?>" method="post" name="Add" id="Add" style="padding:0; margin:0">
			<textarea name="editor1"
					  id="editor1"><?= $row_content['text']/*str_replace('В', '', str_replace('&#160;', ' ', $row_content['text']))*/ ?></textarea>
			<table border="0" width="100%">
				<tr>
					<td align="left">
						<input type="button" onClick="location.href='menuadmin.php'" value="К списку разделов"
							   class="but">
					</td>
					<td align="right">
						<input type="hidden" name="MM_update" value="Add">
						<input name="last_action" type="hidden" id="last_action" value="write">
						<input name="Submit" type="Submit" class="but" value="Опубликовать » ">
					</td>
				</tr>
			</table>
		</form>
	</div>
	<? //Подключение соответствующего модуля
	if (strlen($row_content['kod']) > 0){ ?>
	<table width="100%" border="0" cellpadding="3" cellspacing="0" class="el_tbl"
		   title="Работа с модулем, подключенным к этому разделу">
		<tr>
			<td align="right" valign="middle" style="cursor:pointer; color:#003399; font-weight:bold"
				onClick="opcloseEdit('module'); scroll_area()"><img src="/editor/img/up.gif" width="7" height="7"
																	align="left">
				<div id="module_button" style="width:98%; text-align:left">Рабочая область модуля
					"<?= $row_modules['name'] ?>"
				</div>
			</td>
		</tr>
	</table>

	</div>
	<div id="module" style="display:<? if ($_COOKIE['idshow']['module'] != "Y") {
		echo "none";
	} else {
		echo "block";
	}; ?>; border:2px solid #CCDCE6; width:99%">
		<?
		switch (substr($row_content['kod'], 0, 7)) {
			case "catalog":
				$module = "modules/catalog/index.php";
				$catalog_id = str_replace("catalog", "", $row_content['kod']);
				break;
			case "form":
				$module = "modules/forms/index.php";
				$form_id = str_replace("form", "", $row_content['kod']);
				break;
			case "shop":
				$module = "modules/shop/index.php";
				$catalog_id = str_replace("catalog", "", $row_content['kod']);
				break;
			default:
				$module = "modules/" . $row_content['kod'] . "/index.php";
		}
		//echo "Код модуля ".$module; 
		if (is_file($module)) {
			include $module;
		} else {
			echo "<h5>Модуль не возможно отобразить. Возможно, модуль не установлен или не имеет интерфейса.</h5>";
		}
		}
		if ($saved == 1) {
			echo "<script language=javascript>alert('Изменения сохранены!');</script>";
		}
		?>
	</div>
</center>
<script>initEditor()</script>
</body>
</html>

