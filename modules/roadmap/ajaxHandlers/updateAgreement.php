<?php

use Core\Registry;
use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db = new Db();
$user_signs = [];

$docId = intval($_POST['docId']);
$message = '';

$signs = $db->select('signs', " where table_name = 'agreement' AND  doc_id = ?", [$docId]);
if (count($signs) > 0) {
    foreach ($signs as $s) {
        $user_signs[$s->user_id][$s->section] = ['type' => $s->type, 'date' => $s->created_at];
    }
}

$updateArr = [
    'created_at' => date('Y-m-d H:i:s'),
    'author' => $_SESSION['user_id'],
    'agreementlist' => json_encode($_POST['agreementList'])
];
$result = $db->update('agreement', $docId, $updateArr);
if($result){
    $message = 'Изменеия в документе сохранены.';
}

$check = $db->selectOne('agreement', ' WHERE id = ?', [$docId]);
$agreementList = json_decode($check->agreementlist, true);
$results = 0;
$signers = 0;
for($i = 0; $i < count($agreementList); $i++){
    $itemArr = json_decode($agreementList[$i], true);
    $signers += count($itemArr) - 1;
    for($l = 1; $l < count($itemArr); $l++){
        //Если нет перенаправления
        if(!isset($itemArr[$l]['redirect']) && !is_array($itemArr[$l]['redirect'])){
            //Если результат - это не перенапраление
            if(in_array($itemArr[$l]['result']['id'] , [1, 2, 3])){
                if(in_array($itemArr[$l]['result']['id'] , [1, 2])) {
                    if(isset($user_signs[$itemArr[$l]['id']][$i])
                        && in_array($user_signs[$itemArr[$l]['id']][$i]['type'], [1, 2])) {
                        $results++;
                    }
                }else{
                    $results++;
                }
            }
        }
    }
}

if($results == $signers){
    $message .= '<br>Документ согласован.';
    $result = $db->update('agreement', $docId, ['status' => 1]);
}else{
    $result = $db->update('agreement', $docId, ['status' => 0]);
}

echo json_encode(array(
    'result' => $result,
    'resultText' => $message.'<script>el_app.reloadMainContent();</script>',
    'errorFields' => []));

