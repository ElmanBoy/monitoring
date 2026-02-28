function FormatText(command, option){
  	frames.message.document.execCommand(command, false, option);
  	frames.message.document.focus();
}

function AddH(number){
	var range = frames.message.document.selection.createRange();
	var AnCode="";
	switch(number){
		case "1":AnCode="<H1>"+range.text+"</H1>"; break;
		case "2":AnCode="<H2>"+range.text+"</H2>"; break;
		case "3":AnCode="<H3>"+range.text+"</H3>"; break;
		case "4":AnCode="<H4>"+range.text+"</H4>"; break;
		case "5":AnCode="<H5>"+range.text+"</H5>"; break;
		case "6":AnCode="<H6>"+range.text+"</H6>"; break;
	}
	range.pasteHTML(AnCode);
	range.select();
	range.execCommand();
	range.focus();
}

function AddImage(imagePath){	
MM_openBrWindow('/editor/addimage.php','addimage','statusbar=no,scrollbars=yes,resizable=yes','800','700','true');
frames.message.document.focus();			
}

function ShowHTML(){
document.all.im2.style.display = '';
document.all.im1.style.display = 'none';
NewHTML = frames.message.document.body.innerHTML;
document.all.Frm.style.display = 'none';
document.getElementById("NMH").value = NewHTML;
document.getElementById("NMH").style.display = 'block';
document.getElementById("NMH").focus();
document.getElementById("im2").style.marginTop='-26px';
document.getElementById("publish").style.marginTop='5px';
}

function ShowNormal(){
document.all.im1.style.display = '';
document.all.im2.style.display = 'none';
NewHTML = document.getElementById("NMH").value;
document.getElementById("NMH").style.display = 'none';
frames.message.document.body.innerHTML = NewHTML;
document.all.Frm.style.display = 'block';
document.getElementById("im1").style.marginTop='0px';
document.getElementById("publish").style.marginTop='-15px';
}



function SaveHTML(){
	if(document.getElementById("NMH").style.display == 'block'){
	ShowNormal();
	ShowHTML()
	}else{
	ShowHTML();
	}
}

function a(obj){
obj.style.border = "none";
}

function b(obj){
obj.style.border = "1px Solid Gray";
}

function UPfile(){
document.all.MenuFile.style.display='';
HiddenS();
}

function DOWNfile(){
document.all.MenuFile.style.display='none';
ShowS();
}

function HiddenS(){
document.getElementById("Heading").style.visibility='hidden';
document.getElementById("selectFont").style.visibility='hidden';
}

function ShowS(){
document.getElementById("Heading").style.visibility='visible';
document.getElementById("Heading").style.visibility='visible';
}

function Preview(url){
	var board=window.open(url,"Preview"); 
	return board;
}

function Save(){
	board = Preview();
  	board.document.execCommand('SaveAs');
	board.window.close();
}

function PrintPage(url){
	board = Preview(url);
  	board.document.execCommand('Print');
	board.window.close();
}

function AddHTML(AnCode) {
	var range = frames.message.document.selection.createRange();
	if(range){
		try{
			range.pasteHTML(AnCode);
			range.select();
			range.execCommand();
			frames.message.document.focus();
		}catch(Error){
			alert("Выделите область для вставки элемента.");
			return false;
		}
	}
}
	
	function cleanHTMLContent() {
	if(document.getElementById("NMH").style.display == 'block'){ShowNormal()}
	var tmp=frames.message.document.body.innerHTML;
      tmp = tmp.replace(/<\?xml:.*?>/ig, "");

      tmp = tmp.replace(/<H[0-9]+\s?([^>]*)>/ig, "<p $1>");
      tmp = tmp.replace(/<\/H[0-9]+>/ig, "</p>");

      tmp = tmp.replace(/<TT([^>]*)>/ig, "<p $1>");
      tmp = tmp.replace(/<\/TT>/ig, "</p>");

      tmp = tmp.replace(/<\/?font[^>]*>/ig, "");
      tmp = tmp.replace(/<\/?span[^>]*>/ig, "");
      //tmp = tmp.replace(/<\/?a[^>]*>/ig, "");
      tmp = tmp.replace(/<\/?\w+:\w+[^>]*>/ig, "");

      tmp = tmp.replace(/<p\s*[^>]*>/ig, "<p>");
	tmp = tmp.replace(/<\/p>/ig, "</p>");

			tmp = tmp.replace(/\sclass=Mso\w*?\s/ig, " ");

      tmp = tmp.replace(/(style="[^"]*)TEXT-ALIGN:\s?([a-z]*)([^"]*")/ig, "align=$2 $1$3");
      tmp = tmp.replace(/(style="[^"]*)BACKGROUND:\s?([a-z0-9#]*)([^"]*")/ig, "bgcolor=$2 $1$3");

			tmp = tmp.replace(/\s(?:lang|style|class)\s*=\s*"[^"]*"/ig, " ");
      tmp = tmp.replace(/\s(?:lang|style|class)\s*=\s*'[^']*'/ig, " ");
      tmp = tmp.replace(/\s(?:lang|style|class)\s*=\s*[^\s>]*/ig, " ");

      tmp = tmp.replace(/(<\/?)dir>/ig, "$1blockquote>");

      tmp = tmp.replace(/(<td[^>]*>)\s*<p>([^<>]*)<\/p>\s*<\/td>/ig, "$1$2</td>");

			tmp2 = tmp.replace(String.fromCharCode(8216), "'").replace(String.fromCharCode(8217), "'").replace(String.fromCharCode(8220), '"').replace(String.fromCharCode(8217), '"').replace(String.fromCharCode(8211), "-");

      //return tmp2;
	  frames.message.document.body.innerHTML=tmp2;
	  alert('Код очищен!');
}
	
function getObjectParentTag (tname, startel) {
    found = false; error = false;
    toret = null;
    do {
        if (startel.tagName.toLowerCase() == tname) {
            found = true;
            toret = startel;
        }
        if (startel.tagName.toLowerCase() == "body" || !startel.parentElement || startel == null) {
			error = true;
		}
		if (found || error) {
			break;
		}
		startel = startel.parentElement;
	} while (1);
	return toret;
}

function showparam(){
var range = frames.message.document.selection.createRange();
var tableCell = getObjectParentTag("td", range.parentElement());
if(tableCell!=null){
document.Add.tblwidth.value=tableCell.getAttribute("width");
document.Add.tblh.value=tableCell.getAttribute("height");}
frames.message.document.execCommand("LiveResize", "false", "true");
}

function setparam(){
var range = frames.message.document.selection.createRange();
var tableCell = getObjectParentTag("td", range.parentElement());
	if(tableCell!=null){
		tableCell.setAttribute("width", document.Add.tblwidth.value);
		tableCell.setAttribute("height", document.Add.tblh.value);
		tableCell.setAttribute("align", document.Add.alignfield.value);
		tableCell.setAttribute("valign", document.Add.valignfield.value);
	}	
}

function OpenLink() {
	var newWindowFeatures="dependent=1, scrollbars=yes, Height=520,Width=700"; 
	var board=window.open("/editor/link.php","InsertLinks",newWindowFeatures);
}

function add_comm() {
	var newWindowFeatures="dependent=1,Height=400,Width=300"; 
	var board=window.open("/editor/addcomm.php","InsertLinks",newWindowFeatures);
}

function InsertTable() {
	var newWindowFeatures="dependent=1,Height=600,Width=400, scrollbars=yes"; 
	var board=window.open("/editor/addtable.php","InsertLinks",newWindowFeatures);

}