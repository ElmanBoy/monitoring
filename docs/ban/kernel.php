<?
include("config/config.php");

//перехватываем ошибки и пишем их в лог
//function myErrorHandler ($errno, $errstr, $errfile, $errline) {
// global $errortype;
// $fp=fopen(err_log,"a+");
// $d=date("d-M Y");
// $d=$d."|".$GLOBALS["REMOTE_ADDR"]."|".$errortype[$errno].": ".$errstr." in ".$errfile." on line ".$errline."\n";
// fputs($fp,$d);
// fclose($fp);
//}
//регестрируем наш обработчик ошибок
//$old_error_handler = set_error_handler("myErrorHandler");
?>