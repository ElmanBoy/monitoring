// JavaScript Document
function ae(obj){
obj.style.border = "none";
}

function be(obj){
obj.style.border = "1px Solid Gray";
}

function convertContent(str, mode){
	var out;
	var localPath=SsiteName+"\/editor\/e_modules\/template_code\.php\?([^\.]*)\.php";
	var loc=new RegExp(localPath, "igm");
	
	if(mode=='html'){
		out0=str.replace(/<div\s*id=module_conteiner_code_\d*\s*contentEditable=false\s*style="([^'>]*)"\s*name="([^<>]*)"\s*value="([^<>]*)"\s*>\s*<\/div>/igm, '\n<? el_$2$3?>\n'); 
		out13=out0.replace(/%20/igm, "");
		out12=out13.replace(/<div\s*id=editableZoneStart\s*contentEditable=true\s*style="([^'>]*)">\s*<div\s*contentEditable=false\s*style="([^'>]*)">\s*([^<>]*)\s*<\/div>/igm, "\n<!-- id=editableZoneStart $3 -->\n");
		out13=out12.replace(/<!--\s*id=editableZoneEnd\s*--><\/div>/igm, "\n<!-- id=editableZoneEnd -->\n");
		out=out13.replace(loc, "");
	}else if(mode=='design'){
		out9=str.replace(/<\?\s*el_([^<>(]*)([^<>;]*)\s*[^\?>]*\?>/igm, '<div id=module_conteiner_code contentEditable=false style="border:2px dotted green; width:100%" name="$1" value="$2"><div id=module_conteiner_control contentEditable=false style="background-image:url(\'/editor/img/plugin_edit.gif\'); background-repeat:no-repeat; height:25px; padding-top:5px; padding-left:25px; font-family:Tahoma; font-size:11px; font-weight:bold">Настройка компонента</div></div>'); 
		//out8=out9.replace(/\s*el_\s*/igm , "");
		out8=out9.replace(/%20/igm, "");
		out7=out8.replace(/<!--\s*id\s*=\S*(editableZoneStart)\S*([^-->]*)-->/igm, "<div id=editableZoneStart contentEditable=true style='BORDER: orange 2px dotted'><div contentEditable=false style='color:orange; font-size:12px; font-family:Arial; font-weight:bold; background-color:green; padding:3'>$2</div>");
		out=out7.replace(/<!--\s*id\s*=\S*(editableZoneEnd)\S*([^-->]*)-->/igm, "<!-- id=editableZoneEnd --></div>");
	}
	out2=out.replace(/(&#13;&#10;|\?>\s*<\?)/ig, "?>\n<? ");
	out1=out2.replace(/&#9;/ig, '');
	out=out1.replace(/<\?\s*\?>/ig, ''); 
	//alert(out);
	return out;
}

var noDisable=0;
var mouseX;
var mouseY;
var selObjX;
var selObjY;
var selObjW;
var selObjH;
function getElementPosition(elemId){
    var elem = frames.message.window.document.getElementById(elemId);
	
    var w = elem.offsetWidth;
    var h = elem.offsetHeight;
	
    var l = 0;
    var t = 0;
	
    while (elem){
        l += elem.offsetLeft;
        t += elem.offsetTop;
        elem = elem.offsetParent;
    }
	selObjX=l;
	selObjY=t;
	selObjW=w;
	selObjH=h;
   return {"left":l, "top":t, "width": w, "height":h};
}


function getMouseXY(){
	// browser specific
	if(window.ActiveXObject){
		mouseX = frames.message.window.event.clientX + frames.message.window.document.body.scrollLeft;
		mouseY = frames.message.window.event.clientY + frames.message.window.document.body.scrollTop;
	}else{
		mouseX = e.pageX;
		mouseY = e.pageY; window.status=mouseY;
	}
}


function getSelectedObj(mode){
	if(mode=="click"){
		var selObj=frames.message.document.selection;
		var oControlRange = selObj.createRange();
		return oControlRange(0);
	}else if(mode=="over"){
		getMouseXY();
		var elem=frames.message.document.body.getElementsByTagName("DIV");
		for(i=0; i<elem.length; i++){
			if(elem[i].id){
				getElementPosition(elem[i].id);
				if((mouseX<=selObjX+selObjW && mouseX>=selObjX) && (mouseY>=selObjY && mouseY<=selObjY+selObjH) && (elem[i].id.indexOf("module_conteiner_code")>=0 || elem[i].id.indexOf("module_conteiner_control")>=0 )){
					return elem[i];	
				}
			}
		}
	}
}

var selectedModule;
function getModuleParam(){ 
	if(frames.message.event){
		var module=frames.message.event.srcElement;
	}else{
		var module=	selectedModule;//frames.message.event.srcElement;
	}
	if ((module.tagName == "DIV") && module.id.indexOf("module_conteiner_code")>=0){
		var params = module.value;
		var moduleName = module.name;
		var myLeft = (screen.width-400)/2;
		var myTop = (screen.height-500)/2;
		var paramWindow=window.open("moduleProp.php?params="+params+"&module="+moduleName, "paramWindow", "height=500,width=400,status=no,toolbar=no,menubar=no,resizable=yes,scrollbars=yes,left="+myLeft+",top="+myTop);
	}
}


function getModuleParamFromControl(){
	var selObj=frames.message.document.selection;
	var oControlRange = selObj.createRange();
	var num;
	if(oControlRange(0).tagName == "DIV" && oControlRange(0).id.indexOf("module_conteiner_control")>=0){
		num=oControlRange(0).id.replace("module_conteiner_control_", "");
		selectedModule=frames.message.document.getElementById("module_conteiner_code_"+num).focus();
		getModuleParam();
	}
}

function cleanModuleDivs(){
	var elem=frames.message.document.body.getElementsByTagName("DIV");
	for(i=0; i<elem.length; i++){
		if(elem[i].id.indexOf("module_conteiner_code")>=0){
			elem[i].innerHTML="";
		}
	}
	return true
}

function getNewVal(module, params){
	selectedModule.name=module;
	selectedModule.value=params;
	//drawModule();
	tempSelObj="";
	//selectedModule.blur();
}

function MySaveHTML(){
	if(document.getElementById("NMH").style.display == 'none'){
		MyShowHTML(1);
	}
}


function fileDialog(mode){
	if(mode=="saveas"){
		var file=showModalDialog("template_FileDialog.php", "", "dialogHeight:450px; dialogWidth:350px; status:no; resizable:yes");
		if(file){
			Add.tempName.value=file[0];
			Add.fileName.value=file[1];
			MySaveHTML();
			Add.submit();
		}
	}else if(mode=="new" || mode=="open"){
		var file=showModalDialog("template_FileDialog.php?mode="+mode, "", "dialogHeight:350px; dialogWidth:350px; status:no; resizable:yes");
		if(file){
			location.href="template_editor.php?mode=new&id="+file[1];
		}
	}else if(mode=="save"){
		var OK=confirm("Вы уверены, что хотите перезаписать существующий файл?");
		if(OK){
			MyShowHTML(1);
			Add.submit();
		}
	}
}

var allowProgress=0;
function MyShowHTML(obj){
	var ip=document.getElementById("icons_panel");
	if(cleanModuleDivs()){
		NewHTML = frames.message.document.body.innerHTML;
		document.all.Frm.style.display = 'none';
		document.all.Prev.style.display = 'none';
		document.getElementById("modul_table").style.display='none';
		document.getElementById("NMH").value = convertContent(NewHTML, 'html');
	}
	//ip.style.display="none";
	document.getElementById("NMH").style.display = 'block';
	document.getElementById("NMH").focus();
	var bt=document.getElementById("botTabs");
	var ul=bt.children(0);
	var li=ul.children;
	for(i=0; i<li.length; i++){
		li[i].className='';	
	}
	document.getElementById(obj).className='current';
	document.getElementById(obj).blur();
	document.getElementById("publish").style.marginTop='-15px';
	apCurrWidth=1;
	pEnd=1;
	aiter=0;
	allowProgress=0;
	drowMod="";
}

function MyShowNormal(obj){
	var ip=document.getElementById("icons_panel");
	var NewHTML = document.getElementById("NMH").value;
	document.all.Prev.style.display = 'none';
	document.getElementById("NMH").style.display = 'none';
	ip.style.display="block";
	document.getElementById("modul_table").style.display='block';
	frames.message.document.body.innerHTML = convertContent(NewHTML, 'design');
	document.all.Frm.style.display = 'block';
	var bt=document.getElementById("botTabs");
	var ul=bt.children(0);
	var li=ul.children;
	for(i=0; i<li.length; i++){
		li[i].className='';	
	}
	document.getElementById(obj).className='current';
	document.getElementById(obj).blur();
	document.getElementById("publish").style.marginTop='-15px';
	allowclick();
	allowProgress=0;
}

function MyShowPreview(obj){
	document.Add.target="messagePrev";
	document.Add.action="template_view.php";
	MySaveHTML();
	document.Add.submit();
	document.getElementById("NMH").style.display = 'none';
	document.getElementById("modul_table").style.display='none';
	document.getElementById("icons_panel").style.display="none";
	document.all.Frm.style.display = 'none';
	document.all.Prev.style.display = 'block';
	var bt=document.getElementById("botTabs");
	var ul=bt.children(0);
	var li=ul.children;
	for(i=0; i<li.length; i++){
		li[i].className='';	
	}
	document.getElementById(obj).className='current';
	document.getElementById(obj).blur();
	document.getElementById("publish").style.marginTop='-15px';
	document.Add.target="";
	document.Add.action="";
	allowProgress=1;
}

function checkLoad(){
	if(frames.message.document.body){
		MyShowHTML(2);
		MyShowNormal(1);
		window.clearInterval(timeLoad); 
		timeLoad="";
	}
}

function MySaveHTML(){
	if(document.getElementById("NMH").style.display == 'block'){
		MyShowNormal(1);
		MyShowHTML(2)
	}else{
		MyShowHTML(2);
		MyShowNormal(1);
	}
	allowProgress=1;
}


var oPopup = window.createPopup();
function setModuletitle(){
	var oPopupBody = oPopup.document.body;
	oPopupBody.style.backgroundColor = "lightyellow";
	oPopupBody.style.border = "solid gray 1px";
	oPopupBody.style.margin="5";
    oPopupBody.innerHTML = "<span style='font-family:Arial; font-size:11px'>Двойной клик - настройка компонента<br>Правая кнопка мыши - контекстное меню</span>";
    oPopup.show(frames.message.window.event.clientX, (frames.message.window.event.clientY+20), 230, 40, document.body);
}

function hideModuletitle(){
	oPopup.hide();
	if(tempSelObj){
		tempSelObj.innerHTML=tempInnerHTML;
		tempInnerHTML="";
		tempSelObj="";
	}
}

var keyEdit=0;
function findEditeble(){
	var com=frames.message.document.body.getElementsByTagName("!");
	for(a=0; a<com.length; a++){
		if(com[a].text.search(/id\s*=\s*'\s*editebleZone\s*'/)!=-1){
			contText=com[a].text;
			alert(contText+"\nid="+contText.search(/id\s*=\s*'\s*editebleZone\s*'/));	
			contText=contText.replace("<!--", "<div style='border:2px dotted blue'>"); 
			keyEdit=1;
		}else{
			if(a==com.length-1 && keyEdit==0){
				//findEditeble(com[a]);
				//alert("End tag");
			}
		}
	}
}

function noResize(){
	alert("Размеры этого объекта нельзя изменять");
	if(selectedModule){
		selectedModule.blur();
	}else{
		frames.message.document.selection.empty();
	}
	return false;
}

function getModuleName(mName){
	var out="";
	switch (mName){
		case "menu": out="Меню"; break;	
		case "text": out="Текст"; break;	
		case "calend": out="Календарь"; break;	
		case "module": out="Модули"; break;
		case "counter": out="Счетчик"; break;	
		case "anons": out="Анонсы"; break;	
		case "polls": out="Опросы"; break;	
	}
	return out;
}

var maxID;
var drowMod;
function allowclick(){
	var div=frames.message.document.body.getElementsByTagName("DIV");
	for(d=0; d<div.length; d++){
		if(div[d].id.indexOf("module_conteiner_code")>=0){
			addEvents(div[d]);
			div[d].id="module_conteiner_code_"+d;
			
			var mName=getModuleName(div[d].name);
			if(div[d].children.length>0){
				div[d].children(0).id="module_conteiner_control_"+d;
			}
			if(drowMod && drowMod.indexOf("module_conteiner_code")>=0){
				if(drowMod==div[d].id){
					if(div[d].children.length>0){
						div[d].children(0).innerHTML="<font color=gray>Компонент \""+mName+"\"</font>";
					}
					sendMessage(div[d].name, div[d].value, div[d].id);
					requestNewMessages();
				}
			}else{
				div[d].children(0).innerHTML="<font color=gray>Компонент \""+mName+"\"</font>";
				sendMessage(div[d].name, div[d].value, div[d].id);
				requestNewMessages();
			}
			div[d].contentEditable=false;
			
		}
		if(div[d].id.indexOf("editableZoneStart")>=0){
			div[d].contentEditable=true;
		}
		maxID=d;
	}
	findEditeble();
	
}

function reDrawModule(){
	selectedModule.innerHTML=tempInnerHTML;
	getNewVal(selectedModule.name, selectedModule.value);
	drawModule(); 
}

function clearIntoModule(){
	selectedModule.innerHTML=tempInnerHTML;
}

function setCursor(){
	frames.message.document.body.style.cursor="pointer";		
}

function hideCursor(){
	frames.message.document.body.style.cursor="default";
}

function noAction(){
	return false;	
}

function drawModule(){
	window.setTimeout("drawModuleNow()", 500);	
}

function drawModuleNow(){

	var selObj=frames.message.document.selection;
	if(selObj.length>0){
		var obj1 = selObj.createRange();
		var obj=obj1(0); 
	}else{
		var obj=selectedModule; 
	}
	var num=0;
		num=obj.id.replace("module_conteiner_code_", "");
		obj.id="module_conteiner_code_"+num;
		obj.contentEditable=true;
		addEvents(obj);
		sendMessage(obj.name, obj.value, obj.id);
		requestNewMessages();
		obj.contentEditable=false;
}

function addEvents(obj){
	with (obj){
		attachEvent("onmousedown", disableEdit);
		attachEvent("ondblclick", getModuleParam);
		attachEvent("oncontextmenu", displayMenu);
		attachEvent("oncontextmenu", noAction);
		attachEvent("onmouseover", setModuletitle);
		attachEvent("onmouseout", hideModuletitle);
		attachEvent("onresizestart", noResize);
		attachEvent("ondragend", allowclick);
		attachEvent("ondragstart", dragModule);
		attachEvent("ondragend", reDrawModule);
	}
	if(obj.children.length>0){
		with(obj.children(0)){
			attachEvent("onresizestart", noResize);
			attachEvent("ondragend", drawModule);
			attachEvent("oncontextmenu", noAction);
		}
	}
}

function addNewModule(obj){
	modLayer.style.display="none";
	window.setTimeout("newModule(\""+obj.name+"\", \""+obj.value+"\")", 100);
}

function newModule(name, value){
	var selObj=frames.message.document.selection;
	var oControlRange = selObj.createRange();
	if(oControlRange.length>0){
		var AnCode='<div id=module_conteiner_code_'+maxID+' contentEditable=false style="border:2px dotted green; width:100%" name="'+name+'" value="'+value+'"><div id=module_conteiner_control contentEditable=false style="background-image:url(\'/editor/img/plugin_edit.gif\'); background-repeat:no-repeat; height:25px; padding-top:5px; padding-left:25px; font-family:Tahoma; font-size:11px; font-weight:bold"><font color=gray>Подготовка компонента...</font></div></div>';
		if(oControlRange.item(0).id!="modul_panel"){
			try{
				oControlRange(0).outerHTML=AnCode;
				var ob=frames.message.document.getElementById("module_conteiner_code_"+maxID);
				if(ob){
					ob.focus();
					selectedModule=ob;
					getModuleParam();
					maxID++;
				}
			}catch(Error){
				oControlRange(0).outerHTML='';
			}
		}
	}else{
		return false;
	}
}


function drawNewModule(obj){
	with (modLayer){
		style.display="block";
		style.backgroundImage="url('"+obj.src+"')";
		style.filter="Alpha(opacity=40)";
		style.posLeft=window.event.clientX-20;
		style.posTop=window.event.clientY+15;
	}
	
}

var tempSelObj;
var tempInnerHTML;
function disableEdit(){
	if(frames.message.event){
		var elem=getSelectedObj("over");
	}else{
		var elem=selectedModule;	
	}
	var d;
	if(elem){
		if(elem.id.indexOf("module_conteiner_code")>=0){
			var w=selObjW;
			var h=selObjH;
			d=elem.id.replace("module_conteiner_code_", "");
			if(!tempSelObj){
				tempInnerHTML=elem.innerHTML;
				tempSelObj=elem;
				elem.innerHTML="<div onclick='displayMenu()' id=module_conteiner_control_"+d+" contentEditable=false style=\"background-image:url('/editor/img/plugin_edit.gif'); background-repeat:no-repeat; height:25px; padding-top:5px; padding-left:25px; font-family:Tahoma; font-size:11px; font-weight:bold\">Нередактируемая область</div><div id=module_conteiner_code_"+d+" name=\""+elem.name+"\" value=\""+elem.value+"\" style='background-image:url(/editor/img/shading.gif); BACKGROUND-REPEAT: repeat; width:"+w+"px; height:"+h+"px; font-family:Arial; font-size:11px'><center>Здесь показано как приблизительно<br> будет работать компонент,<br> но редактировать это содержимое нельзя.<br>Настроить компонент можно дважды кликнув<br>по этой области или вызвав контекстное меню правой кнопкой мыши.</center></div>";
				with (elem){
					attachEvent("ondblclick", getModuleParam);
					attachEvent("oncontextmenu", displayMenu);
					attachEvent("oncontextmenu", noAction);
					attachEvent("ondragend", allowclick);
					attachEvent("ondragstart", dragModule);
					focus();
				}
			}
		}
	}
}

function dragModule(){
	/*apCurrWidth=1;
	pEnd=1;
	aiter=0;
	allowProgress=0;*/
	selectedModule=	frames.message.event.srcElement;
	//selectedModule=selectedModule.cloneNode(true);
	drowMod=selectedModule.id; 
}

function createEditebleZone(){
	var obj=frames.message.document.selection.createRangeCollection();
	if(obj.length>0){ 
		var html=obj.item(0).outerHTML;
		var name=prompt("Дайте название новой зоне\nВ названии не должно быть цифр\n", "");
		if(name!="" && name!=null){
			if(name.search(/(\d+)/ig)==-1){
				AnCode = "<div id=editebleZoneStart style='BORDER: orange 2px dotted'><div style='color:orange; font-size:12px; font-family:Arial; font-weight:bold; background-color:green; padding:3'>"+name+"</div>"+html+"<!-- id=editebleZoneEnd --></div>";
				obj.item(0).outerHTML=AnCode;
			}else{
				alert("Цифры в названии редактируемой зоны недопустимы.");
				createeditebleZone();
			}
		}
	}else{
		alert("Сначала выделите будущую зону!");	
	}
}

function displayMenu() {
   whichDiv=frames.message.event.srcElement;
   menu1.style.leftPos+=10;
   menu1.style.posLeft=frames.message.event.clientX+13;
   menu1.style.posTop=frames.message.event.clientY+76;
   menu1.style.display="block";
   menu1.setCapture();
   selectedModule=whichDiv;
   return false;
}
function switchMenu() {   
   el=event.srcElement;
   if (el.className=="menuItem") {
      el.className="highlightItem";
   } else if (el.className=="highlightItem") {
      el.className="menuItem";
   }
}

function hideMenu(){
	window.setTimeout("clickMenu()", 5000);
}

function clickMenu() {
   menu1.releaseCapture();
   menu1.style.display="none";
   if(event){
	   el=event.srcElement;
	   if (el.id=="mnuProps") {
		  selectedModule=tempSelObj;
		  getModuleParam();
	   } else if (el.id=="mnuDel") {
		  tempSelObj.removeNode(true);   
	   } else if (el.id=="mnuMove") {
		  alert("Возьмите курсором компонент за его границу и перетаскивайте.");
		  whichDiv.focus();   
	   }
   }
}