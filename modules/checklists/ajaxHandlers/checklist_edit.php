<?php

use Core\Db;
use Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();
$permissions = array();

$db = new Db;
$reg = new Registry();

if (strlen(trim($_POST['reg_name'])) == 0) {
    $err++;
    $errStr[] = 'Укажите название чек-листа';
    $errorFields[] = 'name';
}
if (strlen(trim($_POST['table_name'])) == 0) {
    $err++;
    $errStr[] = 'Укажите название таблицы чек-листа на английском языке';
    $errorFields[] = 'table_name';
}

if (strlen(trim($_POST['reg_prop'])) == 0) {
    $err++;
    $errStr[] = 'Чек-лист не может быть без полей. Добавьте хотя бы одно поле.';
    $errorFields[] = '';
}

if (count($_POST['roles']) == 0) {
    $err++;
    $errStr[] = 'Укажите роли для доступа к этому чек-листу';
    $errorFields[] = 'roles';
}

$exist = $db->selectOne('checklists', ' where name = ? OR table_name = ?',
    [$_POST['name'], $_POST['table_name']]
);

if (intval($exist->id) > 0 && intval($exist->id) != intval($_POST['reg_id']) && $_POST['name'] == $exist->name) {
    $err++;
    $errStr[] = 'Чек-лист с таким названием уже есть.<br>Выберите другое название';
    $errorFields[] = 'name';
}

if (intval($exist->id) > 0 && intval($exist->id) != intval($_POST['reg_id']) && $_POST['table_name'] == $exist->table_name) {
    $err++;
    $errStr[] = 'Чек-лист с таким названием таблицы в базе данных уже есть.<br>Выберите другое название таблицы';
    $errorFields[] = 'table_name';
}

if ($err == 0) {
    try {
        $registry = [
            'name' => $_POST['reg_name'],
            'table_name' => $_POST['table_name'],
            'active' => $_POST['active'],
            'comment' => $_POST['comment'],
            'in_menu' => intval($_POST['in_menu']),
            'icon' => $_POST['icon'],
            'short_name' => $_POST['short_name'],
            'parent' => intval($_POST['parent']),
            'roles' => json_encode($_POST['roles'])
        ];
        $result = $db->update('checklists', intval($_POST['reg_id']), $registry);
        //Записываем состав полей
        //И создаем таблицу
        $reg->updateChecklist(intval($_POST['reg_id']), $_POST['table_name'],
            json_decode($_POST['reg_prop']), $_POST['comment']);

        // result already set by $db->update()
        if ($result['result']) {
            $message = 'Чек-лист успешно изменён.<script>el_app.reloadMainContent();el_app.dialog_close("registry_edit");</script>';
        } else { $message = '<strong>Ошибка:</strong>&nbsp; ' . $result['resultText']; }
    } catch (\RedBeanPHP\RedException $e) {
        $result = false;
        $message = $e->getMessage();
    }
    $db->transactionClose(intval($_POST['trans_id']));
} else {
    $message = '<strong>Ошибка:</strong>&nbsp; ' . implode('<br>', $errStr);
}
echo json_encode(array(
    'result' => (is_array($result) ? $result['result'] : $result),
    'resultText' => $message,
    'errorFields' => $errorFields)
);
?>