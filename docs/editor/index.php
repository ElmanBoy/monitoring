<?PHP  
require_once($_SERVER['DOCUMENT_ROOT'].'/Connections/dbconn.php');
//error_reporting(E_ALL);
mysql_select_db($database_dbconn, $dbconn);
$query_access1 = "SELECT * FROM userstatus";
$access1 = mysql_query($query_access1, $dbconn) or die(mysql_error());
$row_access1 = mysql_fetch_assoc($access1);
$arreqlevel=array();
do{
array_push($arreqlevel,$row_access1['id']);
}while($row_access1 = mysql_fetch_assoc($access1));

$requiredUserLevel = $arreqlevel;//array(1, 2, 4);

include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php"); ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251" />
<title>Административный раздел</title>

<script language="javascript">
<!--
//var docH=document.body.clientHeight;
function show_hide(){
var docW=document.body.clientWidth;
 if(document.getElementById("right").width!=docW){
	document.getElementById("left").width=1;
	document.getElementById("right").width=docW;
	document.getElementById("open_left").src="img/leftmenu_open.gif";
	document.cookie = "lpanel=N; expires=Thu, 31 Dec 2020 23:59:59 GMT; path=/editor/;";
 }else{
 	document.getElementById("left").width=230;
	document.getElementById("right").width=docW-230;
	document.getElementById("open_left").src="img/spacer.gif";
	document.cookie = "lpanel=Y; expires=Thu, 31 Dec 2020 23:59:59 GMT; path=/editor/;";
 }
}

function resizeWindow(){
	var docW=document.body.clientWidth;
	var docH=document.body.clientHeight;
	var t=document.getElementById("mainTable");
	var c=document.getElementById("right");
	t.width=docW;
	t.height=docH;
	c.width=docW-230;
	c.height=docH;
}

function MM_displayStatusMsg(msgStr) { //v1.0
  status=msgStr;
  document.MM_returnValue = true;
}

var pCurrWidth;
pCurrWidth=1;
var interv="";
var pEnd=0;
var undraw1="";
var opacity=100;

var speed=50;

function undrawProgress(){
	var pProgress_fon=document.getElementById("progress_fon");//ссылка на фон прогресс-бара
	var pProgress_wrap=document.getElementById("progress_wrapper");//ссылка на оболочку прогресс-бара
	var pProgress=document.getElementById("progress");//ссылка на полосу прогресс-бара

	opacity=opacity-15;
	pProgress_wrap.style.filter="Alpha(Opacity="+opacity+")";
	pProgress.style.filter="Alpha(Opacity="+opacity+")";
	if(opacity<1){
		
		window.clearInterval(undraw1);
		undraw1="";
		pProgress_wrap.style.visibility="hidden";
		pProgress.style.visibility="hidden";
		pProgress_fon.style.visibility="hidden";
	}
}


function grawProgress(){
	var pProgress_fon=document.getElementById("progress_fon");//ссылка на фон прогресс-бара
	var pProgress_wrap=document.getElementById("progress_wrapper");//ссылка на оболочку прогресс-бара
	var pProgress=document.getElementById("progress");//ссылка на полосу прогресс-бара

	pCurrWidth=pCurrWidth+1; 
	if( pCurrWidth<400){
		pProgress.style.width=pCurrWidth+"px";
	}else{
		window.clearInterval(interv);
		interv="";
		if(undraw1==""){
			undraw1=window.setInterval("undrawProgress()", speed);
		}
	}
	if(pCurrWidth>=pEnd){
		//pProgress_fon.style.visibility="hidden";
		window.clearInterval(interv); 
		interv="";
	}
}

