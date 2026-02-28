<?php
// Подключение DomPDF
require $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
use Dompdf\Dompdf;
use Dompdf\Options;
/*// Настройки отображения ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Установка временной зоны
date_default_timezone_set('Europe/Moscow');



// Подключение RedBeanPHP
require $_SERVER['DOCUMENT_ROOT'].'/core/rb.php';

// Настройка подключения к PostgreSQL
R::setup( 'pgsql:host=127.0.0.1;dbname=checkappmobile','app_mobile', 'Ilmn_^%aq', false);

// Запрет изменения структуры таблиц
R::freeze(true);

// Установка демо-пользователя (для тестирования)
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Администратор';
$_SESSION['user_role'] = 'admin';*/

// Функция для безопасного вывода данных
function html($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

