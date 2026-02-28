<?php
session_start();
// Генерируем одноразовый challenge
$_SESSION['auth_challenge'] = bin2hex(random_bytes(32));
echo json_encode(['challenge' => $_SESSION['auth_challenge']]);