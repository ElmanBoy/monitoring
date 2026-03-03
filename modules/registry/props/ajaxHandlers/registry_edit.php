<?php
use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$err = 0;
$errStr = [];
$result = false;
$errorFields = [];
$checkbox_array = [];
$radio_array = [];
$option_array = [];

$db = new Db;

$propName  = trim($_POST['prop_name'] ?? '');
$fieldName = trim($_POST['field_name'] ?? '');
$fieldType = trim($_POST['field_types'] ?? '');
$regId     = intval($_POST['reg_id']);

if (strlen($propName) == 0) {
    $err++;
    $errStr[] = 'Укажите название поля';
    $errorFields[] = 'prop_name';
}
if (strlen($fieldName) == 0) {
    $err++;
    $errStr[] = 'Укажите название поля на английском языке';
    $errorFields[] = 'field_name';
} elseif (!preg_match('/^[a-z][a-z0-9_]{0,59}$/', $fieldName)) {
    $err++;
    $errStr[] = 'Название поля на английском должно начинаться с буквы и содержать только строчные латинские буквы, цифры и знак подчёркивания';
    $errorFields[] = 'field_name';
}
if (strlen($fieldType) == 0) {
    $err++;
    $errStr[] = 'Укажите тип поля';
    $errorFields[] = 'field_types';
}

// ИСПРАВЛЕНИЕ #2: В оригинале проверялось $_POST['name'] вместо $propName
$existName = $db->select('regprops', ' where name = ? AND id <> ?', [$propName, $regId]);
if (count($existName) > 0) {
    $err++;
    $errStr[] = 'Поле с таким названием уже есть.<br>Выберите другое название';
    $errorFields[] = 'prop_name';
}

$existField = $db->select('regprops', ' where field_name = ? AND id <> ?', [$fieldName, $regId]);
if (count($existField) > 0) {
    $err++;
    $errStr[] = 'Поле с таким названием на английском языке уже есть.<br>Выберите другое название';
    $errorFields[] = 'field_name';
}

// Сборка radio-значений
if (isset($_POST['radio_label']) && count($_POST['radio_label']) > 0 &&
    strlen(trim($_POST['radio_label'][0])) > 0) {
    $radio_array[] = ['title' => $_POST['radio_title']];
    for ($i = 0; $i < count($_POST['radio_label']); $i++) {
        $radio_array[] = [
            'value' => $_POST['radio_value'][$i],
            'label' => $_POST['radio_label'][$i],
        ];
    }
}

// Сборка checkbox-значений
if (isset($_POST['checkbox_label']) && count($_POST['checkbox_label']) > 0 &&
    strlen(trim($_POST['checkbox_label'][0])) > 0) {
    for ($i = 0; $i < count($_POST['checkbox_label']); $i++) {
        $checkbox_array[] = [
            'value' => $_POST['checkbox_value'][$i],
            'label' => $_POST['checkbox_label'][$i],
        ];
    }
}

// Сборка select-значений
if (isset($_POST['option_label']) && count($_POST['option_label']) > 0 &&
    strlen(trim($_POST['option_label'][0])) > 0) {
    for ($i = 0; $i < count($_POST['option_label']); $i++) {
        $option_array[] = [
            'value' => $_POST['option_value'][$i],
            'label' => $_POST['option_label'][$i],
        ];
    }
}

if ($err == 0) {
    $registry = [
        'active'               => 1,
        'name'                 => $propName,
        'parent'               => $_POST['parent'] ?? 0,
        'comment'              => $_POST['comment'] ?? '',
        'type'                 => $fieldType,
        'size'                 => intval($_POST['size'] ?? 0),
        'cols'                 => intval($_POST['cols'] ?? 0),
        'rows'                 => intval($_POST['rows'] ?? 0),
        'min_value'            => $_POST['min_value'] ?? '',
        'max_value'            => $_POST['max_value'] ?? '',
        'step'                 => intval($_POST['step'] ?? 0),
        'options_list'         => count($option_array) > 0 ? json_encode($option_array) : '[]',
        'checkbox_values'      => count($checkbox_array) > 0 ? json_encode($checkbox_array) : '[]',
        'radio_values'         => count($radio_array) > 0 ? json_encode($radio_array) : '[]',
        'from_db'              => $_POST['fromdb'] ?? '',
        'from_db_value'        => $_POST['fromdb_value'] ?? '',
        'from_db_text'         => $_POST['fromdb_fields'] ?? '',
        'default_value'        => $_POST['default_value'] ?? '',
        'placeholder'          => $_POST['placeholder'] ?? '',
        'calendar_type'        => $_POST['calendar_type'] ?? '',
        'default_currdate'     => isset($_POST['curr_date']) ? 1 : 0,
        'default_currtime'     => isset($_POST['curr_time']) ? 1 : 0,
        'default_currdatetime' => isset($_POST['curr_datetime']) ? 1 : 0,
        'author_id'            => intval($_SESSION['user_id']),
        'label'                => $propName,
        'field_name'           => $fieldName
    ];

    $result = $db->update('regprops', $regId, $registry);
    if ($result['result']) {
        $message = 'Поле успешно изменено.<script>el_app.reloadMainContent();el_app.dialog_close("registry_edit");</script>';
    } else { $message = '<strong>Ошибка:</strong>&nbsp; ' . $result['resultText']; }
    $db->transactionClose(intval($_POST['trans_id']));
} else {
    $message = '<strong>Ошибка:</strong><br> ' . implode('<br>', $errStr);
}

echo json_encode([
    'result'      => $result,
    'resultText'  => $message,
    'errorFields' => $errorFields
]);