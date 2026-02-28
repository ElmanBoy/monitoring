<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
$dotenv->load();
// VAPID конфигурация
define('VAPID_PUBLIC_KEY', getenv('VAPID_PUBLIC_KEY', true));
define('VAPID_PRIVATE_KEY', getenv('VAPID_PRIVATE_KEY', true));
define('VAPID_SUBJECT', getenv('VAPID_SUBJECT', true));