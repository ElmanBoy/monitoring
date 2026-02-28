<script src="http://<?=$_SERVER['SERVER_NAME']?>/editor/editor.js" language="JavaScript"></script>
<script src="http://<?=$_SERVER['SERVER_NAME']?>/editor/colors.js" language="JavaScript"></script>
<script language="JavaScript">

function sendtext() {
//var htmlpage=remoteImportWordHTML(document.Add.NMH.value);
document.Add.text.value=document.getElementById("NMH").value; }//htmlpage;}

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

<table width="100" align="center" cellpadding="0" cellspacing="0" class="html_icons">
      <tr height="1" bgcolor="silver">
        <td colspan="26"></td>
      </tr>
      <tr height="28">
        <td width="22" nowrap align="center"><img src="/editor/img/save.gif" onClick="Save()" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Сохранить файл"> </td>
        <td width="22" nowrap align="center"><img src="/editor/img/print.gif" onClick="PrintPage()" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Печать"> </td>
        <td><img src="/editor/img/I.gif" alt="I.gif" border="0"> </td>
        <td width="22" nowrap align="center"><img src="/editor/img/cut.gif"   onClick="FormatText('cut')" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Вырезать"> </td>
        <td width="22" nowrap align="center"><img src="/editor/img/copy.gif"  onClick="FormatText('copy')" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Копировать"> </td>
        <td width="22" nowrap align="center"><img src="/editor/img/paste.gif" onClick="FormatText('paste'); cleanHTMLContent()" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Вставить"> </td>
        <td><img src="/editor/img/I.gif" alt="I.gif" border="0"> </td>
        <td width="22" nowrap align="center"><img src="/editor/img/preview.gif"  onClick="Preview()" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Предпросмотр"> </td>
        <td><img src="/editor/img/I.gif" alt="I.gif" border="0"> </td>
        <td width="22" nowrap align="center"><img src="/editor/img/undo.gif"  onClick="FormatText('Undo', '')" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Назад"> </td>
        <td width="22" nowrap align="center"><img src="/editor/img/redo.gif"  onClick="FormatText('Redo', '')" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Вперед"> </td>
        <td><img src="/editor/img/I.gif" alt="I.gif" border="0"> </td>
        <td width="22" nowrap align="center"><img src="/editor/img/wlink.gif"  onClick="OpenLink()" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Вставить ссылку"> </td>
        <td width="22" nowrap align="center"><img src="/editor/img/anchor.gif" alt="Вставить якорь" width="20" height="20" 
style="cursor: hand;"  onClick="MM_openBrWindow('/editor/anchor.php?path=<? echo $row_content['path']; ?>','anchor','','600','200','true')" onMouseOver="b(this)" onMouseOut="a(this)"> </td>
        <td width="22" nowrap align="center"><img src="/editor/img/paragraf.gif"  onClick="FormatText('InsertParagraph', 'false')" style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Новый абзац"> </td>
        <td width="22" nowrap align="center"><img src="/editor/img/br.gif"  onClick="AddHTML('<BR>')" style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Новая строка"> </td>
        <td width="22" nowrap align="center"><img src="/editor/img/hr.gif"  onClick="FormatText('InsertHorizontalRule', '')" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Горизонтальная полоса"> </td>
        <td width="22" nowrap align="center"><img src="/editor/img/image.gif" alt="Вставить картинку" 
