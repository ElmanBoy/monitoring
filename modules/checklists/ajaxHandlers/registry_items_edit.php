<?php
use Core\Db;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();
$regId = intval($_POST['parent']);
$rowId = intval($_POST['reg_id']);
$permissions = array();

$db = new Db;
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
        if(strlen($value) > 0) {
            $registry[$f['field_name']] = trim($value);
        }
    }
    $result = $db->update($table->table_name, $rowId, $registry);
    try {
        $message = 'Элемент справочника успешно изменён.
        <script>
        el_app.reloadMainContent();
        el_app.dialog_close("registry_items_edit");
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