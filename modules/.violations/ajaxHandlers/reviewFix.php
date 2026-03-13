<?php
/**
 * modules/violations/ajaxHandlers/reviewFix.php
 *
 * Министерство рассматривает поданное подтверждение устранения нарушения.
 *
 * POST-параметры:
 *   violation_id    — id записи в checksviolations
 *   action          — 'close' | 'return' | 'extend'
 *   check_comment   — комментарий (обязателен при return)
 *   deadline_extended — новая дата (обязательна при extend)
 *   extended_reason — причина продления (обязательна при extend)
 */

use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db   = new Db();
$auth = new Auth();

if (!$auth->isLogin() || !$auth->checkAjax()) {
    echo json_encode(['result' => false, 'resultText' => 'Нет доступа']);
    exit;
}

// Только сотрудники министерства (не роль 5)
$userRoles = $_SESSION['user_roles'] ?? [];
if (in_array(5, (array)$userRoles)) {
    echo json_encode(['result' => false, 'resultText' => 'Недостаточно прав']);
    exit;
}

$violationId      = intval($_POST['violation_id']);
$action           = $_POST['action'] ?? '';
$checkComment     = trim($_POST['check_comment'] ?? '');
$deadlineExtended = trim($_POST['deadline_extended'] ?? '');
$extendedReason   = trim($_POST['extended_reason'] ?? '');

if ($violationId <= 0 || !in_array($action, ['close', 'return', 'extend'])) {
    echo json_encode(['result' => false, 'resultText' => 'Неверные параметры']);
    exit;
}

// Валидация по типу действия
if ($action === 'return' && strlen($checkComment) === 0) {
    echo json_encode(['result' => false, 'resultText' => 'Укажите причину возврата']);
    exit;
}

if ($action === 'extend') {
    if (strlen($deadlineExtended) === 0) {
        echo json_encode(['result' => false, 'resultText' => 'Укажите новый срок устранения']);
        exit;
    }
    if (strlen($extendedReason) === 0) {
        echo json_encode(['result' => false, 'resultText' => 'Укажите причину продления срока']);
        exit;
    }
}

$violation = $db->selectOne('checksviolations', ' WHERE id = ?', [$violationId]);
if (!$violation) {
    echo json_encode(['result' => false, 'resultText' => 'Нарушение не найдено']);
    exit;
}

// Действие возможно только если fix_status = 1 (подано ОК)
// Исключение: extend можно и при fix_status = 0 (продление до подачи)
$allowedFixStatuses = $action === 'extend' ? [0, 1] : [1];
if (!in_array(intval($violation->fix_status), $allowedFixStatuses)) {
    echo json_encode(['result' => false, 'resultText' => 'Некорректный статус нарушения для данного действия']);
    exit;
}

try {
    $updateData = ['check_comment' => $checkComment ?: null];

    switch ($action) {
        case 'close':
            // Нарушение снято
            $updateData['fix_status'] = 2;
            $resultText = 'Нарушение снято.';
            break;

        case 'return':
            // Возврат на доработку ОК
            $updateData['fix_status']   = 3;
            $updateData['check_comment'] = $checkComment;
            $resultText = 'Нарушение возвращено на доработку.';
            break;

        case 'extend':
            // Продление срока — fix_status не меняем, обновляем дедлайн
            $updateData['deadline_extended'] = $deadlineExtended;
            $updateData['extended_reason']   = $extendedReason;
            // Если срок продлён до того как ОК подал — сбрасываем в fix_status=0
            if (intval($violation->fix_status) === 0) {
                $updateData['fix_status'] = 0;
            }
            $resultText = 'Срок устранения продлён до ' . $deadlineExtended . '.';
            break;
    }

    $db->update('checksviolations', $violationId, $updateData);

    echo json_encode([
        'result'     => true,
        'resultText' => $resultText,
    ]);

} catch (Exception $e) {
    echo json_encode(['result' => false, 'resultText' => 'Ошибка: ' . $e->getMessage()]);
}
