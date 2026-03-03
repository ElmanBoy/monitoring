<?php
use Core\Db;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();
$regId = 38; //План проверок
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
    $row = $db->selectOne($table->table_name, ' where id = ?', [$rowId]);

    $last = $db->db::getRow('SELECT MAX(version) AS last_version, uid FROM ' . TBL_PREFIX . $table->table_name." 
    WHERE uid = '" . $row->uid . "' GROUP BY uid");
    reset($regProps);
    $registry = [
        'created_at' => date('Y-m-d H:i:s'),
        'author' => $_SESSION['user_id'],
        'active' => 0,
        'uid' => $last['uid']
    ];
    //Подгатавливаем данные для ввода в БД
    foreach ($regProps as $f) {
        $value = $reg->prepareValues($f, $_POST);
        $registry[$f['field_name']] = $value;
    }
    //Если план еще не утвержден, то можно редактировать. Иначе создаем новую версию плана
    if(intval($row->active) == 0) {
        $result = $db->update($table->table_name, $rowId, $registry);
    }else{
        $registry['version'] = $last['last_version'] + 1;
        $result = $db->insert($table->table_name, $registry);
    }
    try {
        $message = 'Элемент справочника успешно изменён.
        <script>
        el_app.reloadMainContent();
        el_app.dialog_close("registry_edit");
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