var pMessage="Пожалуйста, подождите...";
function drawProgress(currpos, endpos){
	if( pCurrWidth<400){
		var pProgress_fon=document.getElementById("progress_fon");//ссылка на фон прогресс-бара
		var pProgress_wrap=document.getElementById("progress_wrapper");//ссылка на оболочку прогресс-бара
		var pProgress=document.getElementById("progress");//ссылка на полосу прогресс-бара
		var pMess=document.getElementById("mess");//ссылка на текст прогресс-бара
		pMess.innerHTML=pMessage;
		pProgress_fon.style.visibility="visible";
		pProgress_wrap.style.visibility="visible";
		pProgress.style.visibility="visible";
		pProgress_wrap.style.filter="Alpha(Opacity=100)";
		pProgress.style.filter="Alpha(Opacity=100)";
		pCurrWidth=currpos;
		pEnd=endpos;
		if(interv==""){
			interv=window.setInterval("grawProgress()", speed);
		}
	}
}

function stopProgress(){
	//pCurrWidth=400;
	document.getElementById("progress_wrapper").style.display="none";
	document.getElementById("progress_fon").style.visibility="hidden";
}
//-->
</script>
<style type="text/css">
<!--
body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
}
-->
</style>
<link href="style.css" rel="stylesheet" type="text/css">
<style type="text/css">
<!--
.style1 {
	color: #336666;
	font-weight: bold;
	font-size: 11px;
}
.style2 {font-size: 11px}
.style3 {font-size: 10px;color: #215253;}
.style4 {color: #FFFFFF}
-->
</style>
</head>

<body scroll=no onResize="resizeWindow()">
<div id="progress_fon" style="filter:Alpha(Opacity=20); visibility:hidden; background-color:#D9E0E8; width:100%; height:100%; position:absolute"></div>
<div id="progress_wrapper" style="width:410px; padding:10px; background-color:#E9F5F8; border:1px solid #CCCCCC; cursor:wait; text-align:left; border:1px solid #CCCCCC; visibility:hidden; position:absolute; left:43%; top:30%;">
	<div id="mess" style="font-family:Tahoma; font-size:10px; margin-bottom:5px;"></div>
	<input type="image" src="img/close.gif" alt="Скрыть прогресс-бар" onClick="stopProgress()" style="float:right; margin-right:-10px; margin-top:-25px">
	<div id="progress" STYLE="background-image:url(img/progress_fon.gif); border-right: 1px solid #AAAAAA; height:20px; width:1px; visibility:hidden; color:#FFFFFF; font-size:10px"></div>
</div>
<iframe frameborder="0" width="1" height="1" src="longsession.php" style="display:none"></iframe>
<script>
window.statusbar="";
var docH=document.body.clientHeight; //-29
document.write('<table id=mainTable width="100%" border="0" cellspacing="0" cellpadding="0" height="'+docH+'">');
</script>  <tr>
    <td height="20" colspan="3" align="center" style="border:1px outset;">
      <table width="100%" border="0" cellpadding="0" cellspacing="0" background="img/top_fon.jpg">
        <tr>
          
          <td  style="padding-left:30px">
		  <div id="ttop" class="style1">
           Администрирование сайта <a href="http://<?=strtoupper($_SERVER['SERVER_NAME'])?>" target="_blank"><?=strtoupper($_SERVER['SERVER_NAME'])?></a>          </div>
		  </td>
        </tr>
      </table>
   </td>
  </tr>
  <tr>
    <td width="<?=($_COOKIE['lpanel']=='N')?"1":"250";?>" id="left"><iframe src="leftmenu.php" name="topmenu" width="245" height="100%" align="left" scrolling="auto" frameborder="0" onMouseOver="MM_displayStatusMsg('');return document.MM_returnValue"></iframe></td>
	
	<td width="1"><img src="<?=($_COOKIE['lpanel']=='N')?"img/leftmenu_open.gif":"img/spacer.gif";?> " name="open" id="open_left" onClick="show_hide()" title="Показать меню" style="cursor:e-resize"></td>
	
    <script language=javascript> var  dw=document.body.clientWidth-230; document.write('<td width="<?=($_COOKIE['lpanel']=='N')?"100%":"'+dw+'";?>" id="right">')</script><iframe align="left" frameborder="0" height="100%" name="Main" scrolling="auto" width="100%" src="menuadmin.php"></iframe></td>
  </tr>
</table>
</body>
</html>
