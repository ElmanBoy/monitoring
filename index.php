<?php
/**
 * @TODO Создание таблицы справочника
 * todo Добавить вращение логотипа (preloader)
 * todo [сделано] Добавить в общем методе открытия попапа сдвиг окна, если уже есть открытое окно
 * todo Редактирование таблицы справочника во время добавления/удаления полей
 * todo Создать метод рендеринга редактируемой и нередактируемой формы (каждое поле в отдельном методе) с разбивкой на w_50 и w_100
 * todo (?) По возможности добавить поиск поля в общем списке полей при создании и редактировании справочника
 * todo (?) По возможности добавить drag&drop полей в редактируемой форме с обратной связью в списке полей справочника (ajax методом?)
 * todo Залить справочник СМП и другие из телеги
 * todo Конструктор шаблонов проверок с проверкой наличия шаблона по коду проверки при создании задания на проверку и в списке проверок
 * todo Логирование с указанием типа события
 *
 */

use Core\Auth;
use Core\Db;

header('Content-type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
//header('Clear-Site-Data: "cache"');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
//header("Content-Security-Policy: script-src 'self'");
@session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$auth = new Auth();
$db = new Db();

if (isset($_GET['logout'])) {
    unset($_SESSION['login']);
    session_destroy();
    header('Location:/');
    setcookie('last_path', '', time() - 3600, '/');
}

// ─────────────────────────────────────────────────────────────────────────────
// Вспомогательная функция проверки прав доступа к модулю.
// Читает module.json по пути модуля и проверяет view-право текущего пользователя.
// Если module.json не существует — это core-обработчик, проверку пропускаем.
// При запрете — выводит alert и завершает выполнение.
// ─────────────────────────────────────────────────────────────────────────────
$checkModuleAccess = function(string $modulePath) use ($auth): void {
    $moduleJson = $_SERVER['DOCUMENT_ROOT'] . '/modules/' . $modulePath . '/module.json';
    if (!is_file($moduleJson)) {
        return; // core-обработчики без module.json — не блокируем
    }
    $props    = json_decode(file_get_contents($moduleJson), true);
    $moduleId = intval($props['id'] ?? 0);
    if ($moduleId === 0) {
        return;
    }
    $perms = $auth->checkModulePermissions($moduleId);
    if (!($perms['view'] ?? false)) {
        echo '<script>alert("Доступ запрещён.");</script>';
        die();
    }
};

// ─────────────────────────────────────────────────────────────────────────────
// AJAX-запрос
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_POST['ajax']) && intval($_POST['ajax']) == 1) {
    $isPublicAction = in_array($_POST['action'] ?? '', ['login', 'mobileLogin']);

    if (!$isPublicAction) {
        // Проверяем авторизацию, X-Requested-With и CSRF-токен одним методом
        if (!$auth->checkAjax()) {
            echo '<script>alert("Ваша сессия устарела.");document.location.href = "/"</script>';
            die();
        }
    }
    if (ob_get_length()) ob_clean();
    header('Content-type: text/html; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Cache-Control: post-check=0, pre-check=0', false);

    $path = preg_replace('/http(s*):\/\/' . $_SERVER['SERVER_NAME'] . '\//', '', $_SERVER['HTTP_REFERER']);
    $pathArr = explode('?', $path);
    $path = str_replace(array('?', '#'), '', $pathArr[0]);

    // Санитизация входных параметров роутера — только буквы, цифры, _ и -
    // Предотвращает path traversal (../../etc/passwd и подобное)
    $sanitizePath = fn(string $v): string => preg_replace('/[^a-zA-Z0-9_\-]/', '', $v);

    // В зависимости от запрашиваемого режима определяем по какому пути искать и загружать скрипт
    $request_mode = $_POST['mode'] ?? '';
    switch ($request_mode) {

        // Если нужно отобразить попап
        case 'popup':
            $path    = $sanitizePath(isset($_POST['module']) ? $_POST['module'] : $path);
            $urlName = $sanitizePath($_POST['url'] ?? '');
            if (strlen($path) == 0) {
                echo '<script>document.location.href="' . $auth->getDefaultPage() . '"</script>';
                exit();
            }
            $checkModuleAccess($path);
            $dialogUrl = $_SERVER['DOCUMENT_ROOT'] . '/modules/' . $path . '/dialogs/' . $urlName . '.php';
            if (is_file($dialogUrl)) {
                include_once $dialogUrl;
            } else {
                $dialogUrl = $_SERVER['DOCUMENT_ROOT'] . '/core/ajaxHandlers/' . $urlName . '.php';
                if (is_file($dialogUrl)) {
                    include_once $dialogUrl;
                } else {
                    echo '<script>alert("Страница не найдена.");</script>';
                    exit();
                }
            }
            break;

        // Если нужно отобразить страницу
        case 'mainpage':
            $urlName = $sanitizePath($_POST['url'] ?? '');
            $page    = $sanitizePath((isset($_POST['page']) && strlen($_POST['page']) > 0) ? $_POST['page'] : 'index');
            $checkModuleAccess($urlName);
            $pageUrl = $_SERVER['DOCUMENT_ROOT'] . '/modules/' . $urlName . '/pages/' . $page . '_ajax.php';
            if (is_file($pageUrl)) {
                include_once $pageUrl;
            } else {
                echo '<script>alert("Страница не найдена.");</script>';
                exit();
            }
            break;

        // Все остальные режимы (ajaxHandlers)
        default:
            $action = $sanitizePath($_POST['action'] ?? '');
            $path   = $sanitizePath(isset($_POST['path']) ? $_POST['path'] : $path);
            $checkModuleAccess($path);
            $ajaxHandler = $_SERVER['DOCUMENT_ROOT'] . '/modules/' . $path . '/ajaxHandlers/' . $action . '.php';
            if (is_file($ajaxHandler)) {
                include_once $ajaxHandler;
            } else {
                $ajaxHandler = $_SERVER['DOCUMENT_ROOT'] . '/core/ajaxHandlers/' . $action . '.php';
                if (is_file($ajaxHandler)) {
                    include_once $ajaxHandler;
                } else {
                    echo '<script>alert("Обработчик не найден.");</script>';
                    exit();
                }
            }
            break;
    }

// ─────────────────────────────────────────────────────────────────────────────
// Обычный GET-запрос (загрузка страницы модуля)
// ─────────────────────────────────────────────────────────────────────────────
} else {

    // Создаём токен CSRF в cookie — генерируем только если ещё нет в сессии
    try {
        if (empty($_SESSION['csrf-token'])) {
            $_SESSION['csrf-token'] = $auth->buildToken();
        }
        $csrfToken = $_SESSION['csrf-token'];
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    setcookie('CSRF-TOKEN', $csrfToken, 0, '/', $_SERVER['SERVER_NAME']);

    // Проверяем авторизацию
    if (!$auth->isLogin()) {
        include_once __DIR__ . '/tmpl/page/login.php';
        setcookie('last_path', 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 0, '/', $_SERVER['SERVER_NAME']);
        $_SESSION['login_path'] = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    } else {
        $auth->refreshPermissions(); // пересчитываем права из БД при каждой загрузке страницы
        // Получаем начальную страницу в зависимости от роли пользователя
        $default_page = $auth->getDefaultPage();
        if (isset($_GET['url'])) {
            $default_page = $_GET['url'];
        }

        $end_path = $default_page . '/pages/index.php';
        if (substr_count(urldecode($default_page), '?') > 0) {
            $path_arr     = explode('?', urldecode($default_page));
            $default_page = $path_arr[0];
            $end_path     = $default_page . '/pages/index.php';
        }

        $checkModuleAccess($default_page);

        if (!is_file($_SERVER['DOCUMENT_ROOT'] . '/modules/' . $end_path)) {
            echo '<script>alert("Страница не найдена.");document.location.href="/";</script>';
            exit();
        }
        include_once $_SERVER['DOCUMENT_ROOT'] . '/modules/' . $end_path;
    }
}