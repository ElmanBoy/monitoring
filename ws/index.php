<?php
use Core\Db;
use Core\Gui;
use Core\Auth;
use Core\WebSocketServer;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '/var/www/html/core/connect.php';
    $gui = new Gui();
    $server = new WebSocketServer();

    $requestId = $gui->genpass(10);
    $data = json_decode(file_get_contents('php://input'), true);
    if (!empty($data)) {
        @file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/call_in.txt', var_export($data, true), FILE_APPEND);
        echo json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'path' => '/',
            'status' => '200',
            'message' => 'data is accepted',
            'requestId' => $requestId
        ]
        );


// Обработка вебхука

        /*$json = file_get_contents('php://input');
        $data = json_decode($json, true);*/

        // Здесь вы можете выполнять обработку данных
        // Например, отправим полученные данные через WebSocket
        $server->broadcast(json_encode($data));

    } else {
        echo 'Не переданы данные.';
    }
}

/*if (!empty($_POST)) {
    $params = @join("\n", $_POST);
    @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/logs/call_in_post.txt', $params, FILE_APPEND);
    echo json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'path' => '/',
        'status' => '200',
        'message' => 'data is accepted',
        'requestId' => $requestId
    ]);
}else{
    echo 'Не переданы данные.';
}*/
?>