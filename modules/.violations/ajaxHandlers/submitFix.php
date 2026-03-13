<?php
/**
 * modules/violations/ajaxHandlers/submitFix.php
 *
 * ОК прикрепляет файл подтверждения устранения нарушения.
 * Переводит fix_status = 1 (заявлено об устранении).
 *
 * POST-параметры:
 *   violation_id  — id записи в checksviolations
 *   fix_comment   — комментарий ОК (необязателен)
 * FILES:
 *   files[]       — файлы подтверждения
 */

use Core\Db;
use Core\Auth;
use Core\Files;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db    = new Db();
$auth  = new Auth();
$files = new Files();

if (!$auth->isLogin() || !$auth->checkAjax()) {
    echo json_encode(['result' => false, 'resultText' => 'Нет доступа']);
    exit;
}

$userRoles = $_SESSION['user_roles'] ?? [];
if (!in_array(5, (array)$userRoles)) {
    echo json_encode(['result' => false, 'resultText' => 'Недостаточно прав']);
    exit;
}

$violationId = intval($_POST['violation_id']);
$fixComment  = trim($_POST['fix_comment'] ?? '');

if ($violationId <= 0) {
    echo json_encode(['result' => false, 'resultText' => 'Неверный id нарушения']);
    exit;
}

// Проверяем нарушение и принадлежность учреждению
$currentUser = $db->selectOne('users', ' WHERE id = ?', [$_SESSION['user_id']]);
$violation   = $db->db::getRow(
    "SELECT cv.* FROM " . TBL_PREFIX . "checksviolations cv
     JOIN " . TBL_PREFIX . "checkstaff cs ON cs.id = cv.tasks
     WHERE cv.id = ? AND cs.institution = ?",
    [$violationId, $currentUser->institution ?? 0]
);

if (!$violation) {
    echo json_encode(['result' => false, 'resultText' => 'Нарушение не найдено']);
    exit;
}

// Можно подавать только если schedule_status=2 и fix_status=0 или 3 (возврат)
if (!in_array(intval($violation['schedule_status']), [2])) {
    echo json_encode(['result' => false, 'resultText' => 'График ещё не утверждён']);
    exit;
}

if (!in_array(intval($violation['fix_status']), [0, 3])) {
    echo json_encode(['result' => false, 'resultText' => 'Нарушение уже на проверке или снято']);
    exit;
}

// Загружаем файлы
$fileIds = [];
if (!empty($_FILES['files']['name'][0])) {
    $uploadResult = $files->attachFiles($_FILES['files'], $_POST['custom_names'] ?? []);
    if ($uploadResult['result']) {
        // Объединяем с уже существующими файлами
        $existingFiles = json_decode($violation['fix_files'] ?? '[]', true) ?: [];
        $fileIds = array_merge($existingFiles, $uploadResult['ids']);
    } else {
        echo json_encode(['result' => false, 'resultText' => $uploadResult['message']]);
        exit;
    }
} else {
    // Файл обязателен при первичной подаче
    if (intval($violation['fix_status']) === 0) {
        echo json_encode(['result' => false, 'resultText' => 'Прикрепите файл подтверждения']);
        exit;
    }
    $fileIds = json_decode($violation['fix_files'] ?? '[]', true) ?: [];
}

try {
    $db->update('checksviolations', $violationId, [
        'fix_status'  => 1,
        'fix_files'   => json_encode($fileIds),
        'fix_comment' => $fixComment ?: null,
        'check_comment' => null, // сбрасываем комментарий возврата
    ]);

    echo json_encode([
        'result'     => true,
        'resultText' => 'Подтверждение устранения отправлено на проверку.',
    ]);

} catch (Exception $e) {
    echo json_encode(['result' => false, 'resultText' => 'Ошибка: ' . $e->getMessage()]);
}
