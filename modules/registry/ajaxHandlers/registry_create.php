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

// Валидация названия справочника
if (strlen(trim($_POST['reg_name'])) == 0) {
    $err++;
    $errStr[] = 'Укажите название справочника';
    $errorFields[] = 'reg_name';
}

// ИСПРАВЛЕНИЕ #1: Серверная валидация table_name — только строчные латинские буквы,
// цифры и подчёркивание, начиная с буквы, не длиннее 60 символов.
// Предотвращает SQL-инъекцию через DDL-запрос в createRegistry().
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

if (count($_POST['roles']) == 0) {
    $err++;
    $errStr[] = 'Укажите роли для доступа к этому справочнику';
    $errorFields[] = 'roles';
}

// Проверка уникальности названия и таблицы
$exist = $db->selectOne('registry', ' where name = ? OR table_name = ?',
    [$_POST['reg_name'], $tableName]);

if (intval($exist->id) > 0 && $_POST['reg_name'] == $exist->name) {
    $err++;
    $errStr[] = 'Справочник с таким названием уже есть.<br>Выберите другое название';
    $errorFields[] = 'reg_name';
}

if (intval($exist->id) > 0 && $tableName == $exist->table_name) {
    $err++;
    $errStr[] = 'Справочник с таким названием таблицы в базе данных уже есть.<br>Выберите другое название таблицы';
    $errorFields[] = 'table_name';
}

if ($err == 0) {
    try {
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
        $db->insert('registry', $registry);

        $reg->createRegistry($db->last_insert_id, $tableName,
            json_decode($_POST['reg_prop']), $_POST['comment']);

        $result = true;
        $message = 'Справочник успешно создан.<script>';
        if (!isset($_POST['path'])) {
            $message .= 'el_app.reloadMainContent();';
        }
        $message .= 'el_app.dialog_close("registry_create");</script>';

    } catch (\RedBeanPHP\RedException $e) {
        $result = false;
        $message = $e->getMessage();
    }
} else {
    $message = '<strong>Ошибка:</strong><br> ' . implode('<br>', $errStr);
}

echo json_encode([
    'result'      => $result,
    'resultText'  => $message,
    'errorFields' => $errorFields
]);