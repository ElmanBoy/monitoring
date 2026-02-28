<?
error_reporting(0);
$puti=str_replace('//', '/', $_GET['puti']);
$stek=$_GET['stek'];
define('IMAGE', $_GET['image']);
define('LINK', $_GET['link']); 
?>
var answer;

function MM_displayStatusMsg(msgStr) { 
  status=msgStr;
  document.MM_returnValue = true;
}

function a(obj){
	obj.style.backgroundColor = "";
}

function b(obj){
	obj.style.backgroundColor = "#E0EBF5";
}

var codeid;
function MM_goToURL() { 
  var i, args=MM_goToURL.arguments; document.MM_returnValue = false;
  for (i=0; i<(args.length-1); i+=2) eval(args[i]+".location='"+args[i+1]+"'");
}
function hlight(id){
    if(codeid!=id){
        document.getElementById(id).style.backgroundColor="#E7E7E7";
        document.getElementById(id).style.color="#000000";
	}
}
function hlightc(id, name){
    if(codeid!=id){
        for(i=0; i<document.getElementById("list").getElementsByTagName("tr").length; i++){
            document.getElementById(i).style.backgroundColor="";
            document.getElementById(i).style.color="#000000";
        }
        if(document.getElementById("list").getElementsByTagName("tr").length>0){	
        document.getElementById(id).style.backgroundColor="#436173";
        document.getElementById(id).style.color="#ffffff";
        document.getElementById("del_button").title="Удалить файл \""+name+"\"";
        document.getElementById("rn_button").title="Переименовать файл \""+name+"\"";
        document.getElementById("icut").style.visibility="visible";
        document.getElementById("icut").title="Вырезать файл \""+name+"\"";
        document.getElementById("icopy").style.visibility="visible";
        document.getElementById("icopy").title="Копировать файл \""+name+"\"";
        document.getElementById("ipaste").style.visibility="hidden";
        document.act_form.name.value=name;
        document.act_form.action="#"+id;
        codeid=id;
        }
    }else{
        if(document.getElementById("list").getElementsByTagName("tr").length>0){	
        document.getElementById(id).style.backgroundColor="";
        document.getElementById(id).style.color="#000000";
        document.getElementById("icut").style.visibility="hidden";
        document.getElementById("icopy").style.visibility="hidden";
        document.getElementById("ipaste").style.visibility="hidden";
        document.getElementById("del_button").style.visibility="hidden";
        document.getElementById("rn_button").style.visibility="hidden";
        codeid="";
        }
    }
	document.getElementById("foldrn").style.visibility="hidden";
	document.getElementById("folddel").style.visibility="hidden";
}

function hlightcf(id, name){
    if(codeid!=id){
        for(i=0; i<document.getElementById("list").getElementsByTagName("tr").length; i++){
            document.getElementById(i).style.backgroundColor="";
            document.getElementById(i).style.color="#000000";
        }
        if(document.getElementById("list").getElementsByTagName("tr").length>0){	
        document.getElementById(id).style.backgroundColor="#436173";
        document.getElementById(id).style.color="#ffffff";
        document.getElementById("foldrn").style.visibility="visible";
        document.getElementById("foldrn").title="Переименовать папку \""+name+"\"";
        document.getElementById("folddel").style.visibility="visible";
        document.getElementById("folddel").title="Удалить папку \""+name+"\"";
        document.getElementById("icut").style.visibility="visible";
        document.getElementById("icut").title="Вырезать папку \""+name+"\"";
        document.getElementById("icopy").style.visibility="visible";
        document.getElementById("icopy").title="Копировать папку \""+name+"\"";
        if(document.act_form.pastefile.value!=''){
        document.getElementById("ipaste").style.visibility="visible";}
        document.act_form.name.value=name;
        document.act_form.action="#"+id;
        codeid=id;
        }
    }else{
        if(document.getElementById("list").getElementsByTagName("tr").length>0){	
        document.getElementById(id).style.backgroundColor="";
        document.getElementById(id).style.color="#000000";
        document.getElementById("foldrn").style.visibility="hidden";
        document.getElementById("folddel").style.visibility="hidden";
        document.getElementById("icut").style.visibility="hidden";
        document.getElementById("icopy").style.visibility="hidden";
        document.getElementById("ipaste").style.visibility="hidden";
        codeid="";
        }
    }
	document.getElementById("del_button").style.visibility="hidden";
	document.getElementById("rn_button").style.visibility="hidden";


}


function uligh(id){
    if(codeid!=id){
        document.getElementById(id).style.backgroundColor="";
        document.getElementById(id).style.color="#000000";
	}
}

function check(obj){
    var OK=confirm('Вы действительно хотите удалить файл "'+obj+'" ?');
    if (OK) {return true} else {return false}
}

function abut(td){
    document.getElementById(td).className='downbutton';
}

function but(td){
    document.getElementById(td).className='upbutton';
}

