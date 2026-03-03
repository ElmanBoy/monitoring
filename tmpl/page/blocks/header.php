<?php

use Core\Gui;
use Core\Auth;
use Core\Notifications;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$gui = new Gui;
$auth = new Auth();
$notes = new Notifications();

// В режиме разработки каждый запрос получает уникальный timestamp — браузер не кеширует CSS/JS.
// В продакшне используется стабильная версия из APP_VERSION.
$assetVersion = (defined('APP_ENV') && APP_ENV === 'development') ? time() : APP_VERSION;

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Подсистема контроля и надзора</title>
    <meta http-equiv='Cache-Control' content='no-cache, no-store, must-revalidate'>
    <meta http-equiv='Pragma' content='no-cache'>
    <meta http-equiv='Expires' content='0'>
    <link rel="icon" type="image/svg+xml" href="/favicons/favicon.svg"/>
    <link rel="shortcut icon" href="/favicons/favicon.ico"/>
    <meta name="apple-mobile-web-app-title" content="Система мониторинга"/>
    <link rel='manifest' href='/manifest.json'>
    <meta name="theme-color" content="#ffffff"/>
    <link rel="icon" href="/favicons/web-app-manifest-192x192.png"/>
    <link rel="apple-touch-icon" href="/favicons/web-app-manifest-192x192.png"/>
    <link href="/css/start.css?v=<?= $assetVersion ?>" rel="stylesheet"/>
    <link href="/css/fonts.css" rel="stylesheet"/>
    <link href="/css/button.css?v=<?= $assetVersion ?>" rel="stylesheet"/>
    <link href="/css/check-radio.css?v=<?= $assetVersion ?>" rel="stylesheet"/>
    <link href="/css/el-data.css?v=<?= $assetVersion ?>" rel="stylesheet"/>
    <link href="/css/pop_up.css?v=<?= $assetVersion ?>" rel="stylesheet"/>
    <!-- <link href="/css/pie-charts.css" rel="stylesheet" />-->
    <link href="/css/style00.css?v=<?= $assetVersion ?>" rel="stylesheet"/>
    <link href="/css/tipsy.css" rel="stylesheet"/>
    <link href="/js/assets/flatpickr.min.css" rel="stylesheet"/>
    <link href="/js/assets/chosen/chosen.css" rel="stylesheet"/>
    <link href='/css/suggestions.min.css' rel='stylesheet'/>
    <link href="/js/assets/toast/jquery.toast.min.css" rel="stylesheet"/>
    <link href="/js/assets/jquery-ui-1.12.1.custom/jquery-ui.css" rel="stylesheet"/>
    <link href="/js/assets/frappe-gantt/dist/frappe-gantt.css?v=<?= $assetVersion ?>" rel='stylesheet'/>
    <link href="/css/breadcrumb.css?v=<?= $assetVersion ?>" rel='stylesheet'/>
    <script src="/js/assets/jquery-3.6.0.min.js"></script>
    <script src="/js/assets/jquery.el_select1.js?v=<?= $assetVersion ?>"></script>
    <script src="/js/assets/jquery.el_suggest.js?v=<?= $assetVersion ?>"></script>
    <script src="/js/assets/jquery.blockCloner.js"></script>
    <script src="/js/assets/jquery-ui.min.js"></script>
    <script src="/js/assets/jquery.maskedinput.js"></script>
    <script src="/js/assets/toast/jquery.toast.min.js"></script>
    <script src="/js/assets/flatpickr.js"></script>
    <script src="/js/assets/flatpickr/l10n/ru.js"></script>
    <script src="/js/assets/tipsy.min.js"></script>
    <script src="/js/assets/chosen/chosen.jquery.js"></script>
    <script src='/js/assets/jquery.mjs.nestedSortable.js'></script>
    <script src="/js/core/tools.js?v=<?= $assetVersion ?>"></script>
    <script src="/js/core/app.js?v=<?= $assetVersion ?>"></script>
    <script src="/js/core/suggest.js?v=<?= $assetVersion ?>"></script>
    <script src="/js/core/autocomplete.js?v=<?= $assetVersion ?>"></script>
    <script src='/js/assets/crypto-pro-js.min.js'></script>
    <script src='/js/assets/echarts.min.js'></script>
    <script src='/js/assets/cades_sign.js'></script>
    <script src='/js/assets/jquery.suggestions.min.js'></script>
    <script src='/js/assets/quarter_select.js?v=<?= $assetVersion ?>'></script>
    <script src="/js/assets/tinymce/js/tinymce/tinymce.min.js"></script>
    <script src="/js/assets/frappe-gantt/dist/frappe-gantt.umd.js?v=<?= $assetVersion ?>"></script>
    <link href='/js/assets/fullcalendar-6.1.19/dist/main.min.css' rel='stylesheet'>
    <script src='/js/assets/fullcalendar-6.1.19/dist/index.global.premium.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales/ru.global.min.js'></script>
    <!--<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/@event-calendar/build@4.5.1/dist/event-calendar.min.css'>
    <script src='https://cdn.jsdelivr.net/npm/@event-calendar/build@4.5.1/dist/event-calendar.min.js'></script>-->
    <script src='https://api-maps.yandex.ru/2.1/?lang=ru-RU&amp;apikey=<?= defined('YANDEX_MAPS_KEY') ? YANDEX_MAPS_KEY : '' ?>' type='text/javascript'></script>
    <script>
        // ИСПРАВЛЕНИЕ #3: токены вынесены из JS-файлов в PHP-шаблон.
        // Значения берутся из конфига на сервере — не хардкодятся в клиентском коде.
        // Для смены токена достаточно обновить одно место.
        window.DADATA_TOKEN = '<?= defined('DADATA_TOKEN') ? DADATA_TOKEN : '' ?>';
        window.YANDEX_MAPS_KEY = '<?= defined('YANDEX_MAPS_KEY') ? YANDEX_MAPS_KEY : '' ?>';
        var dialogAutoOpen = <?=($_SESSION['user_settings'][0][3]['view_settings']['auto_open'] == '1' ? 'true' : 'false')?>;
        <?=(isset($_SESSION['user_innerPhone'])) ? 'var innerPhone = ' . $_SESSION['user_innerPhone'] . ';' : ''?>
        var pingTimer = null;
        var remainingTime = 0;
        var userId = '<?=$_SESSION['user_id']?>';
        var notificationSound = '<?=$_SESSION['user_settings'][0]['notificationSound']?>';
    </script>
    <link href="/css/custom.css?v=<?= $assetVersion ?>" rel="stylesheet"/>


    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.2.0/chart.min.js" integrity="sha512-VMsZqo0ar06BMtg0tPsdgRADvl0kDHpTbugCBBrL55KmucH6hP9zWdLIWY//OTfMnzz6xWQRxQqsUFefwHuHyg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script> -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/chartist.js/latest/chartist.min.css">
    <script src="https://cdn.jsdelivr.net/chartist.js/latest/chartist.min.js"></script>-->
</head>