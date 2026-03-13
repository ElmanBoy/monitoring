<?php

use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db     = new Db();
$docId  = intval($_POST['act_id']);
$userId = intval($_POST['user_id']);

$result = $db->update('agreement', $docId, ['act_agree' => $userId]);

if ($result['result']) {
    $message = 'С актом ознакомлены.';
} else {
    $message = '<strong>Ошибка:</strong>&nbsp;' . $result['resultText'];
}

echo json_encode([
    'result'      => $result['result'],
    'resultText'  => $message . '<script>el_app.reloadMainContent();</script>',
    'errorFields' => [],
]);