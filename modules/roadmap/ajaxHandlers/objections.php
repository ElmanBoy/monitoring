<?php

use Core\Registry;
use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db = new Db();
$user_signs = [];
$err = 0;
$errStr = [];
$result = false;
$errFields = [];
$empty = 0;

$docId = intval($_POST['act_id']);
$userId = intval($_POST['user_id']);
$message = '';

if(count($_POST['objections']) == 0){
    $err++;
    $errStr[] = 'Укажите возражения';
}else {
    for ($i = 0; $i < count($_POST['objections']); $i++) {
        if(strlen(trim($_POST['objections'][$i])) == 0){
            $empty++;
        }
    }
    if($empty == count($_POST['objections'])){
        $err++;
        $errStr[] = 'Укажите возражения';
        $errFields[] = 'objections';
    }
}

if($err == 0) {
    for($i = 0; $i < count($_POST['objections']); $i++) {
        $result = $db->update('checksviolations', $_POST['violation_id'][$i],
            ['objections' => addslashes(strip_tags($_POST['objections'][$i]))]);
    }
    $result = true;
    $errStr[] = 'Возражения отправлены.';
}

echo json_encode(array(
    'result' => $result,
    'resultText' => implode('<br>', $errStr).($result ? '<script>el_app.reloadMainContent();el_app.dialog_close("agreement");</script>' : ''),
    'errorFields' => $errFields));

