<?php
/**
 * Сохранение итоговой фиксации результатов проверки
 */

use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db = new Db();
$result = false;
$message = '';

try {
    $taskId = intval($_POST['task_id'] ?? 0);
    $checkstaffId = intval($_POST['checkstaff_id'] ?? 0);
    $actId = intval($_POST['act_id'] ?? 0);
    $scheduleId = intval($_POST['schedule_id'] ?? 0);

    if ($taskId === 0) {
        throw new Exception('Не указан ID задачи');
    }

    // Проверяем, не зафиксирован ли уже результат
    $existingResult = $db->selectOne('inspectionresults', ' WHERE task_id = ? AND active = 1', [$taskId]);
    if ($existingResult) {
        throw new Exception('Результаты проверки уже зафиксированы');
    }

    $resultType = trim($_POST['result_type'] ?? '');
    $resultSummary = trim($_POST['result_summary'] ?? '');
    $finalizedDate = trim($_POST['finalized_date'] ?? '');

    // Валидация
    if (empty($resultType)) {
        throw new Exception('Не указан тип результата');
    }
    if (empty($resultSummary)) {
        throw new Exception('Не заполнено итоговое заключение');
    }
    if (empty($finalizedDate)) {
        throw new Exception('Не указана дата финализации');
    }

    // Статистика
    $totalViolations = intval($_POST['total_violations'] ?? 0);
    $violationsFixed = intval($_POST['violations_fixed'] ?? 0);
    $violationsUnfixed = intval($_POST['violations_unfixed'] ?? 0);

    // Сохраняем итоговую фиксацию
    $data = [
        'created_at' => date('Y-m-d H:i:s'),
        'author' => $_SESSION['user_id'],
        'active' => 1,
        'task_id' => $taskId,
        'checkstaff_id' => $checkstaffId > 0 ? $checkstaffId : null,
        'act_id' => $actId > 0 ? $actId : null,
        'schedule_id' => $scheduleId > 0 ? $scheduleId : null,
        'result_type' => $resultType,
        'result_summary' => $resultSummary,
        'violations_total' => $totalViolations,
        'violations_fixed' => $violationsFixed,
        'violations_unfixed' => $violationsUnfixed,
        'finalized_date' => $finalizedDate,
        'finalized_by' => $_SESSION['user_id'],
    ];

    $db->insert('inspectionresults', $data);

    $result = true;
    $message = 'Результаты проверки успешно зафиксированы.<script>el_app.reloadMainContent();el_app.dialog_close("finalize_inspection");</script>';

} catch (Exception $e) {
    $result = false;
    $message = 'Ошибка: ' . $e->getMessage();
}

echo json_encode([
    'result' => $result,
    'resultText' => $message
]);