var acf=document.act_form;

function new_fold(){
    if(document.act_form.newname.value=prompt('Введите название будущей папки', '')){
        document.act_form.act.value="new_folder";
        document.act_form.submit();
    }
}

function rn_fold(){
    var oldname=document.act_form.name.value;
    if(document.act_form.newname.value=prompt('Введите новое название папки '+oldname, oldname)){
        document.act_form.act.value="rename_folder";
        document.act_form.submit();
    }
}

function del_fold(){
    var name=document.act_form.name.value;
    var OK=confirm('Вы действительно хотите удалить папку "'+name+'" и все ее содержимое ?');
    if (OK) {
        document.act_form.act.value="delete_folder";
        document.act_form.submit();
    }
}

function rn_file(){
    var oldname=document.act_form.name.value;
    if(document.act_form.newname.value=prompt('Введите новое название файла '+oldname, oldname)){
        document.act_form.act.value="rename_file";
        document.act_form.submit();
    }
}

function check_name(){
    var count=document.getElementById("upfile").value+1;
    
    for(c=0; c<count; c++){
    file_name=document.getElementById("file_"+c).value.split('\\');
    file_c=file_name.length;
    compare=0;
        for(i=0; i<document.getElementById("list").getElementsByTagName("tr").length; i++){
            if(file_name[file_c-1]==document.getElementById("fdname1_"+i).innerText){
                if(confirm('Файл с названием '+file_name[file_c-1]+' уже есть!\nХотите перезаписать его?')){
                    compare=0;
                }else{
                    compare++;
                }
            }
        }
        if(compare>0){
            return false;
        }else{
            return true;
        }
    }
}


function movethis(action){
	var i_paste=document.getElementById("ipaste");
	var actionn;
	switch (action){
		case 'cut'  :	document.act_form.pastefile.value='<?=$_SERVER['DOCUMENT_ROOT'].'/'.$stek.'/'?>'+document.act_form.name.value;
						document.act_form.cutcopy.value='cut';
						document.act_form.submit();
						break;
		case 'copy' :	document.act_form.pastefile.value='<?=$_SERVER['DOCUMENT_ROOT'].'/'.$stek.'/'?>'+document.act_form.name.value;
						document.act_form.cutcopy.value='copy';
						document.act_form.submit();
						break;
		case 'paste':
        if(document.act_form.cutcopy.value=='cut'){document.act_form.act.value="cut_file"}else{document.act_form.act.value="copy_file"}
						document.act_form.submit();
						break;
	}
}

function prewiev(imgsrce, width, height, ftime, type, repeat) {
    var f=document.getElementById('prewframe');
    switch (type){
    case "image":
    f.innerHTML='';
    w='';
    h='';
    if(width>200){
        wk="";
        wk=width/200;
        w=width/wk;
        h=height/wk;
    }else if(repeat!=1){
        w=width;
    }
    if(height>160){
        hk="";
        hk=height/160;
        h=height/hk;
        w=width/hk;
    }else if(repeat!=1){
        h=height;
    }
    if(w>200||h>160){
        prewiev(imgsrce, w, h, ftime, type, repeat=1);
    }
        
    f.innerHTML='<img src="<?=$puti?>/'+imgsrce+'" width="'+w+'" height="'+h+'" name="prew" border="0" id="prew" style="cursor:pointer" onClick="openPictureWindow_Fever(\''+type+'\',\'/images/CIMG0070.jpg\',\'300\',\'300\',\'Просмотр\',\'200\',\'100\')" title="Кликните по картинке для полноразмерного просмотра">';
    break;
    case "file":
    f.innerHTML='';
    f.innerHTML='<iframe src="/editor/img/spacer.gif" name="prew" frameborder="0" id="prew" width="100%" height="160" style="cursor:pointer"></iframe>';
    break;
    case "txt":
    f.innerHTML='';
    f.innerHTML='<iframe src="<?=$puti?>/'+imgsrce+'" name="prew" frameborder="0" id="prew" width="100%" height="160" style="cursor:pointer"></iframe>';
    break;
    
    case "swf":
    f.innerHTML='';
    w='';
    h='';
	if(width>200){
		wk="";
		wk=width/200;
		w=width/wk;
		h=height/wk;
	}else if(repeat!=1){
		w=width;
	}
	if(height>160){
		hk="";
		hk=height/160;
		h=height/hk;
		w=width/hk;
	}else if(repeat!=1){
		h=height;
	}
	if(w>200||h>160){
		prewiev(imgsrce, w, h, ftime, type, repeat=1);
	}

    f.innerHTML='<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0\" width=\"'+w+'\" height=\"'+h+'\"><param name=movie value="<?=$puti?>/'+imgsrce+'"><param name=quality value=high><embed src="<?=$puti?>/'+imgsrce+'" quality=high pluginspage=\"http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash\" type=\"application/x-shockwave-flash\" width=\"'+w+'\" height=\"'+h+'\"></embed></object>';
    break;
    }
    if(type=='image'||type=='swf'){
        pixel="Размер изображения: "+width+"x"+height+" пикселей<br>";
    }else{
        pixel="";
    }
    document.getElementById('info').innerHTML="<small>Файл: "+imgsrce+"<br>"+pixel+"Время последнего изменения: "+ftime+"</small>";
    document.getElementById("sorce").value=answer.replace('<?=$_SERVER['DOCUMENT_ROOT']?>', '')+'/'+imgsrce; 
    document.getElementById("imwidth").value=width;
    document.getElementById("imheight").value=height;
    document.getElementById("del_button").style.visibility="visible";
    document.getElementById("rn_button").style.visibility="visible";
    <?php if(LINK==1){?>
    document.getElementById("urltext").value="<?=$_SERVER['SERVER_NAME'].$puti?>/"+imgsrce;
    <?php }
    if(IMAGE==1){ ?>
    document.getElementById("imagename").value="<?=$_SERVER['SERVER_NAME'].$puti?>/"+imgsrce;
    <?php }?>
}

