<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require_once '/var/www/html/core/vendor/autoload.php';

class BroadcastingServer implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage; // Храним все подключения
    }

    public function onOpen(ConnectionInterface $conn) {
            // Храним новое соединение
        $this->clients->attach($conn);
        echo "Новое соединение! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        echo "Сообщение: \"{$msg}\" получено\n";
        $this->broadcast($msg);
    }

    public function onClose(ConnectionInterface $conn) {
        // Удаляем разорванное соединение
        $this->clients->detach($conn);
        echo "Соединение {$conn->resourceId} разорвано\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Ошибка: {$e->getMessage()}\n";
        $conn->close();
    }

    public function broadcast($message) {
        // Отправляем сообщение всем клиентам
        foreach ($this->clients as $client) {
            $client->send($message);
        }
    }
}
//>/dev/null 2>&1

// Путь к вашему SSL-сертификату и ключу
$sslContext = stream_context_create([
    'ssl' => [
        //'local_cert'  => '/root/ssl/_msr.mosreg.ru/_.msr.mosreg.ru.crt', // Замените на путь к вашему сертификату
        //'local_pk'    => '/root/ssl/_msr.mosreg.ru/_msr.mosreg.key', // Замените на путь к вашему закрытому ключу
        'verify_peer' => false, // Установите true для проверки доверия
        'allow_self_signed' => true
    ]
]);

// Запускаем сервер
$server = \Ratchet\Server\IoServer::factory(
    new \Ratchet\Http\HttpServer(
        new \Ratchet\WebSocket\WsServer(
            new BroadcastingServer()
        )
    ),
    3010,
    '0.0.0.0' // Слушаем на всех интерфейсах
);

// Теперь добавляем SSL
$result = stream_socket_server('tls://0.0.0.0:3011', $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $sslContext);

if (!$result) {
    throw new Exception("Не удалось запустить сервер: $errstr ($errno)");
}
try {
    $server->run();
}catch (\Exception $e){
    echo $e->getMessage();
}

