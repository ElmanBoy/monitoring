<?
$form=$_GET['form'];
$field=$_GET['field'];
?>
<html>
<head>
<title>HTML-Редактор________________________________________________________________________________________________________________________________________________________________________________________________________________________________________________________________________________</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<script src="editor.js" language="JavaScript"></script>
<script src="colors.js" language="JavaScript"></script>
<style>
input, select { FONT-FAMILY: MS Sans Serif; FONT-SIZE: 12px; }
body, td { FONT-FAMILY: Tahoma; FONT-SIZE: 12px }
a:hover { color: #86869B }
a:visited { color: navy }
a { color: navy }
a:active { color: #ff0000 }
.st { FONT-FAMILY: MS Sans Serif; FONT-SIZE: 12px; }
.MenuFile { position:absolute; top:27; }
body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
	border:0px;
}
body,td,th {
	font-family: Arial, Helvetica, sans-serif;
}
</style>
<script type="text/javascript" src="/editor/e_modules/fckeditor/fckeditor.js"></script>
<script type="text/javascript">
function FCKeditor_OnComplete( editorInstance ){
	editorInstance.Events.AttachEvent( 'OnBlur'	, FCKeditor_OnBlur ) ;
	editorInstance.Events.AttachEvent( 'OnFocus', FCKeditor_OnFocus ) ;
}

function FCKeditor_OnBlur( editorInstance ){
	editorInstance.ToolbarSet.Collapse() ;
}

function FCKeditor_OnFocus( editorInstance ){
	editorInstance.ToolbarSet.Expand() ;
}
	</script>

<script language="JavaScript">
function sendtext() {
opener.document.<? if(!empty($form)){echo $form;}else{echo "Add";}?>.<?=$field?>.value='<?=str_replace("\r\n","",str_replace("'", '`', $_POST['message']))?>';
window.close();
 }
<?
if(isset($_POST['message']) && strlen($_POST['message'])>0){
	echo 'sendtext();';
}
?>
</script>
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body onLoad="window.resizeTo(770,660); window.status='HTML-редактор';">
  <form method="post" name="Add">
  		<script type="text/javascript">
		<!--
		var sBasePath = '/editor/e_modules/fckeditor/';
		oFCKeditor1 = new FCKeditor( 'message' ) ;
		
		oFCKeditor1.Config['ToolbarStartExpanded'] = false ;
		oFCKeditor1.BasePath	= sBasePath ;
		oFCKeditor1.Value	= opener.document.<?=$form?>.<?=$field?>.value ;
		oFCKeditor1.Width = '100%' ;
		oFCKeditor1.Height = '95%' ;
		oFCKeditor1.Create() ;
		//-->

		</script>
<input type="submit" name="Button" value="Вставить" style="background-color:#CBEDCD;">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;            
<input type="button" style="background-color:#FBD0C6;" onClick="window.close()" name="Button" value="Закрыть">
</form></center>
</body>
</html>
