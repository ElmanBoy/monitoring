<?php
use Core\Db;
use Core\Date;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db();
$date = new Date();
$planId = intval($_POST['planId']);
$selected = intval($_POST['selected']);
$rowArr = ['<option value="0">&nbsp;</option>'];

$plan = $db->selectOne('checksplans', ' WHERE id = ?', [$planId]);

// Находим все учреждения в этом плане
$checkInstitutions = $db->select('checkinstitutions', ' WHERE plan_uid = ?', [$plan->uid]);

// Собираем ID записей checkinstitutions для фильтрации приказов
$checkInstIds = [];
if ($checkInstitutions) {
    foreach ($checkInstitutions as $ci) {
        $checkInstIds[] = intval($ci->id);
    }
}

// Ищем приказы только для этих учреждений в рамках плана
$or = [];
if (count($checkInstIds) > 0) {
    $idsStr = implode(',', $checkInstIds);
    $or = $db->db::getAll(
        "SELECT * FROM " . TBL_PREFIX . "agreement
         WHERE source_table = 'checkinstitutions'
         AND source_id IN ($idsStr)
         AND active = 1
         ORDER BY created_at DESC"
    );
}

if($or) {
    foreach ($or as $p) {
        $sel = ($selected == $p['id']) ? ' selected' : '';
        $rowArr[] = '<option value="' . $p['id'] . '"' . $sel . '>' . htmlspecialchars($p['name']) . '</option>';
    }
}

echo json_encode([
    'order' => implode("\n", $rowArr),
    'uid' => $plan->uid == null ? '0' : $plan->uid
]);
