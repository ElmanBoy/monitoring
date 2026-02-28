<?php
use Core\Files;
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$files = new Files();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $fileId = (int)$_GET['id'];

    // Дополнительная валидация
    if ($fileId <= 0) {
        http_response_code(400);
        echo 'Неверный ID файла';
        exit;
    }

    $files->downloadFile($fileId);
} else {
    http_response_code(400);
    echo 'Не указан ID файла';
}