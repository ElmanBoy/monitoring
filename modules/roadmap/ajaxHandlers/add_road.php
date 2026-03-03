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
$registry = [
    'created_at' => date('Y-m-d H:i:s'),
    'author' => $_SESSION['user_id'],
    'header' => $_POST['header'],
   'name' => 'График устранений нарушений',
    'documentacial' => 5,
    'document' => 18
];
$schedule = [];

$regProps = $db->db::getAll('SELECT
            ' . TBL_PREFIX . 'regfields.prop_id AS fId,  
            ' . TBL_PREFIX . 'regprops.*
            FROM ' . TBL_PREFIX . 'regfields, ' . TBL_PREFIX . 'regprops
            WHERE ' . TBL_PREFIX . 'regfields.prop_id = ' . TBL_PREFIX . 'regprops.id AND 
            ' . TBL_PREFIX . 'regfields.reg_id = ? ORDER BY ' . TBL_PREFIX . 'regfields.sort', [$regId]
);
//Проверяем обязательные поля
if(strlen(trim($_POST['header'])) == 0){
    $err++;
    $errStr[] = 'Заполните верх документа';
    $errorFields[] = 'header';
}
if(!is_array($_POST['schedule_number']) || count($_POST['schedule_number']) == 0){
    $err++;
    $errStr[] = 'Добавьте минимум одну строку в график';
    $errorFields[] = 'header';
}else{
   for($i = 0; $i < count($_POST['schedule_number']); $i++){
       if(strlen(trim($_POST['schedule_offers'][$i])) == 0){
           $err++;
           $errStr[] = 'Заполните Предложения по устранению выявленных нарушений и недостатков в строке № '.($i + 1);
           $errorFields[] = 'schedule_offers['.$i.']';
       }
       if(strlen(trim($_POST['schedule_actions'][$i])) == 0){
           $err++;
           $errStr[] = 'Заполните Действия, необходимые для принятия мер по устранению нарушений в строке № '.($i + 1);
           $errorFields[] = 'schedule_actions['.$i.']';
       }
       if(strlen(trim($_POST['schedule_deadlines'][$i])) == 0){
           $err++;
           $errStr[] = 'Заполните Сроки выполнения предложений (устранения нарушений) в строке № '.($i + 1);
           $errorFields[] = 'schedule_deadlines['.$i.']';
       }
       if(strlen(trim($_POST['schedule_responsible'][$i])) == 0){
           $err++;
           $errStr[] = 'Заполните Ответственный за выполнение в строке № '.($i + 1);
           $errorFields[] = 'schedule_responsible['.$i.']';
       }
       if($err == 0){
           $schedule[] = [
               'schedule_number' => intval($_POST['schedule_offers'][$i]),
               'schedule_offers' => htmlspecialchars($_POST['schedule_offers'][$i]),
               'schedule_actions' => htmlspecialchars($_POST['schedule_actions'][$i]),
               'schedule_deadlines' => htmlspecialchars($_POST['schedule_deadlines'][$i]),
               'schedule_responsible' => htmlspecialchars($_POST['schedule_responsible'][$i])
           ];
       }
   }
}


if($err == 0) {

    $registry['agreementlist'] = json_encode($schedule);

/*    foreach ($regProps as $f) {
        $value = $reg->prepareValues($f, $_POST);
        $registry[$f['field_name']] = $value;
    }*/
    try {
        $db->insert('agreement', $registry);
        $result = true;
        $message = 'График устранения нарушения успешно создан.
        <script>
        el_app.reloadMainContent();
        el_app.dialog_close("add_road"); 
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