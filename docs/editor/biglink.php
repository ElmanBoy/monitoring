<?php require_once('../Connections/dbconn.php'); ?>
<?php
mysql_select_db($database_dbconn, $dbconn);
$query_pages = "SELECT id, cat, `path`, caption FROM content ORDER BY cat ASC";
$pages = mysql_query($query_pages, $dbconn) or die(mysql_error());
$row_pages = mysql_fetch_assoc($pages);
$totalRows_pages = mysql_num_rows($pages);
?>
<html> 
<head>
<title>Вставка гиперссылки</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<style type="text/css">
<!--
body {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 12px;
}
-->
</style>
<link href="style.css" rel="stylesheet" type="text/css">
</head>
<script language=Javascript>
var itemsrc;
var itemsrc1;
function prewiev() {
itemfile=window.document.all.select.options[window.document.all.select.selectedIndex].text;
itemsrc="../images/"+itemfile;
itemsrc1='images/'+itemfile;
document.getElementById('prew').src=itemsrc;
imagePath=itemsrc;
document.all.imgpath.value=itemsrc1;
}
function AddLink() {
var adres;
if(document.getElementById("pagesw").checked==true){
 if (document.all.Path.value=="") {adres=document.all.pagesurl.value}
else {adres=document.all.Path.value};}else{
 adres = '/'+document.all.imgpath.value;}
 	window.opener.form1.biglink.value=adres;
	//window.opener.getElementById("linktext").innerHTML="";
	//window.opener.getElementById("linktext").innerHTML=adres;
	window.close();
}
function showform(){
if(document.getElementById("pagesw").checked==true){
document.getElementById("page").style.display="block";document.getElementById("image").style.display="none"}else{
document.getElementById("page").style.display="none";document.getElementById("image").style.display="block"}
}
</script> 
<body bgcolor="#FFFFFF" leftmargin=0 topmargin=0 marginwidth="0" marginheight="0">
<table width=100%>
  <tr><td width="12%">Тип:</td><td width="88%"><select name=Protocol>
        <option value=>Другой</option>
        <option value="http://"selected>http://</option>
        <option value="file://">file://</option> 
        <option value="ftp://">ftp://</option> 
        <option value="https://">https://</option> 
        <option value="mailto:">mailto:</option>
        <option value="gopher://">gopher://</option> 
        <option value="news:">news:</option>
        <option value="telnet:">telnet:</option>
        <option value="wais:">wais:</option>
      </select></td></tr><tr>
        <td rowspan="2" valign="top">
        <table>
        <tr>
          <td><nobr><label>
            <input type="radio" id="pagesw" name="content" value="page" onClick="showform()">
            Станица</label></nobr></td>
        </tr>
        <tr>
          <td><nobr><label>
            <input name="content" id="imagesw" type="radio" onClick="showform()" value="image" checked>
            Изображение</label></nobr></td>
        </tr>
      </table>
    </td><td height="10"><div id="page" style="display:none;"><table border="0" id="page">
          <tr>
            <td> <input size=20 name=Path>
                <br>
      Если ссылка ведет на внутреннюю страницу, то можно ее просто выбрать из списка ниже.<br>
            <select name="pagesurl" size="10" id="pagesurl">
              <?php
do {  
?>
              <option value="<?php echo $row_pages['path']?>"><?php echo $row_pages['caption']?></option>
              <?php
} while ($row_pages = mysql_fetch_assoc($pages));
  $rows = mysql_num_rows($pages);
  if($rows > 0) {
      mysql_data_seek($pages, 0);
	  $row_pages = mysql_fetch_assoc($pages);
  }
?>
                      </select></td>
          </tr>
        </table></div></td>
      </tr><tr>
    <td height="10"><div  id="image" style="display:block;"><table width="100%"  border="0" cellspacing="0" cellpadding="1" >
      <tr>
        <td valign="top">
          <select name="select" size="12" onChange="prewiev()">
            <?
  $d=dir("../images/");
  while($entry=$d->read()) {
  if ($entry!="." && $entry!="..") {
  echo "<option value=$entry>$entry</option>"; } }
  $d->close();
  ?>
          </select>
          <input name="imgpath" type="hidden">
          <br>
        </td>
        <td valign="top">
          <iframe src="../images/spacer.gif" name="prew" id="prew" width="300" height="200" frameborder="0"> </iframe>
        </td>
      </tr>
    </table></div></td>
  </tr></table>
<center><input type=button value=Установить OnClick="AddLink()">
&nbsp;&nbsp;&nbsp;&nbsp;
<input type="button" name="Button" value="Закрыть" onClick="window.close()">
</center>
</body> 
</html>
<?php
mysql_free_result($pages);
?>
