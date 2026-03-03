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
    $errStr[] = "Не выбран ни один пункт.";
}

if($err == 0){
    $ids = (array)$_POST['reg_id'];
    $db->cloneRows('checkitems', $ids);
    $result = true;
    $message = 'Пункты успешно клонированы.<script>el_app.reloadMainContent();</script>';
}else{
    $message = '<strong>Ошибка:</strong>&nbsp; '.implode('<br>', $errStr);
}

echo json_encode(array(
    'result' => $result,
    'resultText' => $message,
    'errorFields' => $errorFields));
?>