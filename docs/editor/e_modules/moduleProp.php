<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<title>Настройка компонента </title>
<link href="/editor/style.css" rel="stylesheet" type="text/css">
<style type="text/css">
body{margin:10}
</style>
<script language="javascript">
var state=0;
var selObj="";
var tempName="";
var Tempcount;
var redraw=0;
var currTable;

function setObje(){
	var sel=document.getElementById("module");
	var selVar=sel.options[sel.selectedIndex].value;
	var newParams="";
	var end="";
	var startArr="";
	var endArr="";
	for(var i=0; i<frmParam.elements.length; i++){
		if(i!=frmParam.elements.length-1){end=","}else{end=""}
		if(frmParam.elements.length>2){
			if(i==1){
				startArr="array(";
			}else{
				startArr="";
			}
			if(end==""){
				end=")";
			}else{
				end=",";
			}
		}
		if(frmParam.elements[i].value.length>0){
			newParams+=startArr+"'"+frmParam.elements[i].value+"'"+end;
		}else{
			newParams+=startArr+"''"+end;
		}
		if(frmParam.elements.length<2){
			newParams=newParams+",''";
		}
	}
	//alert("<?='el_'.$_GET['module']?>\n("+newParams+")");
	opener.getNewVal("<?=$_GET['module']?>","("+newParams+")"); 
	opener.drawModuleNow();
	redraw=1;
	setTimeout("closeWin()", 1000);
}

function closeWin(){
	//opener.disableEdit();
	window.close();
}

function setFocus(){
	var sel=document.getElementById("module");
	if(state==0){
		window.focus();
		
	}
	state=0;
}

function selActive(){
	state=1
}

function selDeactive(){
	state=0
}

function redirect(){
	var sel=document.getElementById("module");
	var selVar=sel.options[sel.selectedIndex].value;
	var newLink="moduleProp.php?params=()&module=<?=$_GET['module']?>&fns="+selVar+"&table="+currTable;
	location.href=newLink;
}
</script>
</head>
<body onBlur="setFocus()" onUnload="if(redraw==0){opener.drawModuleNow()}">

<? // echo $_GET['module'].' params:'.stripslashes($_GET['params']);
include $_SERVER['DOCUMENT_ROOT'].'/editor/e_modules/modulePropArray.php';

$modArr='functionProp'.$_GET['module'];
$modProps=str_replace('(', '', stripslashes($_GET['params']));
$modProps=str_replace(')', '', $modProps);
$modProps=explode(',', $modProps);
$fnsName=str_replace("'", "", $modProps[0]); 
if($fnsName==""){$fnsName=$_GET['fns'];}
if(substr_count($modProps[1], 'array')>0){
	$modProps[1]=str_replace('array', '', $modProps[1]);
	
}

if(count($$modArr)<1){
	echo "Этот компонент не имеет настроек.
	<script language=\"javascript\">
	opener.focus();
	window.blur();
window.returnValue=\"".$_GET['params']."\";
window.close();
</script>";
}else{
	echo "<table class='el_tbl' cellpadding=5><form name=frmParam><tr><td><b>Тип компонента:</b></td>
	<td><select name=module id=module onChange='redirect()' ondeactivate='selDeactive()' onbeforeactivate='selActive()'>
	<option></option>";
	while(list($key, $val)= each(${$modArr})){
		($fnsName==$key)?$sel1='selected':$sel1='';
		echo "<option value='$key' $sel1>$val[fns_name]</option>\n";
	}
	echo "</select></td></tr>\n";
	echo "<tr><td colspan=2><div style='color:green; font-weight:bold'>".${$modArr}[$fnsName]['fns_description']."</div></td></tr>";
	
	while(list($key, $val)= each(${$modArr}[$fnsName])){
		($fnsName==$key)?$sel2='selected':$sel2='';
		if($key!='fns_name' && $key!='fns_description'){
			$i++;
			if(is_array($val)){
				echo "<tr><td colspan=2>";
				if(array_key_exists('control_desc', $val)){
					echo $val['control_desc'].'</br>';
					$nval=$val[0];
				}else{
					$nval=$val;
				}
				$c=0;
				(substr_count($key, ' -p')>0)?$ch=' onChange=\'currTable=this.options[this.selectedIndex].value; redirect()\'':$ch='';
				echo "<select name='$key' ondeactivate='selDeactive()' onbeforeactivate='selActive()' $ch>";
				while(list($skey, $sval)= each($nval)){
					if($skey!='control_desc'){
						$c++;
						("'".$skey."'"==trim($modProps[$i]) || (strlen($modProps[$i])==0 && $c==1) || (str_replace(' -p', '', $_GET['table'])==$skey))?$sel2="selected":$sel2="";
						echo "<option value='$skey' ".$sel2.">$sval</option>\n";
					}
				}
				echo "</select>\n</td></tr>\n";
			}else{
				echo "<tr>
				<td valign=top>
				<input type=text size=15 name='$key' value=\"".trim(str_replace("'", "", $modProps[$i]))."\" 
				onbeforeactivate='selActive()' ondeactivate='selDeactive()'></td>
				<td>$val</td></tr>\n";
			}
		}
	}
	echo "</form><tr><td colspan=2 align=center><input type=button value='Сохранить' onclick='setObje()' class='but'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=button value='Закрыть' onclick='window.close()' class='but'></td></tr></table>";
}













switch ($_GET['module']){
case 'menu':
?>

<script language="javascript">
window.document.title+=' "Меню"';
</script>
<?
break;
case 'counter':
?>
<script language="javascript">
window.document.title+=' "Счетчик"';
</script>
<?
break;
case 'text':
?>
<script language="javascript">
window.document.title+=' "Текст"';
</script>
<?
break;
case 'anons':
?>
<script language="javascript">
window.document.title+=' "Анонсы"';
</script>
<?
break;
case 'calend':
?>
<script language="javascript">
window.document.title+=' "Календарь"';
</script>
<?
break;
case 'polls':
?>
<script language="javascript">
window.document.title+=' "Опросы"';
</script>
<?
break;
case 'module':
?>
<script language="javascript">
window.document.title+=' "Модули"';
</script>
<?
break;
}
?>
<script language="javascript">
window.returnValue="<?=$_GET['params']?>";
</script>

</body>
</html>
