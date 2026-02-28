<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();

if(!is_array($_POST['reg_id']) || count($_POST['reg_id']) == 0){
    $err++;
    $errStr[] = "Не выбрано ни одно поле.";
}

if($err == 0){
    $ids = $_POST['reg_id'];
    R::trashBatch(TBL_PREFIX.'regprops', $ids);
    $result = true;
    $message = 'Поля успешно удалены.<script>el_app.reloadMainContent();</script>';
}else{
    $message = '<strong>Ошибка:</strong><br> '.implode('<br>', $errStr);
}

echo json_encode(array(
    'result' => $result,
    'resultText' => $message,
    'errorFields' => $errorFields));
?>