<?php
use Core\Db;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

// Временное логирование для отладки
error_log("=== REGISTRY_ITEMS_CREATE CALLED === POST: " . json_encode($_POST));

$err = 0;
$errStr = array();
$result = false;
$errorFields = array();
$regId = intval($_POST['parent']);
$db = new Db();
$reg = new Registry();

$regProps = $db->db::getAll('SELECT
            ' . TBL_PREFIX . 'regfields.prop_id AS fId,  
            ' . TBL_PREFIX . 'regprops.*
            FROM ' . TBL_PREFIX . 'regfields, ' . TBL_PREFIX . 'regprops
            WHERE ' . TBL_PREFIX . 'regfields.prop_id = ' . TBL_PREFIX . 'regprops.id AND 
            ' . TBL_PREFIX . 'regfields.reg_id = ? ORDER BY ' . TBL_PREFIX . 'regfields.sort', [$regId]
);
//Проверяем обязательные поля
foreach ($regProps as $f) {
    $check = $reg->checkRequiredField($regId, $f, $_POST);
    if(!$check['result']){
        $err++;
        $errStr[] = $check['message'];
        $errorFields[] = $check['errField'];
    }
}

if($err == 0) {
    $table = $db->selectOne('registry', ' where id = ?', [$regId]);
    reset($regProps);
    $registry = [
        'created_at' => date('Y-m-d H:i:s'),
        'author' => $_SESSION['user_id']
    ];
    foreach ($regProps as $f) {
        $value = $reg->prepareValues($f, $_POST);
        $registry[$f['field_name']] = $value;
    }

    // Специальная обработка для таблицы users - проверка уникальности логина
    if ($table->table_name === 'users' && isset($registry['login'])) {
        $checkLogin = trim($registry['login']);
        error_log("[REGISTRY_CREATE] Table: users, checking login: " . $checkLogin);

        $existingUser = $db->selectOne('users', ' WHERE login = ?', [$checkLogin]);

        if ($existingUser) {
            error_log("[REGISTRY_CREATE] Login exists, generating unique login");
            // Генерируем уникальный логин добавляя цифры
            $baseLogin = $checkLogin;
            $counter = 1;
            do {
                $newLogin = $baseLogin . $counter;
                $existingUser = $db->selectOne('users', ' WHERE login = ?', [$newLogin]);
                $counter++;
            } while ($existingUser && $counter < 100);

            if ($existingUser) {
                $err++;
                $errStr[] = 'Не удалось сгенерировать уникальный логин. Попробуйте изменить логин вручную.';
                $errorFields[] = 'login';
                error_log("[REGISTRY_CREATE] Failed to generate unique login after 100 attempts");
            } else {
                $registry['login'] = $newLogin;
                $errStr[] = 'Логин был изменён на "' . $newLogin . '" (пользователь с таким именем уже существует)';
                error_log("[REGISTRY_CREATE] Login changed to: " . $newLogin);
            }
        }
    }

    if ($err == 0) {
        try {
            $db->insert($table->table_name, $registry);
            $result = true;
            $warningMsg = count($errStr) > 0 ? '<br><strong>Внимание:</strong> ' . implode('<br>', $errStr) : '';
            $message = 'Запись успешно создана.' . $warningMsg . '
            <script>
            el_app.reloadMainContent();
            el_app.dialog_close("registry_items_create");
            </script>';
        } catch (\RedBeanPHP\RedException $e) {
            $result = false;
            $message = $db->handleDbException($e);
            error_log("[REGISTRY_CREATE] Exception: " . $e->getMessage());
        }
    } else {
        $message = '<strong>Ошибка:</strong><br> '.implode('<br>', $errStr);
    }
}else{
    $message = '<strong>Ошибка:</strong><br> '.implode('<br>', $errStr);
}
echo json_encode(array(
    'result' => $result,
    'resultText' => $message,
    'errorFields' => $errorFields));
?>