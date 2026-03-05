<?php
/**
 * Cron-скрипт отправки напоминаний по заданиям.
 *
 * Запускать каждые 5-15 минут:
 *   * * * * * php /var/www/html/cron/send_reminders.php >> /var/log/reminders.log 2>&1
 *
 * Находит записи в cam_reminders где datetime <= NOW() и отправляет уведомление.
 * После успешной отправки запись удаляется (внутри sendingRemind → removeRemind).
 */

// Для cron $_SERVER['DOCUMENT_ROOT'] не определён — задаём вручную
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

// connect.php использует session_start() — в cron сессия не нужна
// Подавляем заголовки и session
define('IS_CRON', true);

use Core\Db;
use Core\Notifications;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/vendor/autoload.php';

// Подгружаем .env вручную (connect.php вызывает session_start и header — не подходит для cron)
$dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
$dotenv->load();

define('TBL_PREFIX', getenv('TBL_PREFIX') ?: 'cam_');
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: '');

date_default_timezone_set('Europe/Moscow');

// Подключаем классы напрямую
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Classes/Db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Classes/Notifications.php';

$db = new Db();
$alert = new Notifications();

$now = date('Y-m-d H:i:s');

// Выбираем все напоминания у которых datetime <= текущего времени
$reminders = $db->select(
    'reminders',
    "WHERE datetime <= ? AND datetime IS NOT NULL AND datetime != ''",
    [$now]
);

if (empty($reminders)) {
    echo '[' . $now . '] Нет напоминаний для отправки.' . PHP_EOL;
    exit;
}

$sent = 0;
$errors = 0;

foreach ($reminders as $r) {
    try {
        $result = $alert->sendingRemind(intval($r->id));
        if ($result) {
            $sent++;
            echo '[' . date('Y-m-d H:i:s') . '] Напоминание #' . $r->id . ' отправлено.' . PHP_EOL;
        } else {
            // Email не настроен или не указан — удаляем чтобы не накапливались
            $alert->removeRemind(intval($r->id));
            echo '[' . date('Y-m-d H:i:s') . '] Напоминание #' . $r->id . ': email не отправлен (нет адреса), запись удалена.' . PHP_EOL;
        }
    } catch (Exception $e) {
        $errors++;
        echo '[' . date('Y-m-d H:i:s') . '] Ошибка напоминания #' . $r->id . ': ' . $e->getMessage() . PHP_EOL;
    }
}

echo '[' . date('Y-m-d H:i:s') . "] Итого: отправлено $sent, ошибок $errors." . PHP_EOL;
