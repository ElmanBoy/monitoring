<?php

use Core\Db;
use Core\Auth;
use Core\Notifications;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db = new Db();
$auth = new Auth();

if (!$auth->isLogin() || !$auth->checkAjax()) {
    echo json_encode(['result' => false, 'resultText' => 'Доступ запрещён']);
    exit;
}

$taskId  = intval($_POST['task_id'] ?? 0);
$action  = trim($_POST['resolve_action'] ?? '');   // 'approve' или 'reject'
$comment = trim($_POST['comment'] ?? '');

if ($taskId <= 0 || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['result' => false, 'resultText' => 'Не указаны обязательные параметры']);
    exit;
}

$chStaff = $db->selectOne('checkstaff', ' WHERE id = ?', [$taskId]);
if ($chStaff === null) {
    echo json_encode(['result' => false, 'resultText' => 'Задача не найдена']);
    exit;
}

$isInspectorWithdraw = ($action === 'reject' && intval($chStaff->user) === intval($_SESSION['user_id']));

if (!$isInspectorWithdraw) {
    // Только руководитель проверки этого учреждения может одобрять или отклонять
    $headRecord = $db->selectOne('checkstaff',
        ' WHERE check_uid = ? AND institution = ? AND is_head = 1 AND active = 1 AND "user" = ?',
        [$chStaff->check_uid, $chStaff->institution, $_SESSION['user_id']]
    );
    if ($headRecord === null && !$auth->isAdmin()) {
        echo json_encode(['result' => false, 'resultText' => 'Только руководитель проверки может принять решение по запросу']);
        exit;
    }
}

if (intval($chStaff->extension_request_status) !== 1) {
    echo json_encode(['result' => false, 'resultText' => 'Нет активного запроса на продление']);
    exit;
}

if ($action === 'approve') {
    $newDates = $chStaff->extension_requested_dates;
    $db->update('checkstaff', $taskId, [
        'dates'                     => $newDates,
        'extension_request_status'  => 2,
        'extension_request_comment' => $comment,
    ]);
    $resultText = 'Продление утверждено. Новый период: ' . $newDates;
    $notifyMessage = '<p><strong>Запрос на продление проверки одобрен</strong></p>'
        . '<p><strong>Новый период проверки:</strong> ' . htmlspecialchars($newDates) . '</p>'
        . (strlen($comment) > 0 ? '<p><strong>Комментарий руководителя:</strong> ' . htmlspecialchars($comment) . '</p>' : '');
} else {
    $db->update('checkstaff', $taskId, [
        'extension_request_status'  => 3,
        'extension_request_comment' => $comment,
    ]);
    $resultText = 'Запрос отклонён';
    $notifyMessage = '<p><strong>Запрос на продление проверки отклонён</strong></p>'
        . (strlen($comment) > 0 ? '<p><strong>Причина:</strong> ' . htmlspecialchars($comment) . '</p>' : '');
}

// Уведомляем инспектора о решении
if (intval($chStaff->user) > 0) {
    $notify = new Notifications();
    $notify->addRecordToPanel(
        intval($chStaff->user),
        $notifyMessage,
        $taskId,
        '/assigned'
    );
}

echo json_encode(['result' => true, 'resultText' => $resultText]);
