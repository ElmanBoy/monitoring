<?php
/**
 * Загрузка подтверждающих документов об устранении нарушения
 */

use Core\Db;
use Core\Files;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db = new Db();
$files = new Files();
$auth = new Auth();

$result = false;
$message = '';

try {
    $agreementId = intval($_POST['agreement_id'] ?? 0);
    $violationIndex = intval($_POST['violation_index'] ?? 0);

    if ($agreementId === 0) {
        throw new Exception('Не указан ID графика');
    }

    // Получаем график
    $agreement = $db->selectOne('agreement', ' WHERE id = ? AND documentacial = 5', [$agreementId]);
    if (!$agreement) {
        throw new Exception('График не найден');
    }

    // Проверяем права доступа (ОК может загружать только для своего учреждения)
    $isObject = $auth->haveUserRole(5);
    if ($isObject) {
        $userInsId = intval($_SESSION['user_institution'] ?? 0);
        if ($userInsId === 0 || intval($agreement->ins_id) !== $userInsId) {
            throw new Exception('Доступ запрещён');
        }
    }

    // Декодируем список нарушений
    $schedule = json_decode($agreement->agreementlist ?? '[]', true) ?: [];

    if (!isset($schedule[$violationIndex])) {
        throw new Exception('Нарушение не найдено');
    }

    // Загружаем файлы
    $uploadedFiles = [];
    if (!empty($_FILES['evidence_files']['name'][0])) {
        foreach ($_FILES['evidence_files']['name'] as $key => $filename) {
            if ($_FILES['evidence_files']['error'][$key] === UPLOAD_ERR_OK) {
                $tmpFile = $_FILES['evidence_files']['tmp_name'][$key];
                $size = $_FILES['evidence_files']['size'][$key];

                // Сохраняем файл
                $fileId = $files->uploadFile(
                    $tmpFile,
                    $filename,
                    $_SESSION['user_id'],
                    'violation_evidence'
                );

                if ($fileId) {
                    $uploadedFiles[] = [
                        'file_id' => $fileId,
                        'filename' => $filename,
                        'uploaded_at' => date('Y-m-d H:i:s'),
                        'uploaded_by' => $_SESSION['user_id']
                    ];
                }
            }
        }
    }

    if (empty($uploadedFiles)) {
        throw new Exception('Не удалось загрузить файлы');
    }

    // Обновляем запись нарушения
    $fixComment = trim($_POST['fix_comment'] ?? '');
    $fixStatus = intval($_POST['fix_status'] ?? 1); // 1 - на проверке

    // Добавляем новые файлы к существующим
    $existingFiles = $schedule[$violationIndex]['fix_files'] ?? [];
    $schedule[$violationIndex]['fix_files'] = array_merge($existingFiles, $uploadedFiles);
    $schedule[$violationIndex]['fix_comment'] = $fixComment;
    $schedule[$violationIndex]['fix_status'] = $fixStatus;

    // Записываем историю изменения статуса
    $oldStatus = isset($schedule[$violationIndex]['old_fix_status'])
        ? intval($schedule[$violationIndex]['old_fix_status'])
        : 0;

    if ($oldStatus !== $fixStatus) {
        $db->insert('fixhistory', [
            'created_at' => date('Y-m-d H:i:s'),
            'agreement_id' => $agreementId,
            'violation_index' => $violationIndex,
            'violation_id' => intval($schedule[$violationIndex]['violation_id'] ?? 0),
            'old_status' => $oldStatus,
            'new_status' => $fixStatus,
            'changed_by' => $_SESSION['user_id'],
            'change_comment' => $fixComment,
            'attached_files' => json_encode($uploadedFiles, JSON_UNESCAPED_UNICODE)
        ]);
    }

    $schedule[$violationIndex]['old_fix_status'] = $fixStatus;

    // Сохраняем обновлённый график
    $db->update('agreement', $agreementId, [
        'agreementlist' => json_encode($schedule, JSON_UNESCAPED_UNICODE)
    ]);

    $result = true;
    $message = 'Документы успешно загружены. Статус нарушения обновлён.<script>el_app.reloadMainContent();</script>';

} catch (Exception $e) {
    $result = false;
    $message = 'Ошибка: ' . $e->getMessage();
}

echo json_encode([
    'result' => $result,
    'resultText' => $message,
    'data' => [
        'uploaded_count' => count($uploadedFiles ?? [])
    ]
]);
