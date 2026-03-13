<?php
/**
 * modules/roadmap/ajaxHandlers/update_road.php
 *
 * Обновляет статус устранения конкретной строки графика.
 * fix_action:
 *   submit  — ОК отправляет подтверждение (файл + комментарий) → fix_status = 1
 *   close   — министерство снимает нарушение                   → fix_status = 2
 *   return  — министерство возвращает на доработку             → fix_status = 3
 *   extend  — министерство продлевает срок                     → deadline_extended
 */

use Core\Db;
use Core\Auth;
use Core\Files;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db   = new Db();
$auth = new Auth();

$err         = 0;
$errStr      = [];
$errorFields = [];
$result      = false;

$roadId    = intval($_POST['road_id']);
$rowIdx    = intval($_POST['row_idx']);
$fixAction = trim($_POST['fix_action'] ?? '');
$isObject  = $auth->haveUserRole(5);

// Загружаем график
$road = $db->selectOne('agreement', ' WHERE id = ? AND documentacial = 5', [$roadId]);
if (!$road) {
    echo json_encode(['result' => false, 'resultText' => 'График не найден.', 'errorFields' => []]);
    exit;
}

$schedule = json_decode($road->agreementlist ?? '[]', true) ?: [];

if (!isset($schedule[$rowIdx])) {
    echo json_encode(['result' => false, 'resultText' => 'Строка не найдена.', 'errorFields' => []]);
    exit;
}

$row    = &$schedule[$rowIdx];
$fixSt  = intval($row['fix_status'] ?? 0);

switch ($fixAction) {

    // ОК: отправить на проверку
    case 'submit':
        if (!$isObject) {
            $err++;
            $errStr[] = 'Недостаточно прав.';
            break;
        }
        if (!in_array($fixSt, [0, 3])) {
            $err++;
            $errStr[] = 'Невозможно выполнить в текущем статусе.';
            break;
        }
        if (empty($_FILES['files']['name'][0])) {
            $err++;
            $errStr[]      = 'Прикрепите файл подтверждения.';
            $errorFields[] = 'files';
            break;
        }

        // Сохраняем файлы
        $uploadDir = '/uploads/roadmap/' . $roadId . '/';
        $serverDir = $_SERVER['DOCUMENT_ROOT'] . $uploadDir;
        if (!is_dir($serverDir)) {
            mkdir($serverDir, 0775, true);
        }

        $savedFiles = $row['fix_files'] ?? [];
        foreach ($_FILES['files']['name'] as $k => $name) {
            if ($_FILES['files']['error'][$k] !== UPLOAD_ERR_OK) continue;
            $ext      = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $safeName = time() . '_' . $k . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
            $dest     = $serverDir . $safeName;
            if (move_uploaded_file($_FILES['files']['tmp_name'][$k], $dest)) {
                $savedFiles[] = $uploadDir . $safeName;
            }
        }

        $row['fix_files']   = $savedFiles;
        $row['fix_comment'] = htmlspecialchars(trim($_POST['fix_comment'] ?? ''));
        $row['fix_status']  = 1;
        $message            = 'Подтверждение отправлено на проверку.';
        break;

    // Министерство: снять нарушение
    case 'close':
        if ($isObject) {
            $err++;
            $errStr[] = 'Недостаточно прав.';
            break;
        }
        if ($fixSt !== 1) {
            $err++;
            $errStr[] = 'Нарушение должно быть на проверке.';
            break;
        }
        $row['fix_status'] = 2;
        $message           = 'Нарушение снято.';
        break;

    // Министерство: вернуть на доработку
    case 'return':
        if ($isObject) {
            $err++;
            $errStr[] = 'Недостаточно прав.';
            break;
        }
        if ($fixSt !== 1) {
            $err++;
            $errStr[] = 'Нарушение должно быть на проверке.';
            break;
        }
        $checkComment = htmlspecialchars(trim($_POST['check_comment'] ?? ''));
        if (strlen($checkComment) === 0) {
            $err++;
            $errStr[]      = 'Укажите причину возврата.';
            $errorFields[] = 'check_comment';
            break;
        }
        $row['fix_status']    = 3;
        $row['check_comment'] = $checkComment;
        $row['fix_files']     = [];
        $row['fix_comment']   = '';
        $message              = 'Нарушение возвращено на доработку.';
        break;

    // Министерство: продлить срок
    case 'extend':
        if ($isObject) {
            $err++;
            $errStr[] = 'Недостаточно прав.';
            break;
        }
        $newDate = trim($_POST['deadline_extended'] ?? '');
        $reason  = htmlspecialchars(trim($_POST['extended_reason'] ?? ''));
        if (strlen($newDate) === 0 || !strtotime($newDate)) {
            $err++;
            $errStr[]      = 'Укажите корректный новый срок.';
            $errorFields[] = 'deadline_extended';
            break;
        }
        if (strlen($reason) === 0) {
            $err++;
            $errStr[]      = 'Укажите причину продления.';
            $errorFields[] = 'extended_reason';
            break;
        }
        $row['deadline_extended'] = $newDate;
        $row['extended_reason']   = $reason;
        $message                  = 'Срок устранения продлён до ' . date('d.m.Y', strtotime($newDate)) . '.';
        break;

    default:
        $err++;
        $errStr[] = 'Неизвестное действие.';
}

if ($err === 0) {
    $upd = $db->update('agreement', $roadId, [
        'agreementlist' => json_encode($schedule, JSON_UNESCAPED_UNICODE),
    ]);
    $result = $upd['result'] ?? false;
    if (!$result) {
        $message = $upd['resultText'] ?? 'Ошибка сохранения.';
    } else {
        $message .= '<script>el_app.reloadMainContent();</script>';
    }
} else {
    $message = '<strong>Ошибка:</strong><br>' . implode('<br>', $errStr);
}

echo json_encode([
    'result'      => $result,
    'resultText'  => $message,
    'errorFields' => $errorFields,
]);