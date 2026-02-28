<?
require_once($_SERVER['DOCUMENT_ROOT'].'/Connections/dbconn.php');
$requiredUserLevel = array(1); 
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php"); 
session_start();

if(isset($_GET['mode']) && $_GET['mode']=='new'){
	session_unregister("temp_file");
}

$t=el_dbselect("SELECT name, path FROM template WHERE id='".$_GET['id']."'",0,$t,'row');

if(!is_dir($_SERVER['DOCUMENT_ROOT']."/tmpl/temp/")){
	mkdir($_SERVER['DOCUMENT_ROOT']."/tmpl/temp/", 0777);
}

if(!isset($_SESSION['temp_file'])){
	el_delDir($_SERVER['DOCUMENT_ROOT']."/tmpl/temp/");
	mkdir($_SERVER['DOCUMENT_ROOT']."/tmpl/temp/", 0777);
	copy($_SERVER['DOCUMENT_ROOT'].'/Connections/.htaccess', $_SERVER['DOCUMENT_ROOT'].'/tmpl/temp/.htaccess');
	$temp_file=el_genpass().".php";
	session_register("temp_file");
	copy($_SERVER['DOCUMENT_ROOT']."/tmpl/page/".$t['path'], $_SERVER['DOCUMENT_ROOT']."/tmpl/temp/".$_SESSION['temp_file']);
}

$open_file=$_SESSION['temp_file'];

if (isset($_POST['cat'])) {
	$head_content='';
	$bottom_content='';
	$fh = fopen($_SERVER['DOCUMENT_ROOT']."/tmpl/temp/head_".$open_file, "r");
	while (!feof($fh)) {
		$head_content.=fgets($fh, 4096);
	}
	fclose($fh);
	$fb = fopen($_SERVER['DOCUMENT_ROOT']."/tmpl/temp/bottom_".$open_file, "r");
	while (!feof($fb)) {
		$bottom_content.=fgets($fb, 4096);
	}
	fclose($fb);
	if(substr_count($_POST['fileName'], '.php')>0){
		$_POST['fileName']=strtolower($_POST['fileName']);
	}else{
		$_POST['fileName']=strtolower($_POST['fileName']).'.php';
	}
	unlink($_SERVER['DOCUMENT_ROOT']."/tmpl/page/".$_POST['fileName']);
	$bf=fopen($_SERVER['DOCUMENT_ROOT']."/tmpl/page/".$_POST['fileName'], 'wb');
	$newData=$head_content.stripslashes($_POST['NMH']).$bottom_content;
	if(fputs($bf, $newData)){
		$ext=el_dbselect("SELECT id FROM template WHERE path='".$_POST['fileName']."'", 0, $ext);
		if(mysql_num_rows($ext)>0){
			$rExt=mysql_fetch_assoc($ext);
			$tQuery="UPDATE template SET name='".$_POST['tempName']."' WHERE id='".$rExt['id']."'";
		}else{
			$tQuery="INSERT INTO template (name, `path`) VALUES ('".$_POST['tempName']."', '".$_POST['fileName']."')";
		}
		el_dbselect($tQuery, 0, $res);
	}
	fclose($bf);

	session_unregister("temp_file");
	el_delDir($_SERVER['DOCUMENT_ROOT']."/tmpl/temp/");
	echo "<script language=javascript>alert('Изменения сохранены!'); location.href='/editor/templates.php'</script>";
}

?>
<html>
<head>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<title>Редактор шаблонов</title>
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
	margin-top: 10px;
	margin-right: 0px;
	margin-bottom: 0px;
}
#message{border:1px #C3C3C3 inset}
.menuItem {font-family:sans-serif;font-size:10pt;width:100;padding-left:20;
   background-Color:menu;color:black}
.highlightItem {font-family:sans-serif;font-size:10pt;width:100;padding-left:20;
   background-Color:highlight;color:white}
.clickableSpan {padding:4;width:500;background-Color:blue;color:white;border:5px gray solid}
</style>
<script language="JavaScript">
var siteName="<?=$_SERVER['SERVER_NAME']?>";
var SsiteName="<?='http:\/\/'.str_replace(".", "\.", $_SERVER['SERVER_NAME'])?>";
var origFileLength; //для отслеживания были ли изменения в коде шаблона
var newFileLength;

