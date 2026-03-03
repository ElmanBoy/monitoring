<?php
use Core\Db;
use Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();

$db = new Db;
$reg = new Registry();

if(strlen(trim($_POST['reg_name'])) == 0){
    $err++;
    $errStr[] = 'Укажите название справочника';
    $errorFields[] = 'reg_name';
}
if(strlen(trim($_POST['table_name'])) == 0){
    $err++;
    $errStr[] = 'Укажите название таблицы справочника на английском языке';
    $errorFields[] = 'table_name';
}

if(count($_POST['roles']) == 0){
    $err++;
    $errStr[] = 'Укажите роли для доступа к этому справочнику';
    $errorFields[] = 'roles';
}

$exist = $db->selectOne("registry", ' where name = ? OR table_name = ?',
    [$_POST['name'], $_POST['table_name']]);

if(intval($exist->id) > 0 && $_POST['name'] == $exist->name){
    $err++;
    $errStr[] = 'Справочник с таким названием уже есть.<br>Выберите другое название';
    $errorFields[] = 'name';
}

if(intval($exist->id) > 0 && $_POST['table_name'] == $exist->table_name){
    $err++;
    $errStr[] = 'Справочник с таким названием таблицы в базе данных уже есть.<br>Выберите другое название таблицы';
    $errorFields[] = 'table_name';
}

if($err == 0) {
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
        $db->insert('registry', $registry);

        //Записываем состав полей
        //И создаем таблицу
        $reg->createRegistry($db->last_insert_id, $_POST['table_name'],
            json_decode($_POST['reg_prop']), $_POST['comment']);

        $result = true;
        $message = 'Справочник успешно создан.<script>';
        if(!isset($_POST['path'])) {
            $message .= 'el_app.reloadMainContent();';
        }
        $message .= 'el_app.dialog_close("registry_create");
        </script>';
    } catch (\RedBeanPHP\RedException $e) {
        $result = false;
        $message = $e->getMessage();
    }

}else{
    $message = '<strong>Ошибка:</strong><br> '.implode('<br>', $errStr);
}
echo json_encode(array(
    'result' => $result,
    'resultText' => $message,
    'errorFields' => $errorFields));
?>