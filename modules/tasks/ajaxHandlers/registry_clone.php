<?php
use Core\Db;
require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();

$db = new Db;

if(is_array($_POST['reg_id']) && count($_POST['reg_id']) == 0){
    $err++;
    $errStr[] = "Не выбран ни один справочник.";
}

if($err == 0){
    $ids = (array)$_POST['reg_id'];
    $db->cloneRows('registry', $ids);
    $result = true;
    $message = 'Справочники успешно клонированы.<script>el_app.reloadMainContent();</script>';
}else{
    $message = '<strong>Ошибка:</strong><br> '.implode('<br>', $errStr);
}

echo json_encode(array(
    'result' => $result,
    'resultText' => $message,
    'errorFields' => $errorFields));
?>