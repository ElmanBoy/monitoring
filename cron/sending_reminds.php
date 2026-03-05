<?php

use Core\Db;
use Core\Notifications;

include_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

// Проверяем что классы доступны
/*if (class_exists('Minishlink\\WebPush\\WebPush')) {
    echo "✅ WebPush установлен успешно!\n";
} else {
    echo "❌ WebPush не найден\n";
}*/

$alert = new Notifications();
$db = new Db();

$reminds = $db->select('reminders', " WHERE datetime <= '".date('Y-m-d H:i:s')."'");
//echo '<br>Count: '.count($reminds);
if(count($reminds) > 0){
    foreach ($reminds as $r){
        try {
            if (!$alert->sendingRemind($r->id)) {
                //echo $r->id.' Не отправлено!<br>';
            } else {
                //echo $r->id.' Отправлено!<br>';
            }
        } catch (ErrorException $e) {
        }
    }
}

//[[{"stage": "1", "urgent": "1", "list_type": "2"}, {"id": 2, "type": 2, "vrio": "0", "urgent": "1"}, {"id": 1, "type": 2, "vrio": "0", "urgent": "1"}], [{"stage": "", "urgent": "1", "list_type": "1"}, {"id": 1, "role": "0", "type": 1, "vrio": "0", "urgent": "1"}, {"id": 2, "role": "1", "type": 1, "vrio": "0", "urgent": "1"}]]