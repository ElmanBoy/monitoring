<?php
//
// ВНИМАНИЕ! До подключения библиотеки в браузер не должно быть выведено
// ни одного символа. В противном случае функция header(), используемая
// библиотекой, не сработает (см. документацию), и возникнет ошибка.
//

// Стартуем сессию.
session_start();
// Подключаем библиотеку поддержки.
require_once "../../../lib/JsHttpRequest/JsHttpRequest.php";
// Создаем главный объект библиотеки.
// Указываем кодировку страницы (обязательно!).
$JsHttpRequest =& new JsHttpRequest("windows-1251");
// Получаем запрос.
$q = @$_REQUEST['q'];
// Формируем результат прямо в виде PHP-массива!
$_RESULT = array(
  "q"      => JsHttpRequest::php2js($q),
  "md5"    => md5(is_array($q)? serialize($q) : $q),
  "hello"  => isset($_SESSION['hello'])? $_SESSION['hello'] : null,
  "upload" => print_r($_FILES, 1),
);
if ($q == "session-set") {
    $_SESSION['test'] = "test_value";
} 
// Демонстрация отладочных сообщений.
if (@strpos($q, 'error') !== false) {
  callUndefinedFunction();
}
if (@$_REQUEST['dt']) {
  sleep($_REQUEST['dt']);
}
// Do NOT write Content-type here: IE ommits it for ActiveX!
?>
<?if (!$JsHttpRequest->ID) {?>Zero loading ID: yes<? echo "\n"; }?>
QUERY_STRING: <?=$_SERVER['QUERY_STRING'] . "\n"?>
Request method: <?=$_SERVER['REQUEST_METHOD'] . "\n"?>
Loader used: <?=$JsHttpRequest->LOADER . "\n"?>
Uploaded file size: <?=@$_FILES['file']['size'] . "\n"?>
_GET: <?=print_r($_GET, 1)?>
_POST: <?=print_r($_POST, 1)?>
_FILES: <?=preg_replace('/(\[(name|size|tmp_name|type)\].*?)(\S+)$/m', '$1***', print_r($_FILES, 1))?>
<?if ($q == "session-get") {?>_SESSION[test]: <?=@$_SESSION['test']?><?}?>

