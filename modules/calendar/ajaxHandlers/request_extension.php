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

$taskId = intval($_POST['task_id'] ?? 0);
$newEndDate = trim($_POST['new_end_date'] ?? '');
$requestComment = trim($_POST['request_comment'] ?? '');

if ($taskId <= 0 || strlen($newEndDate) === 0) {
    echo json_encode(['result' => false, 'resultText' => 'Не указаны обязательные параметры']);
    exit;
}

$chStaff = $db->selectOne('checkstaff', ' WHERE id = ?', [$taskId]);
if ($chStaff === null) {
    echo json_encode(['result' => false, 'resultText' => 'Задача не найдена']);
    exit;
}

// Только сам инспектор может подавать запрос на продление своей задачи
if (intval($chStaff->user) !== intval($_SESSION['user_id'])) {
    echo json_encode(['result' => false, 'resultText' => 'Вы можете запрашивать продление только своих задач']);
    exit;
}

// Новая дата окончания должна быть позже текущей
$dateArr = explode(' - ', $chStaff->dates);
$currentStart = isset($dateArr[0]) ? trim($dateArr[0]) : '';
$currentEnd   = isset($dateArr[1]) ? trim($dateArr[1]) : '';

if ($currentEnd && strtotime($newEndDate) <= strtotime($currentEnd)) {
    echo json_encode(['result' => false, 'resultText' => 'Новая дата окончания должна быть позже текущей (' . $currentEnd . ')']);
    exit;
}

// Формируем полный диапазон: сохраняем исходную дату начала, меняем только конец
$requestedDates = $currentStart . ' - ' . $newEndDate;

$db->update('checkstaff', $taskId, [
    'extension_requested_dates' => $requestedDates,
    'extension_request_status'  => 1,
    'extension_request_comment' => $requestComment,
]);

// Уведомляем руководителя проверки
$headRecord = $db->selectOne('checkstaff',
    ' WHERE check_uid = ? AND institution = ? AND is_head = 1 AND active = 1',
    [$chStaff->check_uid, $chStaff->institution]
);

if ($headRecord !== null && intval($headRecord->user) > 0) {
    $inspector = $db->selectOne('users', ' WHERE id = ?', [$chStaff->user]);
    $inspectorFio = trim($inspector->surname) . ' ' . trim($inspector->name) . ' ' . trim($inspector->middle_name);

    $ins = $db->selectOne('institutions', ' WHERE id = ?', [$chStaff->institution]);
    $insName = $ins ? $ins->short : 'учреждение #' . $chStaff->institution;

    $message = '<p><strong>Запрос на продление проверки</strong></p>'
        . '<p><strong>Инспектор:</strong> ' . htmlspecialchars($inspectorFio) . '</p>'
        . '<p><strong>Объект проверки:</strong> ' . htmlspecialchars($insName) . '</p>'
        . '<p><strong>Текущий период:</strong> ' . htmlspecialchars($chStaff->dates) . '</p>'
        . '<p><strong>Запрошенный период:</strong> ' . htmlspecialchars($requestedDates) . '</p>'
        . (strlen($requestComment) > 0 ? '<p><strong>Комментарий:</strong> ' . htmlspecialchars($requestComment) . '</p>' : '');

    $notify = new Notifications();
    $notify->addRecordToPanel(
        intval($headRecord->user),
        $message,
        $taskId,
        '/calendar'
    );
}

echo json_encode(['result' => true, 'resultText' => 'Запрос на продление отправлен руководителю проверки']);
