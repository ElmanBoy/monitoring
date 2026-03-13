<?php
/**
 * modules/violations/ajaxHandlers/saveSchedule.php
 *
 * ОК заполняет ответственного и срок устранения по каждому нарушению.
 * Финальная кнопка "Отправить на утверждение" переводит все записи в schedule_status=1.
 *
 * POST-параметры:
 *   agreement_id  — id акта (cam_agreement)
 *   items[]       — массив: [{id, responsible, deadline}]
 *   submit        — если '1', переводить в статус 1 (отправить на утверждение)
 */

use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db   = new Db();
$auth = new Auth();

$result     = false;
$resultText = '';

if (!$auth->isLogin() || !$auth->checkAjax()) {
    echo json_encode(['result' => false, 'resultText' => 'Нет доступа']);
    exit;
}

// Только роль 5 (директор учреждения / объект контроля)
$userRoles = $_SESSION['user_roles'] ?? [];
if (!in_array(5, (array)$userRoles)) {
    echo json_encode(['result' => false, 'resultText' => 'Недостаточно прав']);
    exit;
}

$agreementId = intval($_POST['agreement_id']);
$items       = $_POST['items'] ?? [];
$submit      = intval($_POST['submit'] ?? 0);

if ($agreementId <= 0 || empty($items)) {
    echo json_encode(['result' => false, 'resultText' => 'Неверные параметры']);
    exit;
}

// Проверяем что акт существует и принадлежит учреждению текущего пользователя
$agr = $db->selectOne('agreement', ' WHERE id = ? AND documentacial = 2', [$agreementId]);
if (!$agr) {
    echo json_encode(['result' => false, 'resultText' => 'Акт не найден']);
    exit;
}

$currentUser = $db->selectOne('users', ' WHERE id = ?', [$_SESSION['user_id']]);
if (!$currentUser || intval($currentUser->institution) !== intval($agr->ins_id)) {
    echo json_encode(['result' => false, 'resultText' => 'Нет доступа к этому акту']);
    exit;
}

try {
    $db->db::begin();

    foreach ($items as $item) {
        $violationId = intval($item['id'] ?? 0);
        $responsible = trim($item['responsible'] ?? '');
        $deadline    = trim($item['deadline'] ?? '');

        if ($violationId <= 0) continue;

        // Проверяем, что нарушение относится к этому акту
        $violation = $db->db::getRow(
            "SELECT cv.* FROM " . TBL_PREFIX . "checksviolations cv
             JOIN " . TBL_PREFIX . "checkstaff cs ON cs.id = cv.tasks
             WHERE cv.id = ? AND cs.institution = ?",
            [$violationId, $agr->ins_id]
        );

        if (!$violation) continue;

        // Редактировать можно только если schedule_status = 0 или 3 (отклонён — доработка)
        if (!in_array(intval($violation['schedule_status']), [0, 3])) continue;

        $updateData = [
            'responsible' => $responsible,
            'deadline'    => $deadline ?: null,
        ];

        if ($submit === 1) {
            $updateData['schedule_status'] = 1; // отправлено на утверждение
            $updateData['schedule_comment'] = null; // сбрасываем комментарий отклонения
        }

        $db->update('checksviolations', $violationId, $updateData);
    }

    $db->db::commit();

    $result     = true;
    $resultText = $submit === 1
        ? 'План-график отправлен на утверждение.'
        : 'Данные сохранены.';

} catch (Exception $e) {
    $db->db::rollback();
    $result     = false;
    $resultText = 'Ошибка сохранения: ' . $e->getMessage();
}

echo json_encode([
    'result'     => $result,
    'resultText' => $resultText,
]);
