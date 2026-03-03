<?php
@session_start();
ini_set('display_errors', 'On');
error_reporting(E_ALL & ~E_NOTICE);
header('X-XSS-Protection: 1; mode=block');

date_default_timezone_set('Europe/Moscow');

define('ROOT', $_SERVER['DOCUMENT_ROOT']);

// --- Загрузка .env ---
require_once ROOT . '/core/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(ROOT);
$dotenv->load();

// --- Константы приложения ---
define('TBL_PREFIX', getenv('TBL_PREFIX') ?: 'cam_');
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: '');

// --- Внешние API ---
define('DADATA_TOKEN', getenv('DADATA_TOKEN') ?: '');
define('YANDEX_MAPS_KEY', getenv('YANDEX_MAPS_KEY') ?: '');
define('YANDEX_TTS_TOKEN', getenv('YANDEX_TTS_TOKEN') ?: '');
define('TELEGRAM_BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN') ?: '');
define('EAIS_API_URL', getenv('EAIS_API_URL') ?: '');

// --- SMTP ---
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.mosreg.ru');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_FROM', getenv('SMTP_FROM') ?: '');
define('SMTP_BCC', getenv('SMTP_BCC') ?: '');

// --- Прокси ---
define('PROXY_URL', getenv('PROXY_URL') ?: '');

// Версия приложения — используется для cache-busting CSS/JS в шаблонах
define('APP_VERSION', getenv('APP_VERSION') ?: '1.0');

// Окружение: 'production' или 'development'
define('APP_ENV', getenv('APP_ENV') ?: 'production');

// --- VAPID (Web Push) ---
define('VAPID_PUBLIC_KEY', getenv('VAPID_PUBLIC_KEY') ?: '');
define('VAPID_PRIVATE_KEY', getenv('VAPID_PRIVATE_KEY') ?: '');
define('VAPID_SUBJECT', getenv('VAPID_SUBJECT') ?: '');

// --- Подключение к PostgreSQL через RedBeanPHP ---
require_once ROOT . '/core/rb.php';

R::setup(
    'pgsql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'),
    getenv('DB_USER'),
    getenv('DB_PASSWORD'),
    false
);

// Debug только в режиме разработки
if (getenv('APP_DEBUG') === 'true') {
    R::fancyDebug(true);
    R::debug(true, 1);
}

if (!R::testConnection()) {
    die('Нет соединения с базой данных!');
}

R::useJSONFeatures(true);
