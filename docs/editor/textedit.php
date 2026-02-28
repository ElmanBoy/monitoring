<?php require_once('../Connections/dbconn.php'); ?>
<?PHP $requiredUserLevel = array(1, 2); 
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php"); ?>
<?php
$colname_text = "1";
if (isset($_GET['cat'])) {
  $colname_text = (get_magic_quotes_gpc()) ? $_GET['cat'] : addslashes($_GET['cat']);
}
mysql_select_db($database_dbconn, $dbconn);
$query_text = sprintf("SELECT text FROM content WHERE cat = %s", $colname_text);
$text = mysql_query($query_text, $dbconn) or die(mysql_error());
$row_text = mysql_fetch_assoc($text);
$totalRows_text = mysql_num_rows($text);
?>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<meta http-equiv="cache-control" content="no-cache">

<style type="text/css">
<!--
body{background-color:#FFFFFF; font-family:Tahoma; font-size:12px}
table, td {
	border: 1px dotted #999999;
}
body{padding:10px 10px 10px 10px}
.anchorlink{width:20px; height:20px; background-image:url(/editor/img/anchor.gif);}
-->
</style>
<?php 
echo $row_text['text']; 
mysql_free_result($text);
?>

