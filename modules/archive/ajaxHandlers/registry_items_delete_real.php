<?php
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db   = new Db();
$auth = new Auth();

if (!$auth->checkAjax()) {
    echo json_encode(['result' => false, 'resultText' => 'Ошибка авторизации.']);
    exit;
}

$perms = $auth->checkModulePermissions(25);
if (!($perms['delete'] ?? false)) {
    echo json_encode(['result' => false, 'resultText' => 'Недостаточно прав для удаления.']);
    exit;
}

$regId = intval($_POST['registry_id']);

if (!is_array($_POST['reg_id'] ?? null) || count($_POST['reg_id']) === 0) {
    echo json_encode(['result' => false, 'resultText' => 'Не выбран ни один элемент.']);
    exit;
}

$ids   = array_map('intval', $_POST['reg_id']);
$table = $db->selectOne('registry', ' WHERE id = ?', [$regId]);

if (!$table) {
    echo json_encode(['result' => false, 'resultText' => 'Реестр не найден.']);
    exit;
}

$res = $db->delete($table->table_name, $ids);

if ($res['result']) {
    $res['resultText'] = 'Записи безвозвратно удалены.<script>el_app.reloadMainContent();</script>';
}

echo json_encode([
    'result'      => $res['result'],
    'resultText'  => $res['resultText'],
    'errorFields' => [],
]);
