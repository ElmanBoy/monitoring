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
$or = $db->select('agreement', " WHERE plan_id = ?", [$planId]); //print_r($or);
if($or) {
    foreach ($or as $id => $p) {
        $sel = ($selected == $p->id) ? ' selected' : '';
        $rowArr[] = '<option value="' . $p->id . '"' . $sel . '>' . $p->name . '</option>';
    }
}



echo json_encode([
    'order' => implode("\n", $rowArr),
    'uid' => $plan->uid == null ? '0' : $plan->uid
]);
