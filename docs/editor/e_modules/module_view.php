<? 
require_once($_SERVER['DOCUMENT_ROOT'].'/Connections/dbconn.php');
require_once ($_SERVER['DOCUMENT_ROOT'].'/editor/e_modules/JsHttpRequest/lib/JsHttpRequest/JsHttpRequest.php');
// set the user error handler method to be error_handler
//set_error_handler('error_handler', E_ALL);
// error handler function
function error_handler($errNo, $errStr, $errFile, $errLine){
	// clear any output that has already been generated
	if(ob_get_length()) ob_clean();
	// output the error message
	$error_message = 'ERRNO: ' . $errNo . chr(10) .
	'TEXT: ' . $errStr . chr(10) .
	'LOCATION: ' . $errFile .
	', line ' . $errLine;
	echo $error_message;
	// prevent processing any more PHP scripts
	exit;
}

function getmoduleName($mName){
	$out="";
	switch ($mName){
		case "menu": $out="Меню"; break;	
		case "text": $out="Текст"; break;	
		case "calend": $out="Календарь"; break;	
		case "module": $out="Модули"; break;
		case "counter": $out="Счетчик"; break;	
		case "anons": $out="Анонсы"; break;	
		case "polls": $out="Опросы"; break;	
	}
	return $out;
}

$JsHttpRequest =& new JsHttpRequest("windows-1251");?>
<div id=module_conteiner_control contentEditable=false style="background-image:url('/editor/img/plugin.gif'); background-repeat:no-repeat; height:25px; padding-top:5px; padding-left:25px; font-family:Tahoma; font-size:11px; font-weight:bold">Компонент "<?=getmoduleName($_REQUEST['fn'])?>"</div>
<?
eval(str_replace("'", '"', trim(stripslashes($_REQUEST['fns']))).';'); 
?>