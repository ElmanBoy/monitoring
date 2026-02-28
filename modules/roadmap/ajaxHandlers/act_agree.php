<?php

use Core\Registry;
use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db = new Db();
$user_signs = [];

$docId = intval($_POST['act_id']);
$userId = intval($_POST['user_id']);
$message = '';

$signs = $db->select('signs', " where table_name = 'agreement' AND  doc_id = ?", [$docId]);
if (count($signs) > 0) {
    foreach ($signs as $s) {
        $user_signs[$s->user_id][$s->section] = ['type' => $s->type, 'date' => $s->created_at];
    }
}

$updateArr = [
    'act_agree' => $userId
];
$result = $db->update('agreement', $docId, $updateArr);
if($result){
    $message = 'С актом ознакомлены.';
}

echo json_encode(array(
    'result' => $result,
    'resultText' => $message.'<script>el_app.reloadMainContent();</script>',
    'errorFields' => []));

