<?php 
require_once($_SERVER['DOCUMENT_ROOT'].'/Connections/dbconn.php');
//error_reporting(E_ALL);

$query_access1 = "SELECT * FROM userstatus";
$access1 =el_dbselect($query_access1, 0, $access1 );
$row_access1 = mysql_fetch_assoc($access1);
$arreqlevel=array();
do{
array_push($arreqlevel,$row_access1['id']);
}while($row_access1 = mysql_fetch_assoc($access1));

$requiredUserLevel = $arreqlevel;
//$requiredUserLevel = array(1,2); 
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php"); 

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}
if(isset($_POST['Submit']) && empty($_POST['delcat'])){
	for($i=0; $i<count($_POST['id']); $i++){
	  $updateSQL = sprintf("UPDATE cat SET name=%s, sort=%s WHERE id=%s",
						   GetSQLValueString(addslashes(str_replace('``','"',$_POST['name'][$i])), "text"),
						   GetSQLValueString($_POST['sort'][$i], "int"),
						   GetSQLValueString($_POST['id'][$i], "int"));
	
	  
	  $Result1 =el_dbselect($updateSQL, 0, $Result1 );
	}
	el_log('Управление разделами','Изменен порядок следования разделов');
	el_clearcache('menu');
}

//Удаление раздела
if(!empty($_POST['delcat'])){
	if(intval($_POST['delcat'])>0){
		el_deleteCat(intval($_POST['delcat']));
		el_dbselect("OPTIMIZE TABLE `cat`", 0, $res);
		el_dbselect("OPTIMIZE TABLE `content`", 0, $res);
	}else{
		echo '<script>alert("Нельзя удалять главный раздел!")</script>';
	}
}


$query_dbmenu = "SELECT * FROM cat WHERE parent = 0 ORDER BY sort ASC";
$dbmenu =el_dbselect($query_dbmenu, 0, $dbmenu );
$row_dbmenu = mysql_fetch_assoc($dbmenu);
$totalRows_dbmenu = mysql_num_rows($dbmenu);

$colname_dbmenupod = "1";
if (isset($_GET['id'])) {
  $colname_dbmenupod = (get_magic_quotes_gpc()) ? $_GET['id'] : addslashes($_GET['id']);
}

$query_dbmenupod = sprintf("SELECT * FROM cat WHERE parent = %s ORDER BY sort ASC", $colname_dbmenupod);
$dbmenupod =el_dbselect($query_dbmenupod, 0, $dbmenupod );
$row_dbmenupod = mysql_fetch_assoc($dbmenupod);
$totalRows_dbmenupod = mysql_num_rows($dbmenupod);
?>

