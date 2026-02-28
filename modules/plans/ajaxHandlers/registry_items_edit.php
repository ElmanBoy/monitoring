<?php
use Core\Db;
use \Core\Registry;
use \Core\Date;

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
$date = new Date();

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
    reset($regProps);
    $registry = [
        'created_at' => date('Y-m-d H:i:s'),
        'author' => $_SESSION['user_id']
    ];
    foreach ($regProps as $f) {
        $value = $reg->prepareValues($f, $_POST);
        $registry[$f['field_name']] = $value;
    }
    //Если план еще не утвержден, то можно редактировать. Иначе создаем новую версию плана
    if($table->active == 0) {
        $result = $db->update($table->table_name, $rowId, $registry);
    }else{
        $registry['version']++;
        $result = $db->insert($table->table_name, $registry);

        //Создаём документ приказа
        $agreement_header = '';
        $header_vars = [
            'today_date' => $date->dateToString(date('Y-m-d')),
            //'sign_kiriuhin' => $temp->getSign(json_decode($sign_vars[1], 'true')['certificate_info'])
        ];
        $agreement_header .= $temp->twig_parse($tmpl->header, $header_vars);

        $agreement_header .= $temp->twig_parse($plan->longname, ['year' => date('Y')]);

        $body_vars = [];
        $check_number = 1;
        foreach ($checks as $ch) {
            $body_vars[] = [
                'check_number' => $check_number,
                'institution' => stripslashes($ins['array'][$ch['institutions']]),
                'unit' => stripslashes($units['array'][$ch['units']]),
                'inspections' => stripslashes($insp['array'][$ch['inspections']]),
                'period' => $ch['periods'],
                'check_periods' => $ch['check_periods']
            ];
            $check_number++;
        }
        $agreement_body = $temp->twig_parse($tmpl->body, ['checks' => $body_vars]);

        $bottom_vars = [
            'today_date' => $date->dateToString(date('Y-m-d')),
            //'sign_liahova' => $temp->getSign(json_decode($sign_vars[4], true)['certificate_info']),
            'approval_sheet' => ''
        ];
        $agreement_bottom = $temp->twig_parse($tmpl->bottom, $bottom_vars);

        $db->insert('agreement', [
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'author' => $_SESSION['user_id'],
            'documentacial' => 3,
            'document' => $_POST['plan_template'],
            //'doc_number' => $_POST['doc_number'],
            'name' => $_POST['short'],
            'header' => $agreement_header,
            'body' => $agreement_body,
            'bottom' => $agreement_bottom,
            'signators' => json_encode($_POST['signators']),
            'status' => 0
        ]);


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
    $message = '<strong>Ошибка:</strong>&nbsp; '.implode('<br>', $errStr);
}
echo json_encode(array(
    'result' => $result,
    'resultText' => $message,
    'errorFields' => $errorFields));
?>