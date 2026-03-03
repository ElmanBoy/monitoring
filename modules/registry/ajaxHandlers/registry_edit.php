<?php
use Core\Db;
use Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$err = 0;
$errStr = [];
$result = false;
$errorFields = [];

$db = new Db;
$reg = new Registry();
$regId = intval($_POST['reg_id']);

if (strlen(trim($_POST['reg_name'])) == 0) {
    $err++;
    $errStr[] = 'Укажите название справочника';
    $errorFields[] = 'name';
}

// ИСПРАВЛЕНИЕ #1: Серверная валидация table_name
$tableName = trim($_POST['table_name'] ?? '');
if (strlen($tableName) == 0) {
    $err++;
    $errStr[] = 'Укажите название таблицы справочника на английском языке';
    $errorFields[] = 'table_name';
} elseif (!preg_match('/^[a-z][a-z0-9_]{0,59}$/', $tableName)) {
    $err++;
    $errStr[] = 'Название таблицы должно начинаться с буквы, содержать только строчные латинские буквы, цифры и знак подчёркивания, не длиннее 60 символов';
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
    [$_POST['reg_name'], $tableName]);

if (intval($exist->id) > 0 && intval($exist->id) != $regId && $_POST['reg_name'] == $exist->name) {
    $err++;
    $errStr[] = 'Справочник с таким названием уже есть.<br>Выберите другое название';
    $errorFields[] = 'name';
}

if (intval($exist->id) > 0 && intval($exist->id) != $regId && $tableName == $exist->table_name) {
    $err++;
    $errStr[] = 'Справочник с таким названием таблицы в базе данных уже есть.<br>Выберите другое название таблицы';
    $errorFields[] = 'table_name';
}

if ($err == 0) {
    try {
        $reg->updateRegistry($regId, $tableName,
            json_decode($_POST['reg_prop']), $_POST['comment']);

        $registry = [
            'name'       => $_POST['reg_name'],
            'table_name' => $tableName,
            'active'     => intval($_POST['active']),
            'comment'    => $_POST['comment'],
            'in_menu'    => intval($_POST['in_menu']),
            'icon'       => $_POST['icon'],
            'short_name' => $_POST['short_name'],
            'parent'     => intval($_POST['parent']),
            'roles'      => json_encode($_POST['roles'])
        ];
        $result = $db->update('registry', $regId, $registry);

        if ($result['result']) {
            $message = 'Справочник успешно изменён.<script>el_app.reloadMainContent();el_app.dialog_close("registry_edit");</script>';
        } else { $message = '<strong>Ошибка:</strong>&nbsp; ' . $result['resultText']; }
    } catch (\RedBeanPHP\RedException $e) {
        $result = false;
        $message = $e->getMessage();
    }
    $db->transactionClose(intval($_POST['trans_id']));
} else {
    $message = '<strong>Ошибка:</strong>&nbsp; ' . implode('<br>', $errStr);
}

echo json_encode([
    'result'      => $result,
    'resultText'  => $message,
    'errorFields' => $errorFields
]);