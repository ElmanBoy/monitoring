<?php
use Core\WebSocketServer;

require_once '/var/www/html/core/connect.php';

// Настройка WebSocket-сервера
$server = new \Ratchet\App('10.12.123.243', 3010);
$server->route('/websocket/', new WebSocketServer, ['*']);
$server->run();