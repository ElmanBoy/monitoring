<?php
use \Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err        = 0;
$errStr     = [];
$result     = false;
$errorFields = [];
$regId      = intval($_POST['registry_id']);

$db = new Db();

if (!is_array($_POST['reg_id']) || count($_POST['reg_id']) == 0) {
    $err++;
    $errStr[] = 'Не выбран ни один элемент.';
}

if ($err == 0) {
    $ids   = array_map('intval', $_POST['reg_id']);
    $table = $db->selectOne('registry', ' where id = ?', [$regId]);
    $db->archive($table->table_name, $ids, intval($_SESSION['user_id']));
    $result  = true;
    $message = 'Записи перемещены в архив.<script>el_app.reloadMainContent();</script>';
} else {
    $message = '<strong>Ошибка:</strong><br> ' . implode('<br>', $errStr);
}

echo json_encode([
    'result'      => $result,
    'resultText'  => $message,
    'errorFields' => $errorFields,
]);