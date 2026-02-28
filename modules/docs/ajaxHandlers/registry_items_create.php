<?php
use Core\Db;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();
$regId = intval($_POST['parent']);
$db = new Db();
$reg = new Registry();

$docType = intval($_POST['documentacial']);

if($docType == 0){
    $err++;
    $errStr[] = 'Укажите тип документа';
    $errorFields[] = 'documentacial';
}elseif($docType == 6){
    if(count($_POST['agreementlist']) == 0){
        $err++;
        $errStr[] = 'Укажите согласующих и подписантов';
        $errorFields[] = 'users';
    }else{
        for($a = 0; $a < count($_POST['agreementlist']); $a++){
            if(strlen(trim($_POST['agreementlist'][$a])) == 0){
                $err++;
                $errorStage = $a == count($_POST['agreementlist']) - 1 ? ' подписания' : ' №'.($a + 1);
                $errStr[] = 'Укажите сотрудников для этапа'.$errorStage;
                $errorFields[] = 'users['.[$a].']';
            }
        }
    }
}else{
    if(strlen(trim($_POST['header'])) == 0){
        $err++;
        $errStr[] = 'Укажите надпись сверху';
        $errorFields[] = 'header';
    }
    if(strlen(trim($_POST['body'])) == 0){
        $err++;
        $errStr[] = 'Укажите текст в середине документа';
        $errorFields[] = 'body';
    }
    if(strlen(trim($_POST['bottom'])) == 0){
        $err++;
        $errStr[] = 'Укажите надпись внизу';
        $errorFields[] = 'bottom';
    }
}

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
    $html_template = [];
    foreach ($regProps as $f) {
        $value = $reg->prepareValues($f, $_POST);
        $registry[$f['field_name']] = $value;
        if($f['type'] == 'html'){
            $html_template[$f['field_name']] .= $value;
        }
    }
    try {
        $db->insert($table->table_name, $registry);
        if(count($html_template) > 0){
            foreach($html_template as $ket => $val) {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/cache/docs_templates/' . $db->last_insert_id . '_'.$ket.'.html', $val);
            }
        }
        $result = true;
        $message = 'Элемент справочника успешно создан.
        <script>
        el_app.reloadMainContent();
        el_app.dialog_close("registry_items_create");
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