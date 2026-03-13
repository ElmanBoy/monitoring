<?php
/**
 * modules/calendar/ajaxHandlers/save_objections.php
 *
 * Сохраняет возражения ОК по акту в поле objections таблицы cam_agreement.
 * Уведомляет министерство (автора акта / начальника управления).
 *
 * POST params:
 *   params[act_id]   — id записи в cam_agreement (documentacial=2)
 *   params[text]     — текст возражений
 *   objections_files — файлы (multipart)
 */

use Core\Db;
use Core\Auth;
use Core\Notifications;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db    = new Db();
$auth  = new Auth();
$alert = new Notifications();

$result     = false;
$resultText = '';

if (!$auth->checkAjax()) {
    echo json_encode(['result' => false, 'resultText' => 'Ошибка авторизации.']);
    die();
}

// Только ОК (роль 5)
if (!$auth->haveUserRole(5)) {
    echo json_encode(['result' => false, 'resultText' => 'Недостаточно прав.']);
    die();
}

$actId = intval($_POST['params']['act_id'] ?? 0);
$text  = trim($_POST['params']['text'] ?? '');

if ($actId === 0) {
    echo json_encode(['result' => false, 'resultText' => 'Не указан акт.']);
    die();
}

// Проверяем, что акт существует и подписан
$act = $db->selectOne('agreement', ' WHERE id = ? AND documentacial = 2', [$actId]);

if (!$act) {
    echo json_encode(['result' => false, 'resultText' => 'Акт не найден.']);
    die();
}
if (intval($act->status) !== 1) {
    echo json_encode(['result' => false, 'resultText' => 'Акт ещё не подписан. Возражения нельзя подать до подписания.']);
    die();
}
if (!is_null($act->report_id)) {
    echo json_encode(['result' => false, 'resultText' => 'Доклад по этому акту уже утверждён. Возражения не принимаются.']);
    die();
}

// ── Загрузка файлов ──────────────────────────────────────────
$fileIds = [];
if (!empty($_FILES['objections_files']['name'][0])) {
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/objections/' . $actId . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    foreach ($_FILES['objections_files']['name'] as $k => $fname) {
        if ($_FILES['objections_files']['error'][$k] !== UPLOAD_ERR_OK) continue;
        $safeName  = time() . '_' . preg_replace('/[^a-zA-Z0-9._\-]/', '_', basename($fname));
        $targetPath = $uploadDir . $safeName;
        if (move_uploaded_file($_FILES['objections_files']['tmp_name'][$k], $targetPath)) {
            $fid = $db->insert('files', [
                'created_at' => date('Y-m-d H:i:s'),
                'author'     => $_SESSION['user_id'],
                'name'       => $fname,
                'path'       => 'objections/' . $actId . '/' . $safeName,
                'size'       => $_FILES['objections_files']['size'][$k],
            ]);
            if ($fid) $fileIds[] = $fid;
        }
    }
}

if (strlen($text) === 0 && count($fileIds) === 0) {
    echo json_encode(['result' => false, 'resultText' => 'Введите текст возражений или прикрепите файлы.']);
    die();
}

// ── Сохраняем возражения в поле objections ───────────────────
// Если возражения уже были — дополняем файлами, обновляем текст
$existing = json_decode($act->objections ?? '{}', true) ?: [];
$existingFiles = $existing['files'] ?? [];

$objections = [
    'text'      => $text,
    'files'     => array_merge($existingFiles, $fileIds),
    'date'      => date('d.m.Y'),
    'author_id' => intval($_SESSION['user_id']),
];

$updateResult = $db->update('agreement', $actId, [
    'objections' => json_encode($objections, JSON_UNESCAPED_UNICODE),
]);

if ($updateResult['result']) {
    $result     = true;
    $resultText = 'Возражения успешно направлены в министерство.';

    // ── Уведомление автору акта / начальнику управления ──────
    // Ищем сотрудника с is_head=1 из проверяющей группы
    $headStaff = $db->selectOne(
        'checkstaff',
        ' WHERE institution = ? AND is_head = 1 ORDER BY id DESC LIMIT 1',
        [intval($act->ins_id ?? $act->source_id)]
    );
    $notifyUserId = intval($headStaff->user ?? 0);

    if ($notifyUserId > 0) {
        try {
            $alert->notificationSigner(
                $notifyUserId,
                4,
                $actId,
                'Возражения по акту «' . $act->name . '»'
            );
        } catch (Exception $e) {
            error_log('Ошибка уведомления о возражениях: ' . $e->getMessage());
        }
    }
} else {
    $resultText = 'Ошибка сохранения. Попробуйте ещё раз.';
}

echo json_encode([
    'result'     => $result,
    'resultText' => $resultText,
]);