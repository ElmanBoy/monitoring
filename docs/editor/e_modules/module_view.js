var xmlHttpGetMessages =  new JsHttpRequest();//createXmlHttpRequestObject();
/* variables that establish how often to access the server */
var updateInterval = 1000; // how many miliseconds to wait to get new
// when set to true, display detailed error messages
var debugMode = true;
/* initialize the messages cache */
var cache = new Array();
var phpURL="module_view.php";
var obj=new Array();
var currObj="";

/* creates an XMLHttpRequest instance */
function createXmlHttpRequestObject(){
	// will store the reference to the XMLHttpRequest object
	var xmlHttp;
	// this should work for all browsers except IE6 and older
	try{
		// try to create XMLHttpRequest object
		xmlHttp = new XMLHttpRequest();
	}catch(e){
		// assume IE6 or older
		var XmlHttpVersions = new Array("MSXML2.XMLHTTP.6.0",
		"MSXML2.XMLHTTP.5.0",
		"MSXML2.XMLHTTP.4.0",
		"MSXML2.XMLHTTP.3.0",
		"MSXML2.XMLHTTP",
		"Microsoft.XMLHTTP");
		// try every prog id until one works
		for (var i=0; i<XmlHttpVersions.length && !xmlHttp; i++){
			try{
			// try to create XMLHttpRequest object
			xmlHttp = new ActiveXObject(XmlHttpVersions[i]);
			}catch (e) {}
		}
	}
	// return the created object or display an error message
	if (!xmlHttp)
		alert("Error creating the XMLHttpRequest object.");
	else
		return xmlHttp;
}

var aiter=0;
/* function called when the Send button is pressed */
function sendMessage(funcName, funcParams, respObj){
	
	// don't send void messages
	if (trim(funcParams) != ""){
		// if we need to send and retrieve messages
		params ="fn="+funcName+"&fns=" + encodeURIComponent("el_"+funcName+funcParams); 
		// add the message to the queue
		cache.push(params);
		obj.push(respObj);
	}
}

/* makes asynchronous request to retrieve new messages, post new messages,
delete messages */

function requestNewMessages(){
	// only continue if xmlHttpGetMessages isn't void
	if(xmlHttpGetMessages){
		try{
			// don't start another server operation if such an operation
			// is already in progress
			if (xmlHttpGetMessages.readyState == 4 || xmlHttpGetMessages.readyState == 0){
				// we will store the parameters used to make the server request
				var params = "";
				// if there are requests stored in queue, take the oldest one
				if (cache.length>0){
					params = cache.shift();
					currObj=obj.shift();
					// if the cache is empty, just retrieve new messages
				}else{
					params = "mode=RetrieveNew&id=" +lastMessageID;
				}
				
				// call the server page to execute the server-side operation
				xmlHttpGetMessages.open(null, phpURL, true);
				xmlHttpGetMessages.onreadystatechange = handleReceivingMessages;
				xmlHttpGetMessages.send(params);
			}else{
				// we will check again for new messages
				setTimeout("requestNewMessages();", updateInterval);
			}
		}catch(e){
			displayError(e.toString());
		}
	}
}

function requestNewMessagesGET(){
	// only continue if xmlHttpGetMessages isn't void
	if(xmlHttpGetMessages){
		try{
			// don't start another server operation if such an operation
			// is already in progress
			if (xmlHttpGetMessages.readyState == 4 || xmlHttpGetMessages.readyState == 0){
				// we will store the parameters used to make the server request
				var params = "";
				// if there are requests stored in queue, take the oldest one
				if (cache.length>0){
					params = cache.shift();
					currObj=obj.shift();
					// if the cache is empty, just retrieve new messages
				}else{
					params = "mode=RetrieveNew&id=" +lastMessageID;
				}
				phpURL=phpURL+"?"+params;
				// call the server page to execute the server-side operation
				xmlHttpGetMessages.open("GET", phpURL, true);
				xmlHttpGetMessages.onreadystatechange = handleReceivingMessages;
				xmlHttpGetMessages.send(null);
			}else{
				// we will check again for new messages
				setTimeout("requestNewMessages();", updateInterval);
			}
		}catch(e){
			displayError(e.toString());
		}
	}
}

function doLoad(value) {
    // Create new JsHttpRequest object.
    var req = new JsHttpRequest();
    // Code automatically called on load finishing.
    req.onreadystatechange = function() {
        if (req.readyState == 4) {
            // Write result to page element (_RESULT becomes responseJS). 
            document.getElementById('result').innerHTML = 
                '<b>MD5("'+req.responseJS.q+'")</b> = ' +
                '"' + req.responseJS.md5 + '"<br> ';
            // Write debug information too (output becomes responseText).
            document.getElementById('debug').innerHTML = req.responseText;
        }
    }
    // Prepare request object (automatically choose GET or POST).
    req.open(null, 'smpl_backend.php', true);
    // Send data to backend.
    req.send( { q: value } );
}


/* function that handles the http response when updating messages */
function handleReceivingMessages(){
	// continue if the process is completed
	if (xmlHttpGetMessages.readyState == 4){
		readMessages();
	}
}
/* function that processes the server's response when updating messages */
function readMessages(){// retrieve the server's response
	var response = xmlHttpGetMessages.responseText;//responseJs;
	// server error?
	//alert(xmlHttpGetMessages.responseText);
	if (response.indexOf("ERRNO") >= 0 || response.indexOf("error:") >= 0 || response.length == 0){
		//alert(response);throw(response.length == 0 ? "Void server response." : response); 
	}
		displayMessage(response);
	// the ID of the last received message is stored locally
	//}
}
// displays a message
function displayMessage(message){
	// get the scroll object
	var oScroll = frames.message.document.getElementById(currObj);
	// display the message
	if(oScroll){
		aiter++;
		if(allowProgress==0){
			showProgress(aiter);
		}
		oScroll.innerHTML = message;
		//oScroll.contentEditable=false;
	}/*else{
		alert("Объект \""+obj+"\" не найден.");	
	}*/
}

var pC;
var apCurrWidth=1;
var pEnd=1;
function showProgress(aiter){
	if(pC && pEnd<400){
		var pAcc= 400 / pC;
		var pMax=Math.round(pAcc);
		pEnd=pMax * aiter;
		parent.pMessage="Пожалуйста, подождите. Идет прорисовка компонентов...";//Устанавливаем надпись на прогрессе
		parent.opacity=100;//Устанавливаем видимость в процентах прогресса
		parent.speed=10;//Устанавливаем скорость роста прогресса (чем меньше цифра, тем быстрее рост)
		parent.pCurrWidth=1;//Устанавливаем начальную длину прогресс-бара
		parent.drawProgress(apCurrWidth, pEnd);
		//alert(pC+" "+aiter+" "+apCurrWidth+" "+pEnd);
		apCurrWidth=pEnd;
	}
	if(aiter==1){
		pC=cache.length;
	}
}



// function that displays an error message
function displayError(message){
	// display error message, with more technical details if debugMode is true
	displayMessage("Ошибка на стороне сервера! "+ (debugMode ? "<br/>" + message : ""));
}

/* removes leading and trailing spaces from the string */
function trim(s){
	return s.replace(/(^\s+)|(\s+$)/g, "")
}