<?php
use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();
$checkbox_array = [];
$option_array = [];

$db = new Db;

if(strlen(trim($_POST['prop_name'])) == 0){
    $err++;
    $errStr[] = 'Укажите название поля';
    $errorFields[] = 'name';
}
if(strlen(trim($_POST['field_name'])) == 0){
    $err++;
    $errStr[] = 'Укажите название поля на английском языке';
    $errorFields[] = 'field_name';
}
if(strlen(trim($_POST['field_types'])) == 0){
    $err++;
    $errStr[] = 'Укажите тип поля';
    $errorFields[] = 'field_types';
}

$exist = $db->selectOne("checkprops", ' where name = ? OR field_name = ?', [$_POST['name'], $_POST['field_name']]);

if(intval($exist->id) > 0 && $_POST['name'] == $exist->name){
    $err++;
    $errStr[] = 'Поле с таким названием уже есть.<br>Выберите другое название';
    $errorFields[] = 'name';
}
if(intval($exist->id) > 0 && $_POST['field_name'] == $exist->field_name){
    $err++;
    $errStr[] = 'Поле с таким названием на английском языке уже есть.<br>Выберите другое название';
    $errorFields[] = 'field_name';
}

if(isset($_POST['radio_label']) && count($_POST['radio_label']) > 0 && strlen(trim($_POST['radio_label'][0])) > 0){
    $checkbox_array[] = ['title' => $_POST['radio_title']];
    for($i = 0; $i < count($_POST['radio_label']); $i++){
        $checkbox_array[] = [
            'value' => $_POST['radio_value'][$i],
            'label' => $_POST['radio_label'][$i],
        ];
    }
}

if(isset($_POST['option_label']) && count($_POST['option_label']) > 0 && strlen(trim($_POST['option_label'][0])) > 0){
    for($i = 0; $i < count($_POST['option_label']); $i++){
        $option_array[] = [
            'value' => $_POST['option_value'][$i],
            'label' => $_POST['option_label'][$i],
        ];
    }
}

if($err == 0) {
    $registry = array(
        'active' => 1,
        'name' => $_POST['prop_name'],
        'parent' => $_POST['parent'],
        'comment' => $_POST['comment'],
        'type' => $_POST['field_types'],
        'size' => $_POST['size'],
        'cols' => $_POST['cols'],
        'rows' => $_POST['rows'],
        'min_value' => $_POST['min_value'],
        'max_value' => $_POST['max_value'],
        'options_list' => count($option_array) > 0 ? json_encode($option_array) : null,
        'checkbox_values' => count($checkbox_array) > 0 ? json_encode($checkbox_array) : null,
        'from_db' => $_POST['fromdb'],
        'from_db_value' => $_POST['fromdb_value'],
        'from_db_text' => $_POST['fromdb_fields'],
        'default_value' => $_POST['default_value'],
        'placeholder' => $_POST['placeholder'],
        'calendar_type' => $_POST['calendar_type'],
        'default_currdate' => isset($_POST['curr_date']) ? 1 : 0,
        'default_currtime' => isset($_POST['curr_time']) ? 1 : 0,
        'default_currdatetime' => isset($_POST['curr_datetime']) ? 1 : 0,
        'author_id' => $_SESSION['user_id'],
        'label' => $_POST['prop_name'],
        'field_name' => $_POST['field_name']
    );
    try {
        $db->insert('checkprops', $registry);
    } catch (\RedBeanPHP\RedException $e) {
        $errStr[] = $e->getMessage();
    }
    $result = true;
    $message = 'Поле успешно создано.<script>el_app.reloadMainContent();el_app.dialog_close("prop_create");
    </script>';
}else{
    $message = '<strong>Ошибка:</strong><br> '.implode('<br>', $errStr);
}
echo json_encode(array(
    'result' => $result,
    'resultText' => $message,
    'errorFields' => $errorFields));
?>