<?php
use \Core\Db;
use \Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err         = 0;
$errStr      = [];
$result      = false;
$errorFields = [];
$regId       = intval($_POST['registry_id']);

$db   = new Db();
$auth = new Auth();

if (!$auth->isAdmin()) {
    echo json_encode([
        'result'      => false,
        'resultText'  => '<strong>Ошибка:</strong> Недостаточно прав. Восстановление из архива доступно только администратору.',
        'errorFields' => [],
    ]);
    exit;
}

if (!is_array($_POST['reg_id']) || count($_POST['reg_id']) == 0) {
    $err++;
    $errStr[] = 'Не выбран ни один элемент.';
}

if ($err == 0) {
    $ids   = array_map('intval', $_POST['reg_id']);
    $table = $db->selectOne('registry', ' where id = ?', [$regId]);
    $db->restore($table->table_name, $ids);
    $result  = true;
    $message = 'Записи восстановлены из архива.<script>el_app.reloadMainContent();</script>';
} else {
    $message = '<strong>Ошибка:</strong><br> ' . implode('<br>', $errStr);
}

echo json_encode([
    'result'      => $result,
    'resultText'  => $message,
    'errorFields' => $errorFields,
]);
