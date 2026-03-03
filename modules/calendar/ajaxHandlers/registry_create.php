<?php
use Core\Db;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();
$regId = 38;
$db = new Db();
$reg = new Registry();
//print_r($_POST);

$regProps = $db->db::getAll('SELECT
            ' . TBL_PREFIX . 'regfields.prop_id AS fId,  
            ' . TBL_PREFIX . 'regprops.*
            FROM ' . TBL_PREFIX . 'regfields, ' . TBL_PREFIX . 'regprops
            WHERE ' . TBL_PREFIX . 'regfields.prop_id = ' . TBL_PREFIX . 'regprops.id AND 
            ' . TBL_PREFIX . 'regfields.reg_id = ? ORDER BY ' . TBL_PREFIX . 'regfields.sort', [$regId]
);
//Проверяем обязательные поля

if(count($_POST['institutions']) == 0){
    $err++;
    $errStr[] = 'Укажите проверяемые учреждения';
    $errorFields[] = 'institutions[]';
}else{
    $insArr = [];
    for($i = 0; $i < count($_POST['institutions']); $i++){
        $insArr[$i] = [
            'check_types' => $_POST['check_types'][$i],
            'institutions' => $_POST['institutions'][$i],
            'units' => $_POST['units'][$i],
            'periods' => $_POST['periods'][$i],
            'periods_hidden' => $_POST['periods_hidden'][$i],
            'inspections' => $_POST['inspections'][$i],
            'check_periods' => $_POST['check_periods'][$i],
        ];
    }
    $_POST['addinstitution'] = $insArr;
    $_POST['active'] = 0;
    $_POST['uid'] = uniqid();
}

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
    try {
        $db->insert($table->table_name, $registry);
        $result = true;
        $message = 'План успешно создан.
        <script>
        el_app.reloadMainContent();
        el_app.dialog_close("registry_create");
        </script>';
    } catch (\RedBeanPHP\RedException $e) {
        $result = false;
        $message = $e->getMessage();
    }

}else{
    $message = '<strong>Ошибка:</strong>&nbsp; '.implode('<br>', $errStr);
}
echo json_encode(array(
    'result' => $result,
    'resultText' => $message,
    'errorFields' => $errorFields));
?>