function MM_reloadPage(init) {  //reloads the window if Nav4 resized
  if (init==true) with (navigator) {if ((appName=="Netscape")&&(parseInt(appVersion)==4)) {
    document.MM_pgW=innerWidth; document.MM_pgH=innerHeight; onresize=MM_reloadPage; }}
  else if (innerWidth!=document.MM_pgW || innerHeight!=document.MM_pgH) location.reload();
}
MM_reloadPage(true);

function MM_openBrWindow(theURL,winName,features, myWidth, myHeight, isCenter) { //v3.0
  if(window.screen)if(isCenter)if(isCenter=="true"){
    var myLeft = (screen.width-myWidth)/2;
    var myTop = (screen.height-myHeight)/2;
    features+=(features!='')?',':'';
    features+=',left='+myLeft+',top='+myTop;
  }
  window.open(theURL,winName,features+((features!='')?',':'')+'width='+myWidth+',height='+myHeight);
}

function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}

function MM_goToURL() { //v3.0
  var i, args=MM_goToURL.arguments; document.MM_returnValue = false;
  for (i=0; i<(args.length-1); i+=2) eval(args[i]+".location='"+args[i+1]+"'");
}
function docframe() {
if(top==self){
var parent_url="index.html";
var orphan_url=self.location.href;
var reframe_url=parent_url+"?"+orphan_url
location.href=reframe_url
}
//top.location="index.html"
}

function showiframe(){
	var w=document.body.clientWidth-20; 
	var h=document.body.clientHeight-120;
	document.getElementById("message").style.height=h+"px";
	document.getElementById("messagePrev").style.height=h+"px";
	document.getElementById("messagePrev").style.width=w+"px";
	document.getElementById("NMH").style.height=h+"px";
	document.getElementById("NMH").style.width=w;
	document.getElementById("modul_panel").style.height=h;
	document.getElementById("modul_table").style.height=h;
}

function opcloseEdit(id){
if (document.getElementById(id).style.display=="none"){
document.cookie = "idshow["+id+"]=Y; expires=Thu, 31 Dec 2020 23:59:59 GMT; path=/editor/;";
document.getElementById(id).style.display="block";
}else{
document.cookie = "idshow["+id+"]=N; expires=Thu, 31 Dec 2020 23:59:59 GMT; path=/editor/;";
document.getElementById(id).style.display="none";
};
}

function opcloseFilter(id){
	if (document.getElementById(id).style.display=="none"){
		document.cookie = "idshow["+id+"]=Y; expires=Thu, 31 Dec 2020 23:59:59 GMT; path=/editor/;";
		document.getElementById(id).style.display="block";
	}else{
		document.cookie = "idshow["+id+"]=N; expires=Thu, 31 Dec 2020 23:59:59 GMT; path=/editor/;";
		document.getElementById(id).style.display="none";
	}
}


function scroll_area(){
window.scrollBy(0, window.innerHeight ? window.innerHeight : document.body.clientHeight);
}

function ctr_save(){
	if(event.ctrlKey&&event.keyCode==83){
		SaveHTML();
 		sendtext();
		document.Add.submit();
	}
}

function modul_show(){
	var p=document.getElementById("modul_panel");
	var im=document.getElementById("panel_switcher");
	if(p.style.display=="none"){
		p.style.display="block";
		im.src="../img/modul_off.gif";
		im.alt="Скрыть панель компонентов";
	}else{
		p.style.display="none";
		im.src="../img/modul_on.gif";
		im.alt="Показать панель компонентов";
	}
}

function resizeFrame(){
	var h=document.body.clientHeight-110;
	document.getElementById("message").style.height=h;
	document.getElementById("messagePrev").style.height=h;
	document.getElementById("NMH").style.height=h;
	document.getElementById("modul_panel").style.height=h;
	document.getElementById("modul_table").style.height=h;
}

//-->
</SCRIPT>
<script src="/editor/editor.js" language="JavaScript"></script>
<script src="/editor/colors.js" language="JavaScript"></script>
<script src="/editor/e_modules/template_editor.js" language="JavaScript"></script>
<script src="/editor/e_modules/JsHttpRequest/lib/JsHttpRequest/JsHttpRequest.js"></script>
<script src="/editor/e_modules/module_view.js" language="JavaScript"></script>

