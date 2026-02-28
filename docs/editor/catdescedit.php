<?php require_once('../../Connections/dbconn.php'); 

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "edit")) {
  $updateSQL = sprintf("UPDATE cat SET ptext=%s WHERE id=%s",
                       GetSQLValueString($_POST['ptext'], "text"),
                       GetSQLValueString($_POST['id'], "int"));

  mysql_select_db($database_dbconn, $dbconn);
  $Result1 = mysql_query($updateSQL, $dbconn) or die(mysql_error());
  $close=1;
}

$colname_desc = "1";
if (isset($_GET['id'])) {
  $colname_desc = (get_magic_quotes_gpc()) ? $_GET['id'] : addslashes($_GET['id']);
}
mysql_select_db($database_dbconn, $dbconn);
$query_desc = sprintf("SELECT id, name, ptext FROM cat WHERE id = %s", $colname_desc);
$desc = mysql_query($query_desc, $dbconn) or die(mysql_error());
$row_desc = mysql_fetch_assoc($desc);
$totalRows_desc = mysql_num_rows($desc);
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251" />
<title>Редактирование описания раздела </title>
<link href="../style.css" rel="stylesheet" type="text/css" />
<script language="javascript">
function MM_openBrWindow(theURL,winName,features, myWidth, myHeight, isCenter) { //v3.0
  if(window.screen)if(isCenter)if(isCenter=="true"){
    var myLeft = (screen.width-myWidth)/2;
    var myTop = (screen.height-myHeight)/2;
    features+=(features!='')?',':'';
    features+=',left='+myLeft+',top='+myTop;
  }
  window.open(theURL,winName,features+((features!='')?',':'')+'width='+myWidth+',height='+myHeight);
}
</script>
<? if($close==1){echo"
<script language=\"javascript\">
window.close();
</script>";} ?>
</head>

<body>
<table width="98%" border="0" align="center" cellpadding="3" cellspacing="0">
<form action="<?php echo $editFormAction; ?>" name="edit" method="POST">
  <tr>
    <td>Название:</td>
    <td><b><?php echo $row_desc['name']; ?>
      <input name="id" type="hidden" id="id" value="<?php echo $row_desc['id']; ?>" />
    </b></td>
  </tr>
  <tr>
    <td width="10%">Описание:</td>
    <td width="90%"><textarea name="ptext" cols="50" rows="10" id="ptext"><?php echo $row_desc['ptext']; ?></textarea><br>
      <input name="Button" type="button" onClick="MM_openBrWindow('/editor/newseditor.php?field=ptext&form=edit','editor','','595','500','true')" value="HTML-редактор" class="but"></td>
  </tr>
  <tr>
    <td colspan="2" align="center">
    <br>
    <input type="submit" name="Submit" value="Сохранить" class="but" />
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
      <input type="button" name="Submit2" value="Закрыть" onClick="window.close()" class="but"></td>
  </tr>
  <input type="hidden" name="MM_update" value="edit">
</form>
</table>
</body>
</html>
<?php
mysql_free_result($desc);
?>
