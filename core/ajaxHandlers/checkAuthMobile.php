<?php

use Core\Auth;

@session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$auth = new Auth();
$result = [];
$input = 0;

$is_login = $auth->isLogin();

echo json_encode(array(
        'result' => $is_login,
        'resultText' => '')
);