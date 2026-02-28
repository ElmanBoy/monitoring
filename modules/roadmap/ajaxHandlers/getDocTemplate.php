<?php

use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$db = new Db;
$props = $db->selectOne('documents', ' WHERE id = ?', [$_POST['temp_id']]);
echo json_encode($props);