<link href="/editor/style.css" rel="stylesheet" type="text/css">
<style type="text/css">
<!--
.style1 {
	font-size: 9pt;
	color: #FFFFFF;
}
-->
</style>
</head>

<body onLoad="MM_preloadImages('/editor/img/aleft.gif','/editor/img/aright.gif','/editor/img/ashir.gif','/editor/img/blank.gif','/editor/img/blist.gif','/editor/img/bold.gif','/editor/img/br.gif','/editor/img/center.gif','/editor/img/code.gif','/editor/img/copy.gif','/editor/img/cut.gif','/editor/img/fcolor.gif','/editor/img/help.gif','/editor/img/hr.gif','/editor/img/HTML.gif','/editor/img/I.gif','/editor/img/ileft.gif','/editor/img/image.gif','/editor/img/iright.gif','/editor/img/italic.gif','/editor/img/nlist.gif','/editor/img/Normal.gif','/editor/img/paragraf.gif','/editor/img/paste.gif','/editor/img/preview.gif','/editor/img/print.gif','/editor/img/px.gif','/editor/img/redo.gif','/editor/img/save.gif','/editor/img/strike.gif','/editor/img/table.gif','/editor/img/under.gif','/editor/img/undo.gif','/editor/img/wlink.gif')"  onResize="showiframe()">

<form method="post" name="Add">
<input type="hidden" name="fileName" value="<?=$t['path']?>">
<input type="hidden" name="tempName" value="<?=addslashes($t['name'])?>">
<input type="hidden" name="tempFile" value="<?=$open_file?>">
<input id="im1" type="hidden"><input id="im2" type="hidden">
<table width="100%" cellpadding="0" cellspacing="0" bgcolor="#CCDCE6" id="icons_panel">
    <tr height="1" bgcolor="silver"> 
      <td></td>
    </tr>
    <tr height="28">
	<td bgcolor="#CCDCE6"> 
	<table cellpadding="0" cellspacing="0">
        <tr height=28>
     <td width="25" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/page_white_stack.gif"  onClick="location.href='/editor/templates.php'"
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Перейти к списку шаблонов">      </td>
     <td width="25" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/page.gif" onClick="fileDialog('open')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Создать шаблон">      </td>
     <td width="25" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/folder_page.gif" onClick="fileDialog('new')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Открыть существующий шаблон">      </td>
	  <td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/disk.gif" onClick="fileDialog('save')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Сохранить">      </td>
	  <td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/page_save.gif" onClick="fileDialog('saveas')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Сохранить как...">      </td>
      <td width="5" bgcolor="#CCDCE6"> <img src="/editor/img/I.gif"  border="0"> </td>
      <td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/cut.gif"   onClick="FormatText('cut')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Вырезать">      </td>
      <td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/copy.gif"  onClick="FormatText('copy')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Копировать">      </td>
      <td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/paste.gif" onClick="FormatText('paste'); cleanHTMLContent()" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Вставить">      </td>
      <td width="5" bgcolor="#CCDCE6"> <img src="/editor/img/I.gif"  border="0"> </td>
      <td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/zoom.gif"  onClick="Preview('<?=$page_url?>')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Просмотр раздела">      </td>
      <td width="5" bgcolor="#CCDCE6"> <img src="/editor/img/I.gif"  border="0"> </td>
      <td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/undo.gif"  onClick="FormatText('Undo', '')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Назад">      </td>
      <td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/redo.gif"  onClick="FormatText('Redo', '')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Вперед">      </td>
      <td width="5" bgcolor="#CCDCE6"> <img src="/editor/img/I.gif"  border="0"> </td>
      <td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/world_link.png" alt="Вставить ссылку" width="20" height="20" 
