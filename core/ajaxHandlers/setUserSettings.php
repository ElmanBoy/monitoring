<?php
use Core\Auth;

@session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$auth = new Auth();
$result = false;
$message = '';

if($auth->isLogin()){
    $new_settings[] = ['name' => $_POST['name'], 'value' => $_POST['value']];
    try {
        $auth->setUserSettings($new_settings);
        $result = true;
        $message = 'Настройки сохранены.';
    } catch (\RedBeanPHP\RedException $e) {
        $message = $e->getMessage();
    }
}

echo json_encode([
    'result' => $result,
    'resultText' => $message
]);