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

if(isset($_GET['logout'])){
    unset($_SESSION['login']);
    session_destroy();
    header('Location:/');
    setcookie('last_path', '', time() - 3600, '/');
}

//Ajax-запрос перенаправляем на скрипт, указанный в $_POST['action']
if (isset($_POST['ajax']) && intval($_POST['ajax']) == 1) {
    if($_POST['action'] != 'login' && $_POST['action'] != 'mobileLogin') {
        if (!$auth->isLogin()) {
            echo '<script>alert("Ваша сессия устарела.");document.location.href = "/"</script>';
            die();
        }
    }
    if (ob_get_length()) ob_clean();
    header("Content-type: text/html; charset=utf-8");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);

    $path = preg_replace('/http(s*):\/\/'.$_SERVER['SERVER_NAME'].'\//', '', $_SERVER['HTTP_REFERER']);
    $pathArr = explode('?', $path);
    $path = str_replace(array('?', '#'), '', $pathArr[0]);

    //В зависимости от запрашиваемого режима определяем по какому пути искать и загружать скрипт
    $request_mode = $_POST['mode'] ?? '';
        switch ($request_mode) {
            //Если нужно отобразить попап
            case 'popup':
                $path = (isset($_POST['module'])) ? $_POST['module'] : $path;
                if (strlen($path) == 0) {
                    //header('Location: /main');
                    echo '<script>document.location.href="' . $auth->getDefaultPage() . '"</script>';
                    exit();
                }
                $dialogUrl = $_SERVER['DOCUMENT_ROOT'] . '/modules/' . $path . '/dialogs/' . $_POST['url'] . '.php';
                if (is_file($dialogUrl)) {
                    include_once $dialogUrl;
                } else {
                    $dialogUrl = $_SERVER['DOCUMENT_ROOT'] . '/core/ajaxHandlers/' . $_POST['url'] . '.php';
                    if (is_file($dialogUrl)) {
                        include_once $dialogUrl;
                    } else {
                        //header('Location: /main');
                        echo '<script>document.location.href="' . $auth->getDefaultPage() . '"</script>';
                        exit();
                    }
                }

                break;
            //Если нужно отобразить страницу
            case 'mainpage':
                $page = (isset($_POST['page']) && strlen($_POST['page']) > 0) ? $_POST['page'] : 'index';
                $pageUrl = $_SERVER['DOCUMENT_ROOT'] . '/modules/' . $_POST['url'] . '/pages/' . $page . '_ajax.php';
                if (is_file($pageUrl)) {
                    include_once $pageUrl;
                } else {
                    //header('Location: /main');
                    echo '<script>document.location.href="' . $auth->getDefaultPage() . '"</script>';
                    exit();
                }
                break;
            //Все остальные режимы
            default:
                $_POST['action'] = str_replace(array('.', '/'), '', $_POST['action']);
                $path = (isset($_POST['path'])) ? $_POST['path'] : $path;
                $ajaxHandler = $_SERVER['DOCUMENT_ROOT'] . '/modules/' . $path . '/ajaxHandlers/' . $_POST['action'] . '.php';
                if (is_file($ajaxHandler)) {
                    include_once $ajaxHandler;
                } else {
                    $ajaxHandler = $_SERVER['DOCUMENT_ROOT'] . '/core/ajaxHandlers/' . $_POST['action'] . '.php';
                    if (is_file($ajaxHandler)) {
                        include_once $ajaxHandler;
                    } else {
                        //header('Location: /main');
                        echo '<script>document.location.href="' . $auth->getDefaultPage() . '"</script>';
                        exit();
                    }
                }
                break;
        }

}else {

    //Создаем токен CSRF в cookie
    try {
        $csrfToken = $auth->buildToken();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    $_SESSION['csrf-token'] = $csrfToken;
    setcookie('CSRF-TOKEN', $csrfToken, 0, '/', $_SERVER['SERVER_NAME']/*, true*/);
;
    //Проверяем авторизацию
    if (!$auth->isLogin()) {
        //echo 'https://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        include_once __DIR__ . '/tmpl/page/login.php';
        setcookie('last_path', 'https://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 0, '/', $_SERVER['SERVER_NAME']/*, true*/);
        $_SESSION['login_path'] = 'https://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    } else {
        //Если авторизован, загружаем запрашиваемую информацию
        //$referer = str_replace('https://'.$_SERVER['SERVER_NAME'].'/', '', ($_SERVER['HTTP_REFERER'] ?? ''));

        //Получаем начальную страницу в зависимости от роли пользователя
        $default_page = $auth->getDefaultPage();

        //$default_page = (strlen($referer) > 0) ? $referer : $default_page;
        if (isset($_GET['url'])) {
            $default_page = $_GET['url'];
        }
        $end_path = $default_page.'/pages/index.php';
        if(substr_count(urldecode($default_page), '?') > 0){
            $path_arr = explode('?', urldecode($default_page));
            $end_path = $path_arr[0].'/pages/index.php';
        }
        include_once $_SERVER['DOCUMENT_ROOT'] . '/modules/'.$end_path;
    }
}