<html>
<head>
<title>Управление разделами</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<link href="style.css" rel="stylesheet" type="text/css">
<style type="text/css">
.lbr{border-left:1px solid #F0F9FF}
.rbr{border-right:1px solid #F0F9FF; border-left:1px solid #F0F9FF}
.more{
border:1px solid #436173; 
cursor:pointer; 
width:100px;
height:17px; 
font-size:10px;
text-align:center;
color:#436173;
}
#ndiv{filter: Alpha(Opacity=75); 
position:absolute;
width:600px;
height:30px;
background-color:#FFFFFF;
visibility:visible;
}
/*body, table, td, div{cursor:url('mbusy.cur');} */
</style>
<script language="javascript" >
var request;
var parent1;

function doLoad(parent, lev, key) {
	if(key==0){
		initdiv();
	}
	parent1=parent;
	var s=document.getElementById("statusdiv");
	url="menuchild.php?mode=child&parent="+parent+"&lev="+lev;
  if (window.XMLHttpRequest) {
    request = new XMLHttpRequest();
    request.onreadystatechange = processRequestChange;
    request.open("GET", url, true);
    request.send(null);
  } else if (window.ActiveXObject) {
    request = new ActiveXObject("Microsoft.XMLHTTP");
    if (request) {
      request.onreadystatechange = processRequestChange;
      request.open("GET", url, true);
      request.send();
    }
  }
}

function getRequestStateText(code) {
  switch (code) {
    case 0: return "Uninitialized."; break;
    case 1: return "Loading..."; break;
    case 2: return "Loaded."; break;
    case 3: return "Interactive..."; break;
    case 4: return "Complete."; break;
  }
}

ns4 = (document.layers)? true:false
ie4 = (document.all)? true:false
var mX;
var mY;

function mosecoord(){
if (ns4) {document.captureEvents(Event.MOUSEMOVE);}
	if (ns4) {mX=e.pageX; mY=e.pageY}
	if (ie4) {mX=event.clientX+document.body.scrollLeft; mY=event.clientY+document.body.scrollTop;}
}

function init(e) {
	/**/
	document.onmousemove=mousemove;
}

function initdiv(){
var s=document.getElementById("statusdiv");
	mosecoord();
	s.style.top = mY+"px";	
	s.style.left = mX+"px";
	s.style.zIndex=200;
	document.body.style.cursor="url('mybusy.ani')";
	s.style.display="block";
}

function mousemove() {
var s=document.getElementById("statusdiv");
	if(s.style.display=='block'){
		mosecoord();
		s.style.top =mY+10+"px";	
		s.style.left =mX+15+"px";
	}
}

function processRequestChange() {
var s=document.getElementById("statusdiv");
  abortRequest = window.setTimeout("request.abort();", 10000);
  if (request.readyState == 4) {
    clearTimeout(abortRequest);
    if (request.status == 200) {
      document.getElementById("ch"+parent1).innerHTML = request.responseText;
    } else {
      alert("Не удалось получить данные.\nПожалуйста, кликните еще раз.");
    }
	  s.style.display="none";
	  document.body.style.cursor="default";
  }
}
</script>
<script language="JavaScript" type="text/JavaScript">
<!--
function MM_openBrWindow(theURL,winName,features, myWidth, myHeight, isCenter) { //v3.0
  if(window.screen)if(isCenter)if(isCenter=="true"){
    var myLeft = (screen.width-myWidth)/2;
    var myTop = (screen.height-myHeight)/2;
    features+=(features!='')?',':'';
    features+=',left='+myLeft+',top='+myTop;
  }
  window.open(theURL,winName,features+((features!='')?',':'')+'width='+myWidth+',height='+myHeight);
}

function MM_goToURL() { //v3.0
  var i, args=MM_goToURL.arguments; document.MM_returnValue = false;
  for (i=0; i<(args.length-1); i+=2) eval(args[i]+".location='"+args[i+1]+"'");
}
function check(item_name){
var OK=confirm('Вы действительно хотите удалить раздел "'+item_name+'" ?');
if (OK) {return true} else {return false}
}

function show_panel(id, name, path, menu, level){
serv='<?=$_SERVER['SERVER_NAME']?>';
if(menu=="Y"){chk='checked';}else{chk='';}
var t1='<table width="100%"  border="0" cellpadding="0" cellspacing="0"><tr onClick="MM_openBrWindow(\'newcategory.php?parentid='+id+'\',\'newcat\',\'scrollbars=no,resizable=yes\',\'720\',\'450\',\'true\')" onMouseOver="style.backgroundColor=\'#CCDCE6\'"  onMouseOut="style.backgroundColor=\'\'"><td valign="middle" align="center"><img src="img/menu_new.gif" alt="Добавить подраздел" width="34" height="24" border="0" align="left" ></td><td>Добавить подраздел</td></tr><tr onMouseOver="style.backgroundColor=\'#CCDCE6\'"  onMouseOut="style.backgroundColor=\'\'" onClick="MM_openBrWindow(\'menumigrate.php?id='+id+' \',\'newcat\',\'scrollbars=yes,resizable=yes\',\'400\',\'600\',\'true\')"><td valign="middle" align="center"><img src="img/menu_migrate.gif" alt="Перенести в другой раздел" width="34" height="24" border="0" align="left"></td><td>Перенести в другой раздел</td></tr><tr onMouseOver="style.backgroundColor=\'#CCDCE6\'"  onMouseOut="style.backgroundColor=\'\'" onClick="MM_openBrWindow(\'metainfo.php?id='+id+'&lev='+level+'\',\'metainfo\',\'scrollbars=yes,resizable=yes\',\'650\',\'700\',\'true\')"><td valign="middle"  align="center"><input name="submitdelete" type="image" src="img/leftmenu_tools.gif" alt="Свойства раздела" border="0"></td><td>Свойства раздела</td></tr>';
if(path!="" && path!="/"){
var t2='<tr onMouseOver="style.backgroundColor=\'#CCDCE6\'"  onMouseOut="style.backgroundColor=\'\'" onClick="document.edit.delcat.value='+id+'; if(check(\''+name+'\')){document.edit.submit()};"><td valign="middle"  align="center"><input name="submitdelete" type="image" src="img/menu_delete.gif" alt="Удалить раздел" border="0"></td><td>Удалить раздел</td></tr></table>';
//<tr onMouseOver="style.backgroundColor=\'#CCDCE6\'"  onMouseOut="style.backgroundColor=\'\'" onClick="document.edit'+id+'.action.value=\'moveup\'; document.edit'+id+'.submit()"><td valign="middle" align="center"><input name="submitup" type="image" id="submitup"  src="img/menu_up.gif" alt="Передвинуть вверх" border="0"></td><td>Передвинуть вверх</td></tr><tr onMouseOver="style.backgroundColor=\'#CCDCE6\'"  onMouseOut="style.backgroundColor=\'\'" onClick="action.value=\'movedown\'; submit()"><td valign="middle" align="center"><input name="submitdown" type="image" id="submitdown" src="img/menu_down.gif" alt="Передвинуть вниз" border="0"></td><td>Передвинуть вниз</td></tr>
}else{
	t2="";
}
document.getElementById("panel").innerHTML=t1+t2;
//show_hide_panel();
	if(document.getElementById("panel").style.visibility=="visible"){
		document.getElementById("panel").style.visibility="hidden";
	}else{
		mtop=document.body.scrollTop + event.clientY+8;
		mleft=450;/*document.body.scrollLeft + event.clientX;*/
		document.getElementById("panel").style.left=mleft;
		document.getElementById("panel").style.top=mtop;
		document.getElementById("panel").style.visibility="visible";
	}

}


function hide_all_panel(){
document.getElementById("panel").style.visibility="hidden";
}

function hide_panel(){
document.getElementById("panel").style.visibility="hidden";
}

function close_panel(){
setTimeout('hide_panel()', 3000);
}

var overtr;
function gcp(id){
	var obj=document.getElementById("tr"+id);
	obj.style.backgroundColor="#E7E7E7";
	overtr="tr"+id;
}

function gcp1(id){
	var obj=document.getElementById("tr"+id);
	obj.style.backgroundColor="#CCDCE6";
}


function gc(id){
	var obj=document.getElementById("tr"+id);
	obj.style.backgroundColor="#E7E7E7";
}

function gc1(id){
	var obj=document.getElementById("tr"+id);
	obj.style.backgroundColor="#D9E6EE";
	overtr="tr"+id;
}



function writeCookie(name, value, hours){
  var expire = "";
  if(hours != null){
    expire = new Date((new Date()).getTime() + hours * 3600000);
    expire = "; expires=" + expire.toGMTString();
  }
  document.cookie = name + "=" + value + expire;
}

// Example:

// alert( readCookie("myCookie") );

function readCookie(name){
  var cookieValue = "";
  var search = name + "=";
  if(document.cookie.length > 0){ 
    offset = document.cookie.indexOf(search);
    if (offset != -1){ 
      offset += search.length;
      end = document.cookie.indexOf(";", offset);
      if (end == -1) end = document.cookie.length;
      cookieValue = document.cookie.substring(offset, end)
    }
  }
  return cookieValue;
}


function opentree(id, lev, key){
	var im=document.getElementById("im"+id);
	var obj=document.getElementById("ch"+id);
	if(typeof(obj)=='object' && typeof(im)=='object'){
		if(obj.style.display=='none'){
			obj.innerHTML='';
			doLoad(id, lev, key);
			im.src="img/minus.gif"; 
			obj.style.display='block';
			document.cookie = "amenu"+id+"=Y; expires=Thu, 31 Dec 2100 23:59:59 GMT;";
			document.cookie = "amenuid"+id+"="+id+"; expires=Thu, 31 Dec 2100 23:59:59 GMT;";
			document.cookie = "amenulev"+id+"="+lev+"; expires=Thu, 31 Dec 2100 23:59:59 GMT;";
		}else{
			document.cookie = "amenu"+id+"=N; expires=Thu, 31 Dec 2100 23:59:59 GMT;";
			im.src="img/plus.gif";
			obj.style.display='none';
		}
	}
}
var i=0;
var timeLoad;
function showtreememo(id, c){
	
	if(document.getElementById("tr"+id[i])){ 
		if(document.getElementById("ch"+id[i]).style.display=="none"){
			opentree(id[i], c[i], 1); 
		}
		if(!document.getElementById("parentNode"+id[i])){ 
			setTimeout(function(){showtreememo(id, c);}, 500);
		}else{
			i++;
			showtreememo(id, c);
		}
	}else{
		setTimeout(function(){showtreememo(id, c);}, 500); 
	}
}

//-->

</script>
<style type="text/css">
<!--
.style1 {
	color: #436173;
	font-weight: bold;
}
div{border:1px solid #F0F9FF;}
.parent{
	left:10px;
	background-color:#CCDCE6;
	position:absolute;
	width: 560px;
	z-index:1;
	height:30px;
	float:left
}
.parent1{width:77px; position:relative; float:left; height:30px}
.parid{width:48px; float:left; position:relative; height:30px}
.parent2{width:175px; position:relative; float:left; z-index:10; height:30px; padding-top:4px}
.parent3{width:56px; position:relative; float:left; z-index:10; height:30px; padding-top:4px}
.parent4{width:77px; position:relative; float:left; z-index:10; height:30px;}
.parent5{width:100px; position:relative; float:left; z-index:10; height:30px; padding-top:6px}
.child{ 
	left:10px;
	background-color:#D9E6EE;
	position:absolute;
	width: 556px;
	z-index:1;
	height:30px;
	float:left
}

-->
</style>
</head>

<body onBlur="hide_all_panel()" onLoad="init()">
<div style="visibility:hidden;position:absolute; left=0;top=0;z-index:100;border:1px solid #999999;background-color:white;width:250px;filter:progid:DXImageTransform.Microsoft.Shadow(color=#999999,Direction=225,Strength=3); cursor:default" id="panel">
</div>
 <div id="statusdiv" style="display:none; position:absolute; z-index:100; border:1px solid #999999;background-color:white;width:250px; height:30px; padding-top:10px;filter:progid:DXImageTransform.Microsoft.Shadow(color=#999999,Direction=225,Strength=3);"><small><b>Пожалуйста, подождите...</b></small></div>
 <h5>Управление разделами.</h5>Здесь можно изменить порядок следования разделов в меню и названия самих разделов.
   <br>
   <br>
 
 <button title="Добавить новый раздел в меню" onClick="MM_openBrWindow('newcategory.php?parentid=0','newcat','scrollbars=no,resizable=yes','720','450','true')"> Новый раздел </button><br>
<br>
 <?
function el_child($parent){
global $database_dbconn, $dbconn, $hid1;
$child=mysql_query("SELECT * FROM cat WHERE parent='$parent'", $dbconn);
	$allchil=mysql_num_rows($child);
	if($allchil>0){
		return TRUE;
	}else{
		return FALSE;
	}
}
 
 
 $imenu=0;
function el_menuadmin(){//Parent items, first level only
global $database_dbconn;
global $dbconn,$userLevel;
global $SERVER_NAME;

$querymenutree = "SELECT * FROM cat WHERE parent=0 ORDER BY sort ASC";	
$menutree =el_dbselect($querymenutree, 0, $menutree );
$row_menutree = mysql_fetch_assoc($menutree);
$m=0;
 do{
 	$m++;
 	if(strlen($row_menutree['edit'])>0){
 		$araccess=explode(",",$row_menutree['edit']);
 	}else{
	 	$araccess=array(0);
	}
 	if(in_array($userLevel,$araccess)||$userLevel=="1"){
$parent=$row_menutree['id'];
?>
<div class="parent" id="tr<?=$row_menutree['id']; ?>" onMouseOver='gcp("<?=$row_menutree['id']; ?>")' onMouseOut='gcp1("<?=$row_menutree['id']; ?>")' >

    <div class="parent1">
		  <img src="img/level_1.gif" border=0 align=middle>
		  <? if(el_child($parent)!=FALSE){?>
		  <img src="img/plus.gif" title="Подразделы" id="im<?=$row_menutree['id']?>" border=0 align=middle onClick="opentree(<?=$row_menutree['id']?>, 2, 0);" style="cursor:pointer">
		  <? }?>
	</div>
    <div class="parid">&nbsp;<?php echo $row_menutree['id']; ?></div>
	<div class="parent2">
		  <input name="id[]" type="hidden" id="id" value="<?php echo $row_menutree['id']; ?>">
          <input name="name[]" type="text" id="name" style="font-weight:bold;<?=($row_menutree['menu']!="Y")?"color:#999999":""?>" title="Двойной клик - редактирование описания раздела" onDblClick="MM_openBrWindow('e_modules/catdescedit.php?id=<?php echo $row_menutree['id']; ?>','newcat','scrollbars=yes,resizable=yes','500','200','true')" value="<?php echo str_replace('"','``',stripslashes($row_menutree['name'])); ?>" size="25">
	</div>
 
    <div class="parent3" align="center" >
      <input name="path" type="hidden" value="<?php echo $row_menutree['path']; ?>">          
	  <? if($m!=1){?><input name="sort[]" type="text" id="sort" value="<?php echo $row_menutree['sort']?>" size="2"><? }else{ echo $row_menutree['sort'];
	  echo '<input name="sort[]" type="hidden" id="sort" value="'.$row_menutree['sort'].'">';}?>
	</div>
    <div class="parent4">
		  <table  border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td width="35" align="center"><input name="imageField2" type="image" onClick="MM_goToURL('self','editor.php?cat=<?php echo $row_menutree['id']; ?>');return document.MM_returnValue" style="cursor:pointer" src="img/menu_edit.gif" alt="Редактировать содержимое раздела"  border="0"></td>
              <td width="13%" align="center" class="lbr">
			  <a href="http://<? echo $_SERVER['SERVER_NAME'].$row_menutree['path']; ?>" title="<? echo $_SERVER['SERVER_NAME'].$row_menutree['path']; ?>" target="_blank"><img src="img/menu_view.gif" width="35" height="24" border="0" style="cursor:pointer;"></a>
			  </td>
              <td width="13%" align="center" class="lbr">&nbsp;</td>
            </tr>
          </table>                    
         <input name="action" type="hidden" id="action">
	</div>
	<div class="parent5"><nobr>
         	<div onClick="show_panel('<?=$row_menutree['id']?>', '<?=str_replace('"','``',stripslashes($row_menutree['name']))?>', '<?=$row_menutree['path']?>', '<?=$row_menutree['menu']?>', 1)" class="more" id="panel<?=$row_menutree['id']; ?>">
		 Дополнительно			</div>
			<input type="hidden" name="MM_update" value="edit"> 
	</div>

</div><br>
<div style="display:none; padding-left:10px" id="ch<?=$row_menutree['id']?>"></div><br>
<? 
/*if($_COOKIE['amenu'.$row_menutree['id']]=='Y'){
	echo '<script>opentree('.$row_menutree['id'].', '.$_COOKIE['amenulev'.$row_menutree['id']].', 1)</script>';
}*/
$imenu=0;
//menuadminchild($parent, 'cat', $lev=2, $imenu=0);
	}
}while($row_menutree = mysql_fetch_assoc($menutree));
mysql_free_result($menutree);
}
?> 
<table  border="1" cellpadding="3" cellspacing="0" bordercolor="#F0F9FF" id="mainlist">
<tr bgcolor="#B1C5D2">
<td style="width:69px"><span class="style1">Уровень</span></td>
<td style="width:40px"><span class="style1">ID</span></td>
<td style="width:167px"><span class="style1">Название</span></td>
<td style="width:40px"><span class="style1">Номер</span></td>
<td style="width:188px" align="center" bgcolor="#B1C5D2"><span class="style1">Действия</span></td>
</tr>
<tr>
<td colspan="5">
<form method="POST" action="<?php echo $editFormAction; ?>" name="edit">
<? el_menuadmin(); 
function el_haveChild($id){
	$res=el_dbselect("SELECT id FROM cat WHERE parent=$id", 0, $res);
	return (mysql_num_rows($res)>0)?true:false;
}
$allcat=el_dbselect("(SELECT id FROM cat ORDER BY parent ASC) UNION (SELECT id FROM cat WHERE parent>0 ORDER BY parent ASC)", 0, $allcat);
$allc=mysql_fetch_assoc($allcat);
if(mysql_num_rows($allcat)>0){
	$i=0;
	echo '<script>
	var openCats=new Array();
	var openCookie=new Array();';
	do{
		if($_COOKIE['amenu'.$allc['id']]=='Y' && el_haveChild($allc['id'])){
			echo '
			openCats['.$i.']='.$allc['id'].';
			openCookie['.$i.']='.$_COOKIE['amenulev'.$allc['id']].';';
			$i++;
		}
	}while($allc=mysql_fetch_assoc($allcat));
	echo '
	showtreememo(openCats, openCookie);
	</script>'."\n";
}
?>
<input name="delcat" type="hidden" id="delcat"><br><br>
<input type="submit" name="Submit" value="Сохранить изменения" class="but">
</form>
</td>
</tr>
</table>
</body>
</html>
<?php
mysql_free_result($dbmenu);

mysql_free_result($dbmenupod);
?>