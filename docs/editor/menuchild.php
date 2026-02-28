<?
require_once('../Connections/dbconn.php'); 

mysql_select_db($database_dbconn, $dbconn);
$query_access1 = "SELECT * FROM userstatus";
$access1 = mysql_query($query_access1, $dbconn) or die(mysql_error());
$row_access1 = mysql_fetch_assoc($access1);
$arreqlevel=array();
do{
array_push($arreqlevel,$row_access1['id']);
}while($row_access1 = mysql_fetch_assoc($access1));

$requiredUserLevel = $arreqlevel;
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php"); 

if($_GET['mode']=='child' && isset($_GET['parent'])){
mysql_select_db($database_dbconn, $dbconn);

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


function menuadminchild($parent, $table, $lev, $imenu){//Child Items
global $database_dbconn;
global $dbconn, $userLevel;
global $SERVER_NAME;
$querymenuchild = "SELECT * FROM cat WHERE parent='$parent' ORDER BY sort ASC";
$menuchild = mysql_query($querymenuchild, $dbconn) or die(mysql_error());
$row_menuchild = mysql_fetch_assoc($menuchild);
$idchild=$row_menuchild['id'];
if($idchild){//if item is exist...
$imenu++;
($imenu>1)?$lev++:$lev=$lev;
do{
$parent1=$row_menuchild['id'];
if(strlen($row_menuchild['edit'])>0){
 		$araccess=explode(",",$row_menuchild['edit']);
 	}else{
	 	$araccess=array(0);
	}
 	if(in_array($userLevel,$araccess)||$userLevel=="1"){
?>
<div class="child" id="tr<?php echo $row_menuchild['id']; ?>" onMouseOver="gc('<?=$row_menuchild['id']; ?>')" onMouseOut="gc1('<?=$row_menuchild['id']; ?>')" >

    <div class="parent1"><img src="img/level_<?=$lev?>.gif" border=0 align=middle>
		   <? if(el_child($parent1)!=FALSE){?>
		  <img src="img/plus.gif" title="Подразделы" id="im<?=$row_menuchild['id']?>" border=0 align=middle onClick="opentree(<?=$row_menuchild['id']?>, <?=$_GET['lev']+1?>, 0)" style="cursor:pointer">
		  <? }?>
	</div>
	<div class="parid">&nbsp;<?php echo $row_menuchild['id']; ?></div>
    <div class="parent2">            <input name="id[]" type="hidden" id="id" value="<?php echo $row_menuchild['id']; ?>">
          <input name="name[]" type="text" id="name" <?=($row_menuchild['menu']!="Y")?"style=\"color:#999999\"":""?> title="Двойной клик - редактирование описания раздела" onDblClick="MM_openBrWindow('e_modules/catdescedit.php?id=<?php echo $row_menuchild['id']; ?>','newcat','scrollbars=yes,resizable=yes','500','200','true')" value="<?php echo str_replace('"','``',stripslashes($row_menuchild['name'])); ?>" size="26">
	</div>
    <div  class="parent3" align="center">            <input name="path" type="hidden" value="<?php echo $row_menuchild['path']; ?>">          <input name="sort[]" type="text" id="sort" value="<?php echo $row_menuchild['sort']?>" size="2">
   </div>
   
   <div class="parent4">
            <table  border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td width="35" align="center"><input name="imageField" type="image" style="cursor:pointer" onClick="MM_goToURL('self','editor.php?cat=<?php echo $row_menuchild['id']; ?>');return document.MM_returnValue" src="img/menu_edit.gif" alt="Редактировать содержимое раздела"  border="0">
			  </td>
              <td width="13%" align="center" class="lbr"><a href="http://<? echo $_SERVER['SERVER_NAME'].$row_menuchild['path']; ?>" title="<? echo $_SERVER['SERVER_NAME'].$row_menuchild['path']; ?>" target="_blank"><img src="img/menu_view.gif" width="35" height="24" border="0" style="cursor:pointer;"></a>
			  </td>
              <td width="13%" align="center" class="lbr">&nbsp;</td>
            </tr>
            </table>            
            <input name="action" type="hidden" id="action" value=""><input type="hidden" name="MM_update" value="edit">
            <input name="parentNode<?=$parent?>" type="hidden" id="parentNode<?=$parent?>" value="<?=$parent?>">
  </div>
  
	<div class="parent5">
    	<div onClick="show_panel('<?=$row_menuchild['id']?>', '<?=str_replace('"','``',stripslashes($row_menuchild['name']))?>', '<?=$row_menuchild['path']?>', '<?=$row_menuchild['menu']?>', <?=$lev?>)" class="more">
		 Дополнительно
		</div>
		
    </div>

</div><br>
<div style="display:none" id="ch<?=$row_menuchild['id']?>"></div><br>     
<? 

	}
}while($row_menuchild = mysql_fetch_assoc($menuchild));
mysql_free_result($menuchild);
echo "\n"; 
 }
} 
if(ob_get_length())ob_clean();
header("Content-type: text/html; charset=windows-1251");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
echo '<br>';
menuadminchild($_GET['parent'], 'cat', $_GET['lev'], $imenu=0);
}
?>