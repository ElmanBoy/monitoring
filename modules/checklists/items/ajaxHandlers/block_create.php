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

if(strlen(trim($_POST['block_name'])) == 0){
    $err++;
    $errStr[] = 'Укажите наименование блока';
    $errorFields[] = 'name';
}
if(strlen(trim($_POST['field_name'])) == 0){
    $err++;
    $errStr[] = 'Укажите название блока на английском языке';
    $errorFields[] = 'field_name';
}

$exist = $db->selectOne("checkitems", ' where name = ? OR field_name = ?', [$_POST['name'], $_POST['field_name']]);

if(intval($exist->id) > 0 && $_POST['block_name'] == $exist->name){
    $err++;
    $errStr[] = 'Блок с таким названием уже есть.<br>Выберите другое название';
    $errorFields[] = 'name';
}
if(intval($exist->id) > 0 && $_POST['field_name'] == $exist->field_name){
    $err++;
    $errStr[] = 'Блок с таким названием на английском языке уже есть.<br>Выберите другое название';
    $errorFields[] = 'field_name';
}


if($err == 0) {
    $registry = array(
        'active' => 1,
        'name' => $_POST['block_name'],
        'parent' => $_POST['parent'],
        'type' => 'block',
        'comment' => $_POST['comment'],
        'is_block' => '1',
        'author_id' => $_SESSION['user_id'],
        'label' => $_POST['block_name'],
        'field_name' => $_POST['field_name']
    );
    try {
        $db->insert('checkitems', $registry);
    } catch (\RedBeanPHP\RedException $e) {
        $errStr[] = $e->getMessage();
    }
    $result = true;
    $message = 'Пункт успешно создан.<script>el_app.reloadMainContent();el_app.dialog_close("block_create");
    </script>';
}else{
    $message = '<strong>Ошибка:</strong><br> '.implode('<br>', $errStr);
}
echo json_encode(array(
    'result' => $result,
    'resultText' => $message,
    'errorFields' => $errorFields));
?>