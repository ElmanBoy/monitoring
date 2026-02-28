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
    $errStr[] = 'Укажите название справочника';
    $errorFields[] = 'name';
}
if (strlen(trim($_POST['table_name'])) == 0) {
    $err++;
    $errStr[] = 'Укажите название таблицы справочника на английском языке';
    $errorFields[] = 'table_name';
}

if (strlen(trim($_POST['reg_prop'])) == 0) {
    $err++;
    $errStr[] = 'Справочник не может быть без полей. Добавьте хотя бы одно поле.';
    $errorFields[] = '';
}

if (count($_POST['roles']) == 0) {
    $err++;
    $errStr[] = 'Укажите роли для доступа к этому справочнику';
    $errorFields[] = 'roles';
}

$exist = $db->selectOne('registry', ' where name = ? OR table_name = ?',
    [$_POST['name'], $_POST['table_name']]
);

if (intval($exist->id) > 0 && intval($exist->id) != intval($_POST['reg_id']) && $_POST['name'] == $exist->name) {
    $err++;
    $errStr[] = 'Справочник с таким названием уже есть.<br>Выберите другое название';
    $errorFields[] = 'name';
}

if (intval($exist->id) > 0 && intval($exist->id) != intval($_POST['reg_id']) && $_POST['table_name'] == $exist->table_name) {
    $err++;
    $errStr[] = 'Справочник с таким названием таблицы в базе данных уже есть.<br>Выберите другое название таблицы';
    $errorFields[] = 'table_name';
}

if ($err == 0) {
    try {
        //Записываем состав полей
        //И создаем таблицу
        $reg->updateRegistry(intval($_POST['reg_id']), $_POST['table_name'],
            json_decode($_POST['reg_prop']), $_POST['comment']);

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
        $result = $db->update('registry', intval($_POST['reg_id']), $registry);


        $result = true;
        if ($result) {
            $message = 'Справочник успешно изменён.<script>el_app.reloadMainContent();el_app.dialog_close("registry_edit");</script>';
        } else {
            $message = '<strong>Ошибка:</strong>&nbsp; Не удалось изменить справочник.';
        }
    } catch (\RedBeanPHP\RedException $e) {
        $result = false;
        $message = $e->getMessage();
    }
    $db->transactionClose(intval($_POST['trans_id']));
} else {
    $message = '<strong>Ошибка:</strong>&nbsp; ' . implode('<br>', $errStr);
}
echo json_encode(array(
    'result' => $result,
    'resultText' => $message,
    'errorFields' => $errorFields)
);
?>