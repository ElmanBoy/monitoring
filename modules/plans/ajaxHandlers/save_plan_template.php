<?php

use Core\Db;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();
$regId = 45; //Шаблоны планов
$rowId = intval($_POST['reg_id']);
$permissions = array();

$db = new Db;
$reg = new Registry();

$regProps = $db->db::getAll('SELECT
            ' . TBL_PREFIX . 'regfields.prop_id AS fId, 
            ' . TBL_PREFIX . 'regfields.required AS required, 
            ' . TBL_PREFIX . 'regprops.*
            FROM ' . TBL_PREFIX . 'regfields, ' . TBL_PREFIX . 'regprops
            WHERE ' . TBL_PREFIX . 'regfields.prop_id = ' . TBL_PREFIX . 'regprops.id AND 
            ' . TBL_PREFIX . 'regfields.reg_id = ? ORDER BY ' . TBL_PREFIX . 'regfields.sort', [$regId]
);
//Проверяем обязательные поля
$_POST['name'] = $_POST['short'];
$_POST['active'] = 1;
if (!isset($_POST['institutions']) || count($_POST['institutions']) == 0) {
    $err++;
    $errStr[] = 'Укажите проверяемые учреждения';
    $errorFields[] = 'institutions[]';
} else {
    $insArr = [];
    for ($i = 0; $i < count($_POST['institutions']); $i++) {
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

if(strlen(trim($_POST['short'])) == 0){
    $err++;
    $errStr[] = 'Укажите короткое название шаблона';
    $errorFields[] = 'short';
}else{
    $sh = $db->select("plannames", " WHERE short = '".addslashes($_POST['short'])."'");
    if(count($sh) > 0){
        $err++;
        $errStr[] = 'Шаблон с таким названием уже есть. Задайте другое название.';
        $errorFields[] = 'short';
    }
}

foreach ($regProps as $f) {
    $check = $reg->checkRequiredField($regId, $f, $_POST);
    if (!$check['result']) {
        $err++;
        $errStr[] = $check['message'];
        $errorFields[] = $check['errField'];
    }
}

if ($err == 0) {
    $table = $db->selectOne('registry', ' where id = ?', [$regId]);
    $row = $db->selectOne($table->table_name, ' where id = ?', [$rowId]);

    $last = $db->db::getRow('SELECT MAX(version) AS last_version, uid FROM ' . TBL_PREFIX . $table->table_name . " 
    WHERE uid = '" . $row->uid . "' GROUP BY uid"
    );
    reset($regProps);
    $registry = [
        'created_at' => date('Y-m-d H:i:s'),
        'author' => $_SESSION['user_id'],
        'active' => 1,
        'uid' => $last['uid']
    ];
    //Подгатавливаем данные для ввода в БД
    foreach ($regProps as $f) {
        $value = $reg->prepareValues($f, $_POST);
        $registry[$f['field_name']] = $value;
    }

    try {
        $result = $db->insert($table->table_name, $registry);
        $message = 'Шаблон плана успешно схранён.<script>
        $("[name=planname]").append($("<option>", {value: "'.$db->last_insert_id.'", text: "'.$_POST['short'].'", selected: true}))
        .val("'.$db->last_insert_id.'").trigger("chosen:updated");
        </script>';
    } catch (\RedBeanPHP\RedException $e) {
        $result = false;
        $message = $e->getMessage();
    }
} else {
    $message = '<strong>Ошибка:</strong><br> ' . implode('<br>', $errStr);
}
echo json_encode(array(
    'result' => $result,
    'resultText' => $message,
    'errorFields' => $errorFields)
);
