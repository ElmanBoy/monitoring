<?php
require_once 'config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

header('Content-Type: application/json');

$dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
$dotenv->load();

echo json_encode(['publicKey' => $_ENV['VAPID_PUBLIC_KEY']]);