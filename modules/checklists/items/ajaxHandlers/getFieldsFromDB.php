<?php

use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$db = new Db;

$regId = intval($_POST['reg_id']);
$selected = intval($_POST['selected']);
$selectedValue = intval($_POST['selectedValue']);
$out = [];

$regProps = $db->db::getAll('SELECT  
            ' . TBL_PREFIX . 'regprops.* AS name
            FROM ' . TBL_PREFIX . 'regfields, ' . TBL_PREFIX . 'regprops
            WHERE ' . TBL_PREFIX . 'regfields.prop_id = ' . TBL_PREFIX . 'regprops.id AND 
            ' . TBL_PREFIX . 'regfields.reg_id = ? ORDER BY ' . TBL_PREFIX . 'regfields.sort', [$regId]
);
$out['text'][] = '<option value=\'\'>&nbsp;</option>';
$out['value'][] = '<option value=\'0\'>id</option>';
foreach ($regProps as $f) {
    $out['text'][] = '<option value=\'' . $f['id'] . '\'' . ($selected == $f['id'] ? ' selected' : '') . '>' . stripslashes($f['name']) . '</option>';
    $out['value'][] = '<option value=\'' . $f['id'] . '\'' . ($selectedValue == $f['id'] ? ' selected' : '') . '>' . stripslashes($f['name']) . '</option>';
}

echo json_encode($out);