style="cursor: hand;"  onClick="OpenLink()" onMouseOver="be(this)" onMouseOut="ae(this)" >      </td>
<td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/world_delete.png" alt="Удалить ссылку" width="20" height="20" 
style="cursor: hand;"  onClick="FormatText('Unlink')" onMouseOver="be(this)" onMouseOut="ae(this)" >      </td>
<td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/anchor.gif" alt="Вставить якорь" width="20" height="20" 
style="cursor: hand;"  onClick="MM_openBrWindow('/editor/anchor.php?path=<? echo $row_content['path']; ?>','anchor','','600','200','true')" onMouseOver="be(this)" onMouseOut="ae(this)">      </td>
      <td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/paragraf.gif"  onClick="FormatText('InsertParagraph', 'false')" style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Новый абзац">      </td>
      <td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/br.gif"  onClick="AddHTML('<BR>')" style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Новая строка">      </td>
      <td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/hr.gif"  onClick="FormatText('InsertHorizontalRule', '')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Горизонтальная полоса">      </td>
        <td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/image.gif" alt="Вставить картинку" 
style="cursor: hand;"  onClick="AddImage()" onMouseOver="be(this)" onMouseOut="ae(this)">        </td>
     
      <td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/table.gif"  onClick="InsertTable()" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Вставить таблицу">      </td>
      <td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/table_row_insert.gif"  onClick="InsertTable()" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Вставить строку в таблицу">      </td>
      <td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/table_row_delete.gif"  onClick="InsertTable()" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Удалить строку из таблицы">      </td>
      <td width="5" bgcolor="#CCDCE6"> <img src="/editor/img/I.gif"  border="0"> </td>
          <td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/cleanword.gif"  onClick="cleanHTMLContent()" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" class="Im" alt="Очистить HTML код">      </td>
	  <td width="23" align="center" nowrap bgcolor="#CCDCE6"> <img src="/editor/img/code.gif"  onClick="Code = prompt('Введите HTML-код', ''); 	if ((Code != null) && (Code != '')){ AddHTML(Code); }" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" class="Im" alt="Вставить HTML код">      </td>
	  <td width="23" align="center" nowrap> <img src="/editor/img/comment_add.gif"  onClick="add_comm()" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" class="Im" alt="Создать комментарий из выделенного текста">      </td>
	  <td align="center" nowrap bgcolor="#CCDCE6"><!--<a href="javascript:createEditebleZone()">Пометить как редактируюмую зону</a>-->
	  <b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Редактируем шаблон &laquo;<?=addslashes($t['name'])?>&raquo;</b>
	  </td>
	  </tr>
	  </table>
	  </td>
    </tr><tr bgcolor="#CCDCE6" style="border-top:2px solid #F0F9FF">
            <td colspan="25" bgcolor="#CCDCE6" style="border-top:2px solid #F0F9FF">
			  <table cellpadding="0" cellspacing="0">
        <tr height=28>
          <td>&nbsp;
            <select name="selectSize" title="Размер шрифта" onChange="FormatText('fontsize', selectSize.options[selectSize.selectedIndex].value);document.Add.selectSize.options[0].selected = true;" >
              <option selected>-- Размер --</option>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="4">4</option>
              <option value="5">5</option>
              <option value="6">6</option>
            </select>&nbsp;          </td>
			
			<td>&nbsp;
            <select name="selectStyle" title="Стиль" onChange="AddH( selectStyle.options[selectStyle.selectedIndex].value);selectStyle.options[0].selected = true;" >
              <option selected>-- Стиль --</option>
              <option value="1">Заголовок 1</option>
              <option value="2">Заголовок 2</option>
              <option value="3">Заголовок 3</option>
              <option value="4">Заголовок 4</option>
              <option value="5">Заголовок 5</option>
              <option value="6">Заголовок 6</option>
            </select>&nbsp;          </td>
			<td>&nbsp;
			<select name="selectFont" onChange="FormatText('fontname', selectFont.options[selectFont.selectedIndex].value);document.Add.selectFont.options[0].selected = true;"  >
			<option selected>-- Шрифт --</option>
			<option value="Arial, Helvetica, sans-serif">Arial</option>
			<option value="Courier New, Courier, mono">Courier New</option>
			<option value="Times New Roman, Times, serif">Times New Roman</option>
			<option value="Verdana, Arial, Helvetica, sans-serif">Verdana</option>
			</select>
			&nbsp;
			</td>
			
          <td width="22" nowrap align="center"> <img src="/editor/img/bold.gif"  onClick="FormatText('bold', '')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Жирный шрифт"> </td>
          <td width="22" nowrap align="center"> <img src="/editor/img/italic.gif"  onClick="FormatText('italic', '')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Наклонный шрифт"> </td>
          <td width="22" nowrap align="center"> <img src="/editor/img/under.gif"  onClick="FormatText('underline', '')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Подчеркнутый шрифт"> </td>
          <td width="22" nowrap align="center"> <img src="/editor/img/strike.gif"  onClick="FormatText('StrikeThrough', '')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Перечеркнутый шрифт"> </td>
           <td width="22" nowrap align="center"> <img src="/editor/img/fcolor.gif"  onClick="OpenColors()" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Цвет шрифта"> </td>
          <td width="5"> <img src="/editor/img/I.gif"  border="0"> </td>
          <td width="22" nowrap align="center"><img src="/editor/img/aleft.gif"  onClick="FormatText('JustifyLeft', '')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Выравнивание по левому краю"></td>
          <td width="22" nowrap align="center"><img src="/editor/img/center.gif"  onClick="FormatText('JustifyCenter', '')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Выравнивание по центру"></td>
          <td width="22" nowrap align="center"><img src="/editor/img/ashir.gif"  onClick="FormatText('JustifyFull', '')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Выравнивание по ширине текста"></td>
          <td width="22" nowrap align="center"><img src="/editor/img/aright.gif"  onClick="FormatText('JustifyRight', '')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Выравнивание по правому краю"></td>
          <td width="5"> <img src="/editor/img/I.gif"  border="0"> </td>
          <td width="22" nowrap align="center"> <img src="/editor/img/blist.gif" onMouseOver="be(this)" onMouseOut="ae(this)"  onClick="FormatText('InsertUnorderedList', '')" 
