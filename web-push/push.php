<?php
require_once 'config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

$db = new R();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = (int)$data['userId'];
    $subscription = $data['subscription'];

    // Сохраняем подписку
    $stmt = $db::exec('INSERT INTO cam_subscriptions (user_id, endpoint, p256dh, auth) 
        VALUES (
                '.$userId.', 
                \''.$subscription['endpoint'].'\', 
                \''.$subscription['keys']['p256dh'].'\', 
                \''.$subscription['keys']['auth'].'\'
                ) 
        ON CONFLICT (user_id, endpoint) 
        DO UPDATE SET 
            p256dh = EXCLUDED.p256dh, 
            auth = EXCLUDED.auth,
            updated_at = CURRENT_TIMESTAMP'
    );

    echo json_encode(['success' => true]);
}