style="cursor: hand;"  onClick="MM_openBrWindow('/editor/addimage.php','addimage','statusbar=no,scrollbars=yes,resizable=yes','750','700','true')" onMouseOver="b(this)" onMouseOut="a(this)"> </td>
        <td width="22" nowrap align="center"><img src="/editor/img/table.gif"  onClick="InsertTable()" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Вставить таблицу"> </td>
        <td><img src="/editor/img/I.gif" alt="I.gif" border="0"> </td>
        <td width="23" nowrap align="center"><img src="/editor/img/cleanword.gif"  onClick="cleanHTMLContent()" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" class="Im" alt="Очистить HTML код"> </td>
        <td width="23" nowrap align="center"><img src="/editor/img/code.gif"  onClick="Code = prompt('Введите HTML-код', ''); 	if ((Code != null) && (Code != '')){ AddHTML(Code); }" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" class="Im" alt="Вставить HTML код"> </td>
      </tr>
      <tr>
        <td colspan="22"><table cellpadding="0" cellspacing="0">
            <tr height=28>
              <td><select name="selectSize" title="Размер шрифта" onChange="FormatText('fontsize', selectSize.options[selectSize.selectedIndex].value);document.getElementById('selectSize').options[0].selected = true;" >
                  <option selected>-- Размер --</option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                  <option value="4">4</option>
                  <option value="5">5</option>
                  <option value="6">6</option>
                </select>
              </td>
              <td width="22" nowrap align="center"><img src="/editor/img/bold.gif"  onClick="FormatText('bold', '')" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Жирный шрифт"> </td>
              <td width="22" nowrap align="center"><img src="/editor/img/italic.gif"  onClick="FormatText('italic', '')" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Наклонный шрифт"> </td>
              <td width="22" nowrap align="center"><img src="/editor/img/under.gif"  onClick="FormatText('underline', '')" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Подчеркнутый шрифт"> </td>
              <td width="22" nowrap align="center"><img src="/editor/img/strike.gif"  onClick="FormatText('StrikeThrough', '')" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Перечеркнутый шрифт"> </td>
              <td width="22" nowrap align="center"><img src="/editor/img/fcolor.gif"  onClick="OpenColors()" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Цвет шрифта"> </td>
              <td><img src="/editor/img/I.gif" alt="I.gif" border="0"> </td>
              <td width="22" nowrap align="center"><img src="/editor/img/aleft.gif"  onClick="FormatText('JustifyLeft', '')" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Выравнивание по левому краю"></td>
              <td width="22" nowrap align="center"><img src="/editor/img/center.gif"  onClick="FormatText('JustifyCenter', '')" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Выравнивание по центру"></td>
              <td width="22" nowrap align="center"><img src="/editor/img/ashir.gif"  onClick="FormatText('JustifyFull', '')" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Выравнивание по ширине текста"></td>
              <td width="22" nowrap align="center"><img src="/editor/img/aright.gif"  onClick="FormatText('JustifyRight', '')" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Выравнивание по правому краю"></td>
              <td><img src="/editor/img/I.gif" alt="I.gif" border="0"> </td>
              <td width="22" nowrap align="center"><img src="/editor/img/blist.gif" onMouseOver="b(this)" onMouseOut="a(this)"  onClick="FormatText('InsertUnorderedList', '')" 
style="cursor: hand;" alt="Ненумерованный список"> </td>
              <td width="22" nowrap align="center"><img src="/editor/img/nlist.gif"  onClick="FormatText('InsertOrderedList', '')" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Нумерованный список"> </td>
              <td width="22" nowrap align="center"><img src="/editor/img/ileft.gif"  onClick="FormatText('Outdent', '')" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Уменьшить отступ"> </td>
              <td width="22" nowrap align="center"><img src="/editor/img/iright.gif"  onClick="FormatText('Indent', '')" 
style="cursor: hand;" onMouseOver="b(this)" onMouseOut="a(this)" alt="Увеличить отступ"> </td>
              <td><img src="/editor/img/I.gif" alt="I.gif" border="0"> </td>
              <td width="22" nowrap align="center"><img src="/editor/img/help.gif" alt="Помощь" 
style="cursor: hand;"  onClick="MM_openBrWindow('/editor/help.htm','help','scrollbars=yes,resizable=yes','600','600','true')" onMouseOver="b(this)" onMouseOut="a(this)"> </td>
            </tr>
        </table></td>
      </tr>
      <tr height="1" bgcolor="silver">
        <td colspan="30"></td>
      </tr>
      <tr height="1" bgcolor="silver">
        <td colspan="30"></td>
      </tr>
      <tr>
        <td colspan="20"></td>
      </tr>
</table>
    <table width="98%" align="center">
    <tr> 
      <td> 
        <script language="javascript">
			w=document.body.clientWidth-200; 
			h=document.body.clientHeight-200;
document.write ('<div id="Frm"><iframe src="/editor/textedit.php" id="message" width='+w+' height='+h+' style="bg" onpaste="cleanHTMLContent()" resize=yes></iframe></div><textarea name="NMH" id="NMH" style="width:'+w+'px;height:'+h+'px;display:none"></textarea>')
frames.message.document.designMode = "On";
frames.message.document.onpaste = cleanHTMLContent;
</script> <div></div>
<div id="im1"> <img src="/editor/img/Normal.gif" alt="Режим дизайна" name="Normal" width="108" height="17" border="0" usemap="#m_Normal"> 
          <map name="m_Normal">
            <area shape="poly" coords="59,1,56,8,59,15,99,15,105,3,104,1,59,1" href="javascript:ShowHTML();" alt="Показ HTML кода">
          </map>
        </div>
        <div id="im2" style="display:none"> <img src="/editor/img/HTML.gif" name="HTML" width="108" height="17" border="0" usemap="#m_HTML" alt="Показ HTML кода"> 
          <map name="m_HTML">
            <area shape="poly" coords="1,1,51,0,55,9,53,15,8,15,2,3,1,1" href="javascript:ShowNormal();"  alt="Режим дизайна">
          </map>
        </div>
      </td>
    </tr>
  </table>
 <input name="<?=$html_field?>" type="hidden" id="<?=$html_field?>" /> 
 <script language="javascript">
//onSubmit=SaveHTML();
//document.getElementById("<?=$html_field?>").value=document.getElementById("NMH").value;
</script>

