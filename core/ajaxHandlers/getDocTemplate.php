<?php

use Core\Db;
use Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$db = new Db;
$reg = new Registry();
$tempId = intval($_POST['temp_id']);
if($tempId > 0) {
    $props = $db->selectOne('documents', ' WHERE id = ?', [$_POST['temp_id']]);
    $props->agreementlist = json_encode($reg->fixJsonString($props->agreementlist));
    echo json_encode($props);
}
