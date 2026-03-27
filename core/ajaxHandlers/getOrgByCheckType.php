<?php
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db();
$auth = new Auth();

if (!$auth->isLogin()) {
    die();
}

$checkType = isset($_POST['check_type']) ? $_POST['check_type'] : '';
$checkTypeInt = intval($checkType);
$selected  = intval($_POST['selected'] ?? 0);

// Если тип проверки не выбран (пустой, 0 или отрицательный) — возвращаем все учреждения
if ($checkType === '' || $checkType === '0' || $checkTypeInt <= 0 || empty($checkType)) {
    $orgs = $db->db::getAll(
        'SELECT id, short FROM ' . TBL_PREFIX . 'institutions
         WHERE active = 1
         ORDER BY short'
    );

    echo '<option value=""></option>';
    foreach ($orgs as $o) {
        $sel = (intval($o['id']) === $selected) ? ' selected' : '';
        echo '<option value="' . $o['id'] . '"' . $sel . '>' .
            stripslashes(htmlspecialchars($o['short'])) .
            '</option>';
    }
    die();
}

// Находим типы организаций, которым разрешён данный тип проверки
// cam_orgtypes.checks — jsonb массив id типов проверок
$orgTypeIds = [];
$types = $db->db::getAll(
    'SELECT id FROM ' . TBL_PREFIX . 'orgtypes WHERE checks @> \'"' . $checkTypeInt . '"\'::jsonb OR checks @> \'[' . $checkTypeInt . ']\'::jsonb'
);

foreach ($types as $t) {
    $orgTypeIds[] = intval($t['id']);
}

if (count($orgTypeIds) === 0) {
    // Нет подходящих типов организаций — возвращаем пустой список с подсказкой
    echo '<option value="" disabled>Нет учреждений для данного типа проверки</option>';
    die();
}

// Загружаем учреждения нужных типов
$inIds = implode(',', $orgTypeIds);
$orgs = $db->db::getAll(
    'SELECT id, short FROM ' . TBL_PREFIX . 'institutions 
     WHERE orgtype IN (' . $inIds . ') AND active = 1
     ORDER BY short'
);

echo '<option value=""></option>';
foreach ($orgs as $o) {
    $sel = (intval($o['id']) === $selected) ? ' selected' : '';
    echo '<option value="' . $o['id'] . '"' . $sel . '>' .
        stripslashes(htmlspecialchars($o['short'])) .
        '</option>';
}