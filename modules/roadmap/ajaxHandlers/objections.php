<?php

use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db          = new Db();
$err         = 0;
$errStr      = [];
$result      = false;
$errFields   = [];
$empty       = 0;

$docId  = intval($_POST['act_id']);
$userId = intval($_POST['user_id']);

$objections   = $_POST['objections']   ?? [];
$violationIds = $_POST['violation_id'] ?? [];

if (count($objections) === 0) {
    $err++;
    $errStr[] = 'Укажите возражения';
} else {
    foreach ($objections as $o) {
        if (strlen(trim($o)) === 0) $empty++;
    }
    if ($empty === count($objections)) {
        $err++;
        $errStr[]    = 'Укажите возражения';
        $errFields[] = 'objections';
    }
}

if ($err === 0) {
    for ($i = 0; $i < count($objections); $i++) {
        $db->update('checksviolations', intval($violationIds[$i]),
            ['objections' => addslashes(strip_tags($objections[$i]))]);
    }
    $result   = true;
    $errStr[] = 'Возражения отправлены.';
}

echo json_encode([
    'result'      => $result,
    'resultText'  => implode('<br>', $errStr) .
        ($result ? '<script>el_app.reloadMainContent();el_app.dialog_close("agreement");</script>' : ''),
    'errorFields' => $errFields,
]);