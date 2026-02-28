<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<title>Создание комментария</title>
<script language=Javascript>
function CheckText(){
	if(window.opener.frames.message.document.selection.createRange().text==''){
	alert("Сначала выделите текст, который должен стать комментарием.");
	window.close();
	}
}
function AddC() {
if((document.all.align.value=='')||(document.all.wid.value=='')){alert("Необходимо заполнить все поля"); }else{
	AnCode = '<div style="width:'+document.all.wid.value+'%; float:'+document.all.align.value+'; border:'+document.all.bord.value+'px '+document.all.bstyle.value+' '+document.all.bcolor.value+'; background-color:'+document.all.bgColor.value+'; color:'+document.all.fontColor.value+'; padding:10px; margin:10px">'+window.opener.frames.message.document.selection.createRange().text+'</div>';
 	var range = window.opener.frames.message.document.selection.createRange();
		range.pasteHTML(AnCode);
		range.select();
		range.execCommand();
		window.close();	}
}
var sInitColor = null; 
function callColorDlg(field, td){
	if (sInitColor == null) 
		var sColor = dlgHelper.ChooseColorDlg(); 
	else 
		var sColor = dlgHelper.ChooseColorDlg(sInitColor);	
	sColor = sColor.toString(16); 
	if (sColor.length < 6) { 
		var sTempString = "000000".substring(0,6-sColor.length); 
		sColor = sTempString.concat(sColor);
	} 
	document.execCommand("ForeColor", false, sColor); 
	sInitColor = sColor; 
	document.getElementById(field).value = sInitColor; 
	document.getElementById(td).style.backgroundColor = sInitColor;
	show();
}

function show(){
var s=document.getElementById("sample");
s.style.color=document.all.fontColor.value;
s.style.backgroundColor=document.all.bgColor.value;
s.style.borderWidth=document.all.bord.value;
s.style.borderStyle=document.all.bstyle.value;
s.style.borderColor=document.all.bcolor.value;
s.style.padding="10px";
}
</script>
<style>
#bogc{background-color:#999999;} 
#bgc{background-color:#EEEAD0;} 
#cf{background-color:#000000;} 
</style>
<link href="style.css" rel="stylesheet" type="text/css">
</head>
<body onLoad="CheckText();show()">
<div id="sample">Так будет выглядеть внешний вид комментария</div><br>
<table border="0" align="center" cellpadding="5" cellspacing="0" class="el_tbl">
  <tr>
    <td width="20%" align="right">Расположение:</td>
    <td width="80%"><select name="align" id="align">
      <option value="left" selected>Слева</option>
      <option value="center">По середине</option>
      <option value="right">Справа</option>
    </select>    </td>
  </tr>
  <tr>
    <td align="right">Ширина:</td>
    <td ><input name="wid" type="text" id="wid" value="100" size="4">
    %</td>
  </tr>
  <tr>
    <td align="right">Ширина рамки: <br></td>
    <td ><input name="bord" type="text" id="bord" value="1" size="2" onChange="show()" onKeyUp="show()"> 
    px </td>
  </tr>
  <tr>
    <td align="right">Стиль рамки: </td>
    <td ><select name="bstyle" id="bstyle" onChange="show()">
      <option value="solid">Сплошная</option>
      <option value="dashed" selected>Тире</option>
      <option value="dotted">Точки</option>
      <option value="outset">Выступающая</option>
      <option value="inset">Утопленная</option>
            </select></td>
  </tr>
   <tr>
    <td align="right">Цвет рамки: </td>
    <td ><table width="20" border="0" cellpadding="3" cellspacing="0">
        <tr>
          <td><input type="button" name="Button" value="Выбрать" class="but" onClick="callColorDlg('bcolor', 'bogc')"></td>
          <td bgcolor="#999999" id="bogc"><img src="/editor/img/spacer.gif" width="20" height="20"></td>
        </tr>
      </table>
    <input name="bcolor" type="hidden" id="bcolor" value="#999999" onChange="show()"></td>
  </tr>
  <tr>
  <tr>
    <td align="right">Цвет фона: </td>
    <td ><table width="20" border="0" cellpadding="3" cellspacing="0">
        <tr>
          <td><input type="button" name="Button" value="Выбрать" class="but" onClick="callColorDlg('bgColor', 'bgc')"></td>
          <td bgcolor="#EEEAD0" id="bgc"><img src="/editor/img/spacer.gif" width="20" height="20"></td>
        </tr>
      </table>
    <input name="bgColor" type="hidden" id="bgColor" value="#EEEAD0" onChange="show()"></td>
  </tr>
  <tr>
    <td align="right">Цвет шрифта: </td>
    <td ><table width="20" border="0" cellpadding="3" cellspacing="0">
        <tr>
          <td><input type="button" name="Button" value="Выбрать" class="but" onClick="callColorDlg('fontColor', 'cf')"></td>
          <td bgcolor="#000000" id="cf"><img src="/editor/img/spacer.gif" width="20" height="20"></td>
        </tr>
      </table>
      <input name="fontColor" type="hidden" id="fontColor" value="#000000" onChange="show()"></td>
  </tr>
  <tr>
    <td align="center"><input type="button" name="Button" value="Создать" class="but" onClick="AddC()"></td>
    <td align="center" ><input name="Button" type="button" class="but" onClick="window.close()" value="Закрыть"></td>
  </tr>
</table>
<OBJECT id=dlgHelper classid=clsid:3050f819-98b5-11cf-bb82-00aa00bdce0b name=dlgHelper VIEWASTEXT></OBJECT>
</body>
</html>
