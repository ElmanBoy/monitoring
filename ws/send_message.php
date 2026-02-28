<?php
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;

require_once '/var/www/html/core/vendor/autoload.php'; // Подключаем автозагрузчик Composer

// Проверяем что классы доступны
/*if (class_exists('Minishlink\\WebPush\\WebPush')) {
    echo "✅ WebPush установлен успешно!\n";
} else {
    echo "❌ WebPush не найден\n";
}*/

    $connector = new Connector();
$serverUri = 'wss://monitoring.msr.mosreg.ru/websocket'; // Укажите адрес вашего WebSocket-сервера

$messageToSend = json_encode(['userId' => 1, 'title' => 'Тестовое уведомление', 'body' => "Проверить\nзвук"]); // Сообщение для отправки

$connector($serverUri)
    ->then(function(WebSocket $conn) use ($serverUri, $messageToSend) {
        echo "Подключено к серверу: $serverUri\n";

        // Отправляем сообщение
        if($conn->send($messageToSend) != false){
            echo "Отправлено сообщение: $messageToSend\n";
        }else{
            echo "Не удалось отправить сообщение\n";
        }

        // Закрываем соединение
        $conn->close();
    }, function($e) {
        echo "Не удалось подключиться: {$e->getMessage()}\n";
    });

/*\Ratchet\Client\connect('ws://crm.msr.mosreg.ru:3000')->then(function($conn) {
    $conn->on('message', function($msg) use ($conn) {
        echo "Received: {$msg}\n";
        $conn->close();
    });

    $conn->send('Hello World!');
}, function ($e) {
    echo "Could not connect: {$e->getMessage()}\n";
});*/

// Вместо вышеупомянутой функции send В сервере,
// Вы можете вызвать метод broadcast.
// Вам потребуется некоторый способ взаимодействия с сервером,
// например, обмен сообщениями через базу данных, файловую систему,
// или даже самим клиентом-отправителем (но этот клиент не должен обрабатывать полученные сообщения)
