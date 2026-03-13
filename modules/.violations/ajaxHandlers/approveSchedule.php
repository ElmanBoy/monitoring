<?php
/**
 * modules/violations/ajaxHandlers/approveSchedule.php
 *
 * Министерство утверждает или отклоняет план-график устранения нарушений целиком.
 *
 * POST-параметры:
 *   agreement_id  — id акта
 *   action        — 'approve' | 'reject'
 *   comment       — комментарий (обязателен при reject)
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

// Роли министерства — не роль 5
$userRoles = $_SESSION['user_roles'] ?? [];
if (in_array(5, (array)$userRoles)) {
    echo json_encode(['result' => false, 'resultText' => 'Недостаточно прав']);
    exit;
}

$agreementId = intval($_POST['agreement_id']);
$action      = $_POST['action'] ?? '';
$comment     = trim($_POST['comment'] ?? '');

if ($agreementId <= 0 || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['result' => false, 'resultText' => 'Неверные параметры']);
    exit;
}

if ($action === 'reject' && strlen($comment) === 0) {
    echo json_encode(['result' => false, 'resultText' => 'Укажите комментарий для отклонения']);
    exit;
}

$agr = $db->selectOne('agreement', ' WHERE id = ? AND documentacial = 2', [$agreementId]);
if (!$agr) {
    echo json_encode(['result' => false, 'resultText' => 'Акт не найден']);
    exit;
}

// Получаем все нарушения по данному акту со статусом 1 (ожидают утверждения)
$plan   = $db->selectOne('checksplans', ' WHERE id = ?', [intval($agr->plan_id)]);
$staffRows = $plan
    ? $db->select('checkstaff', ' WHERE check_uid = ? AND institution = ?', [$plan->uid, $agr->ins_id])
    : [];

$taskIds = array_map(fn($s) => intval($s->id), $staffRows);

if (empty($taskIds)) {
    echo json_encode(['result' => false, 'resultText' => 'Нарушения не найдены']);
    exit;
}

$taskIdsStr = implode(',', $taskIds);

// Проверяем что все нарушения в статусе 1 (заполнены ОК)
$notReady = $db->db::getCell(
    "SELECT COUNT(*) FROM " . TBL_PREFIX . "checksviolations
     WHERE tasks IN ($taskIdsStr) AND schedule_status != 1"
);

if (intval($notReady) > 0 && $action === 'approve') {
    echo json_encode([
        'result'     => false,
        'resultText' => 'Не все пункты заполнены объектом контроля'
    ]);
    exit;
}

try {
    $newStatus = $action === 'approve' ? 2 : 3;

    $db->db::exec(
        "UPDATE " . TBL_PREFIX . "checksviolations
         SET schedule_status  = ?,
             schedule_comment = ?
         WHERE tasks IN ($taskIdsStr)
           AND schedule_status = 1",
        [$newStatus, $action === 'reject' ? $comment : null]
    );

    $result     = true;
    $resultText = $action === 'approve'
        ? 'План-график утверждён.'
        : 'План-график отклонён. Замечания направлены объекту контроля.';

} catch (Exception $e) {
    $result     = false;
    $resultText = 'Ошибка: ' . $e->getMessage();
}

echo json_encode([
    'result'     => $result,
    'resultText' => $resultText,
]);
