<?php

use Core\Db;
use Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$err         = 0;
$errStr      = [];
$result      = false;
$errorFields = [];
$db          = new Db();
$reg         = new Registry();

$docId = intval($_POST['doc_id']);
$insId = intval($_POST['ins_id']);

// Проверяем дубликат
$existRoad = $db->selectOne('agreement', ' WHERE documentacial = 5 AND source_id = ?', [$docId]);
if ($existRoad) {
    echo json_encode([
        'result'      => false,
        'resultText'  => 'График для этого акта уже создан.',
        'errorFields' => [],
    ]);
    exit;
}

// Валидация
if (strlen(trim($_POST['header'] ?? '')) === 0) {
    $err++;
    $errStr[]      = 'Заполните верх документа';
    $errorFields[] = 'header';
}

$offers      = $_POST['schedule_offers']      ?? [];
$actions     = $_POST['schedule_actions']     ?? [];
$deadlines   = $_POST['schedule_deadlines']   ?? [];
$responsible = $_POST['schedule_responsible'] ?? [];
$violIds     = $_POST['violation_id']         ?? [];

if (!is_array($offers) || count($offers) === 0) {
    $err++;
    $errStr[]      = 'Добавьте минимум одну строку в график';
    $errorFields[] = 'schedule_offers';
} else {
    for ($i = 0; $i < count($offers); $i++) {
        if (strlen(trim($actions[$i] ?? '')) === 0) {
            $err++;
            $errStr[]      = 'Заполните действия для устранения в строке №' . ($i + 1);
            $errorFields[] = 'schedule_actions[' . $i . ']';
        }
        if (strlen(trim($deadlines[$i] ?? '')) === 0) {
            $err++;
            $errStr[]      = 'Заполните срок устранения в строке №' . ($i + 1);
            $errorFields[] = 'schedule_deadlines[' . $i . ']';
        }
        if (strlen(trim($responsible[$i] ?? '')) === 0) {
            $err++;
            $errStr[]      = 'Заполните ответственного в строке №' . ($i + 1);
            $errorFields[] = 'schedule_responsible[' . $i . ']';
        }
    }
}

if ($err === 0) {
    $schedule = [];
    for ($i = 0; $i < count($offers); $i++) {
        $schedule[] = [
            'violation_id'         => intval($violIds[$i] ?? 0),
            'schedule_offers'      => htmlspecialchars($offers[$i]),
            'schedule_actions'     => htmlspecialchars($actions[$i]),
            'schedule_deadlines'   => $deadlines[$i],
            'schedule_responsible' => htmlspecialchars($responsible[$i]),
            // Поля статуса устранения
            'fix_status'           => 0,
            'fix_comment'          => '',
            'fix_files'            => [],
            'check_comment'        => '',
            'deadline_extended'    => null,
            'extended_reason'      => '',
        ];
    }

    $registry = [
        'created_at'    => date('Y-m-d H:i:s'),
        'author'        => $_SESSION['user_id'],
        'header'        => $_POST['header'],
        'name'          => 'График устранения нарушений',
        'documentacial' => 5,
        'document'      => 18,
        'source_id'     => $docId,   // ссылка на акт
        'ins_id'        => $insId,
        'agreementlist' => json_encode($schedule, JSON_UNESCAPED_UNICODE),
    ];

    try {
        $db->insert('agreement', $registry);
        $result  = true;
        $message = 'График устранения нарушений успешно создан.<script>el_app.reloadMainContent();el_app.dialog_close("add_road");</script>';
    } catch (\RedBeanPHP\RedException $e) {
        $result  = false;
        $message = $e->getMessage();
    }
} else {
    $message = '<strong>Ошибка:</strong><br>' . implode('<br>', $errStr);
}

echo json_encode([
    'result'      => $result,
    'resultText'  => $message,
    'errorFields' => $errorFields,
]);