style="cursor: hand;" alt="Ненумерованный список"> </td>
          <td width="22" nowrap align="center"> <img src="/editor/img/nlist.gif"  onClick="FormatText('InsertOrderedList', '')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Нумерованный список"> </td>
          <td width="22" nowrap align="center"> <img src="/editor/img/ileft.gif"  onClick="FormatText('Outdent', '')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Уменьшить отступ"> </td>
          <td width="22" nowrap align="center"> <img src="/editor/img/iright.gif"  onClick="FormatText('Indent', '')" 
style="cursor: hand;" onMouseOver="be(this)" onMouseOut="ae(this)" alt="Увеличить отступ"> </td>
  <td width="5"> <img src="/editor/img/I.gif"  border="0"> </td>
<td width="22" nowrap align="center"> <img src="/editor/img/help.gif" alt="Помощь" 
style="cursor: hand;"  onClick="MM_openBrWindow('help.htm','help','scrollbars=yes,resizable=yes','600','600','true')" onMouseOver="be(this)" onMouseOut="ae(this)">      </td>
        </tr>
      </table> </td>
    </tr>
    <tr height="1" bgcolor="silver"> 
      <td colspan="31"></td>
    </tr>
      <tr height="1" bgcolor="silver"> 
      <td colspan="31"></td>
    </tr>
    <tr> 
      <td colspan="20"> </td>
    </tr>
