<?php
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();

$db = new DB;
$auth = new Auth();

if($auth->checkAjax()) {

    if ((is_array($_POST['role_id']) && count($_POST['role_id']) == 0) || !isset($_POST['role_id'])) {
        $err++;
        $errStr[] = "Не выбрана ни одна роль.";
    }

    if ($err == 0) {
        $ids = (array)$_POST['role_id'];
        $db->cloneRows('roles', $ids);
        $result = true;
        $message = 'Роли успешно клонированы.<script>el_app.reloadMainContent();</script>';
    } else {
        $message = '<strong>Ошибка:</strong>&nbsp; ' . implode('<br>&nbsp; ', $errStr);
    }

    echo json_encode(array(
        'result' => $result,
        'resultText' => $message,
        'errorFields' => $errorFields));
}
?>