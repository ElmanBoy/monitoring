<?php
@session_start();
ini_set('display_errors','On');
error_reporting(E_ALL & ~E_NOTICE);
header("X-XSS-Protection: 1; mode=block");

require_once '/var/www/html/core/rb.php';


//Определение общих для всех модулей констант
define('ROOT', $_SERVER['DOCUMENT_ROOT']);
define('TBL_PREFIX', 'cam_');

define('ENCRYPTION_KEY', 'ab86d144e3f080b61c7c2e43');

/*
Статический ключ в Яндексе
Идентификатор ключа
YCAJEvwQxLD_Qs_nrrqZHy0HK
Ваш секретный ключ
YCMJKPR2RD2R6hmeRP4rM3CW4uHT7SVj_5FNqeDU
 */

date_default_timezone_set('Europe/Moscow');

/**
* Подключаемся к базе данных
* Последний (4-й) параметр по умолчанию выставлен в FALSE
* Если нужно применить заморозку таблиц в БД (отменить создание на лету),
* то нужно данный параметр выставить в TRUE
* или так: R::freeze(true);
*/
R::setup( 'pgsql:host=127.0.0.1;dbname=checkappmobile','app_mobile', 'Ilmn_^%aq', false);

// Проверка подключения к БД
R::fancyDebug( TRUE );
R::debug( TRUE, 1 );
if(!R::testConnection()) die('Нет соединения с базой данных!');
R::useJSONFeatures(TRUE);
//R::exec('SET NAMES utf8');
/*if($_POST['action'] != 'login' && $_GET['url'] != 'login' && !isset($_SESSION['login'])){
    header('Location: /login');
    echo '<script>document.location.href = "/"</script>';
}*/

/*
 *  minsoc.secure.mosreg.ru
 * Логин учетной записи ЕКП: BoyazitovEM
d22N1YLB

ssh root@10.12.123.241
elmanb Yufjh_12*71
 *
 */

require_once '/var/www/html/core/vendor/autoload.php';


?>
