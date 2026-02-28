<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();

if(!is_array($_POST['role_id']) || count($_POST['role_id']) == 0){
    $err++;
    $errStr[] = "Не выбрана ни одна роль.";
}

if($err == 0){
    $ids = $_POST['role_id'];
    R::trashBatch('ohs_roles', $ids);
    $result = true;
    $message = 'Роли успешно удалены.<script>el_app.setMainContent(\'/roles\');</script>';
}else{
    $message = '<strong>Ошибка:</strong><br> '.implode('<br>', $errStr);
}

echo json_encode(array(
    'result' => $result,
    'resultText' => $message,
    'errorFields' => $errorFields));
?>