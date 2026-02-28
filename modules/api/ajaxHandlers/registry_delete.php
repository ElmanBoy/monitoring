<?php
use Core\Db;
use Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();

$db = new Db;
$reg = new Registry();

if(!is_array($_POST['reg_id']) || count($_POST['reg_id']) == 0){
    $err++;
    $errStr[] = "Не выбран ни один справочник.";
}

if($err == 0){
    $ids = $_POST['reg_id'];
    for($i = 0; $i < count($ids); $i++) {
        $reg->deleteRegistry($ids[$i]);
    }
    $result = true;
    $message = 'Справочники успешно удалены.<script>el_app.reloadMainContent();</script>';
}else{
    $message = '<strong>Ошибка:</strong><br> '.implode('<br>', $errStr);
}

echo json_encode(array(
    'result' => $result,
    'resultText' => $message,
    'errorFields' => $errorFields));
?>