function openPictureWindow_Fever(imageType,imageName,imageWidth,imageHeight,alt,posLeft,posTop) {  
    var imageName=document.getElementById("sorce").value;
    var imageWidth=document.getElementById("imwidth").value;
    var imageHeight=document.getElementById("imheight").value;
    if(imageWidth==''){imageWidth=600}
    if(imageHeight==''){imageHeight=500}
        newWindow = window.open("","newWindow","width="+imageWidth+",height="+imageHeight+",scrollbars=no,left="+posLeft+",top="+posTop);
        newWindow.document.open();
    switch (imageType){
        case "swf":
        newWindow.document.write('<html><title>'+imageName+'</title><body bgcolor="#FFFFFF" style="margin: 0px 0px 0px 0px">'); 
        newWindow.document.write('<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0\" width=\"'+imageWidth+'\" height=\"'+imageHeight+'\">');
        newWindow.document.write('<param name=movie value="'+imageName+'"><param name=quality value=high>');
        newWindow.document.write('<embed src="'+imageName+'" quality=high pluginspage=\"http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash\" type=\"application/x-shockwave-flash\" width=\"'+imageWidth+'\" height=\"'+imageHeight+'\">');
        newWindow.document.write('</embed></object>');	
        break;
        
        case "image":
        newWindow.document.write('<html><title>'+imageName+'</title><body bgcolor="#FFFFFF" style="margin: 0px 0px 0px 0px;padding: 0px 0px 0px 0px;">'); 
        newWindow.document.write('<img src="'+imageName+'"  alt="Кликните по картинке, чтобы закрыть ее" style="cursor:pointer" onClick="self.close()">'); 
        break;
        
        case "file":
        case "txt" :
        newWindow.document.write('<html><body onload="window.close()"></body></html>');
        newWindow.location.href=imageName;
        break;
    }
	newWindow.document.write('</body></html>');
	newWindow.document.close();
	newWindow.focus();
}
<?php if(IMAGE!=1){ ?>
var c=0; 
function addline(){
	var table = document.getElementById('upFileTbl');
	var newTR=table.insertRow(0);
	newTR.setAttribute("id", "line_"+c);
	var newTD=newTR.insertCell(0);
	var newinput=document.createElement("input");
	newinput.setAttribute("id", "file_"+c);
	newinput.setAttribute("name", "file[]");
	newinput.setAttribute("type", "file");
    newTD.appendChild(newinput);
	var newButton=document.createElement("input");
	newButton.setAttribute("type", "button");
	newButton.setAttribute("value", "x");
	newButton.setAttribute("title", "Удалить");
	newButton.className="but";
	newButton.onclick=function(){return rmline(c);}
	var delButton=newTD.insertBefore(newButton, newinput);
	c++; 
}

function rmline(q){
    if (c<=0) return false; else c--;
	var table = document.getElementById('upFileTbl');
	var delLine=document.getElementById('line_'+(q-1));
	table.deleteRow(delLine.rowIndex);
}
<?php }?>

document.write('<script src="/js/JsHttpRequest/lib/JsHttpRequest/JsHttpRequest.js"></script>');

function getFileList(mode, sort, pdir, sorce){
    var div=document.getElementById('tblFileList');
    div.innerHTML='<img src="/editor/img/loading.gif" width="18" height="18" align=left> Пожалуйста, подождите...';
    var req = new JsHttpRequest();
    req.onreadystatechange = function() {
        if (req.readyState == 4) {
            div.innerHTML = req.responseText;
            answer=req.responseJS.answer; 
            document.getElementById("sorce").value=answer;
        }
    }
    req.open(null, '/editor/upfileList.php', true);
    req.send( { mode:mode, sort:sort, pdir:pdir, sorce:sorce } );
}