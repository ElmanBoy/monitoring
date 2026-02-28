var request;
var parent1;
function doLoad(parent, lev) {
	parent1=parent;
	url="menuchild.php?mode=child&parent="+parent+"&lev="+lev;
  if (window.XMLHttpRequest) {
    request = new XMLHttpRequest();
    request.onreadystatechange = processRequestChange;
    request.open("GET", url, true);
    request.send(null);
  } else if (window.ActiveXObject) {
    request = new ActiveXObject("Microsoft.XMLHTTP");
    if (request) {
      request.onreadystatechange = processRequestChange;
      request.open("GET", url, true);
      request.send();
    }
  }
}

function getRequestStateText(code) {
  switch (code) {
    case 0: return "Uninitialized."; break;
    case 1: return "Loading..."; break;
    case 2: return "Loaded."; break;
    case 3: return "Interactive..."; break;
    case 4: return "Complete."; break;
  }
}

ns4 = (document.layers)? true:false
ie4 = (document.all)? true:false

function init() {
	if (ns4) {document.captureEvents(Event.MOUSEMOVE);}
	document.onmousemove=mousemove;
}
function mousemove(e) {
	var X;
	var Y;
	var s=document.getElementById("status");
	if(!cursor) var cursor = window.event;
	if (cursor.pageX || cursor.pageY) {
    	X = cursor.pageX;
    	Y = cursor.pageY;
  	} else if (cursor.clientX || cursor.clientY) {
    	X = cursor.clientX + document.body.scrollLeft;
    	Y = cursor.clientY + document.body.scrollTop;
  	}
	s.style.top =(Y+15)+"px";	
	s.style.left =(X +15)+"px";
}

function processRequestChange() {
	init();
	var s=document.getElementById("status");
	var X;
	var Y;
	if(!cursor) var cursor = window.event;
	if (cursor.pageX || cursor.pageY) {
    	X = cursor.pageX;
    	Y = cursor.pageY;
  	} else if (cursor.clientX || cursor.clientY) {
    	X = cursor.clientX + document.body.scrollLeft;
    	Y = cursor.clientY + document.body.scrollTop;
  	}
	s.style.top =(Y+15)+"px";	
	s.style.left =(X +15)+"px";
	s.style.zIndex=100;
	s.style.display="block";
	//document.movediv;
  //document.getElementById("resultdiv").style.display = 'none';
  //document.getElementById("state").value = getRequestStateText(request.readyState);
  abortRequest = window.setTimeout("request.abort();", 10000);
  // если выполнен
  if (request.readyState == 4) {
    clearTimeout(abortRequest);
    //document.getElementById("statuscode").value = request.status;
   // document.getElementById("statustext").value = request.statusText;
    // если успешно
    if (request.status == 200) {
      //document.getElementById("resultdiv").style.display = 'block';
	  //alert(request.responseText);
      document.getElementById("ch"+parent1).innerHTML = request.responseText;
    } else {
      alert("Ќе удалось получить данные:\n" + request.statusText);
    }
    //document.getElementById("loading").style.display = 'none';
  }
  // иначе, если идет загрузка или в процессе - показываем слой "«агружаютс€ данные"
  else if (request.readyState == 3 || request.readyState == 1) {
    //document.getElementById("loading").style.display = 'block';
  }
  s.style.display="none";
}


