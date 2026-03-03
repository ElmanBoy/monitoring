<?php
use Core\Auth;


require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();

$auth = new Auth();

if($auth->checkAjax()) {

    if (!is_array($_POST['role_id']) || count($_POST['role_id']) == 0) {
        $err++;
        $errStr[] = "Не выбрана ни одна роль.";
    }

    if ($err == 0) {
        $ids = $_POST['role_id'];
        R::trashBatch(TBL_PREFIX . 'roles', $ids);
        $result = true;
        $message = 'Роли успешно удалены.<script>el_app.reloadMainContent();</script>';
    } else {
        $message = '<strong>Ошибка:</strong>&nbsp; ' . implode('<br>&nbsp; ', $errStr);
    }

    echo json_encode(array(
        'result' => $result,
        'resultText' => $message,
        'errorFields' => $errorFields));
}else{
    echo json_encode(array(
        'result' => $result,
        'resultText' => 'Ваша сессия устарела.<script>document.location.href = "/"</script>',
        'errorFields' => []));
}
?>