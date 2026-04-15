<?php
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db   = new Db();
$auth = new Auth();

if (!$auth->checkAjax()) {
    echo json_encode(['result' => false, 'resultText' => 'Ошибка авторизации.', 'errorFields' => []]);
    exit;
}

$perms = $auth->checkModulePermissions(1); // модуль Планы — id=1
if (!($perms['delete'] ?? false)) {
    echo json_encode(['result' => false, 'resultText' => 'Недостаточно прав.', 'errorFields' => []]);
    exit;
}

if (!is_array($_POST['reg_id'] ?? null) || count($_POST['reg_id']) === 0) {
    echo json_encode(['result' => false, 'resultText' => 'Не выбран ни один план.', 'errorFields' => []]);
    exit;
}

$ids = array_map('intval', $_POST['reg_id']);
$db->archive('checksplans', $ids, intval($_SESSION['user_id']));

echo json_encode([
    'result'      => true,
    'resultText'  => 'Планы перемещены в архив.<script>el_app.reloadMainContent();</script>',
    'errorFields' => [],
]);