</table>
  <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
    <tr> 
      <td valign="middle"><table border="0" cellpadding="0" cellspacing="0" id="modul_table">
	  <tr><td valign="middle" align="center">
	  <div  id="modul_panel" style="display:none">
	    <p><img src="../img/modul_anons.gif" name="anons" 
		value="('el_anonsNews', array(10, 'simple'))" alt="Анонсы последних новостей, статей и т.п."
		ondragend="addNewModule(this)" ondrag="drawNewModule(this)"><br>
	      <br>
	      <img src="../img/modul_calendar.gif" width="50" height="50" name="calend" 
		  value="('el_calendNews', '')" alt="Колендарь событий, архива новостей и т.п."
		  ondragend="addNewModule(this)" ondrag="drawNewModule(this)"><br>
	      <br>
	      <img src="../img/modul_counter.gif" width="50" height="50" name="counter" 
		  value="('el_counter', '')" alt="Сборщик статистики посещаемости сайта (невидимый элемент)"
		  ondragend="addNewModule(this)" ondrag="drawNewModule(this)"><br>
	      <br>
	     <!-- <img src="../img/modul_forms.gif" width="50" height="50" alt="Формы обратной связи, заявок и т.п."
		  ondragend="addNewModule(this)" ondrag="drawNewModule(this)"><br>
	      <br>-->
	      <img src="../img/modul_menu.gif" width="50" height="50" name="menu" 
		  value="('el_menut_simple', '')" alt="Меню разделов и подразделов различных типов" 
		  ondragend="addNewModule(this)" ondrag="drawNewModule(this)"><br>
	      <br>
	      <img src="../img/modul_polls.gif" width="50" height="50" name="polls" 
		  value="('el_poll', 'newest')" alt="Опрос/голосование на сайте"
		  ondragend="addNewModule(this)" ondrag="drawNewModule(this)"><br>
	      <br>
	      <img src="../img/modul_text.gif" width="50" height="50" name="text" 
		  value="('el_pageprint', 'text')" alt="Текст страницы, заголовок или текст из инфоблока"
		  ondragend="addNewModule(this)" ondrag="drawNewModule(this)">
		  <br>
	      <img src="../img/modul_module.gif" width="50" height="50" name="module" 
		  value="('el_pagemodule', '')" alt="Динамически подключаемые модули (невидимый элемент)"
		  ondragend="addNewModule(this)" ondrag="drawNewModule(this)"></p>
	  </div></td>
	  <td align="right" valign="middle" style="border-left:1px solid #c0c0c0">
	  <img src="../img/modul_on.gif" onClick="modul_show()" id="panel_switcher" style="cursor:pointer" alt="Показать панель компонентов">	  </td></tr>
	  </table></td>
	  <td width="100%"> <input name="text" type="hidden" id="text" >
    <input name="cat" type="hidden" id="cat" value="<?php echo $row_content['cat']; ?>">
<div id="Frm">
<iframe src="<?='/editor/e_modules/template_code.php?file='.$open_file?>" id="message" name="message" style="width:100%; height:100%;"  onpaste="cleanHTMLContent()" resize=yes></iframe>
</div>
<div id="Prev" style="display:none">
<iframe src="" id="messagePrev" name="messagePrev" style="width:100%; height:100%;" resize=yes></iframe>
</div>
<textarea name="NMH" id="NMH" style="width:100%; height:100%; display:none" onKeyDown="ctr_save()"></textarea>
<div  id="botTabs">
	<ul>
		<li id="1" class="current"><a href="javascript:MyShowNormal(1);"><img src="/editor/img/page_paintbrush.gif" border="0" align="left">&nbsp;Дизайн</a></li>
		<li id="2"><a href="javascript:MyShowHTML(2);"><img src="/editor/img/page_code.gif" border="0" align="left">&nbsp;HTML</a></li>
		<li id="3"><a href="javascript:MyShowPreview(3);"><img src="/editor/img/page_green.gif" border="0" align="left">&nbsp;Просмотр</a></li>
	</ul>
</div>
<div align="right" style="margin-top:-15px; display:none" id="publish">
      <input name="Submit" type="button" onClick="location.href='/editor/templates.php'" class="but" value="К списку шаблонов">
	  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	  <input name="Submit" type="button" onClick="MySaveHTML(); sendtext(); fileDialog('saveas')" class="but" value="Сохранить как... »">
	  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	  <input name="Submit2" type="button" onClick="MySaveHTML(); sendtext(); fileDialog('save')" class="but" value="Сохранить »">
</div></td>
    </tr>
  </table>
  <div id=menu1 onClick="clickMenu()" onMouseOver="switchMenu()" onMouseOut="switchMenu(); hideMenu()" style="position:absolute;display:none;width:100;background-Color:menu; border: outset 2px gray">
<div class="menuItem" id=mnuProps>Настройки</div>
<div class="menuItem" id=mnuMove>Переместить</div>
<div class="menuItem" id=mnuDel>Удалить</div>
</div>
<div id="modLayer" style="width:50px; height:50px; position:absolute; display:none"></div>
	<script language="javascript">
	showiframe();
	//frames.message.document.designMode = "On";
	var timeLoad="";
	if(timeLoad==""){
		timeLoad=window.setInterval("checkLoad()", 10);
	}
	</script>
</form>
</body>
</html>
