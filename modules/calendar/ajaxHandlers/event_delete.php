<?php
use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();

$db = new Db;

if(intval($_POST['id']) == 0){
    $err++;
    $errStr[] = "Не выбрано ни одно задание.";
}

if($err == 0){
    $id = intval($_POST['id']);
    $db->delete('checkstaff', [$id]);
    $result = true;
    $message = 'Задание успешно удалено.';
}else{
    $message = '<strong>Ошибка:</strong><br> '.implode('<br>', $errStr);
}

echo json_encode(array(
    'result' => $result,
    'resultText' => $message,
    'errorFields' => $errorFields));
?>