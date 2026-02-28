<?php
use Core\Db;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$err = 0;
$errStr = array();
$result = false;
$errorFields = array();
$db = new Db();
$reg = new Registry();
$messages = '';


$comparisonData = [];
for($i = 0; $i < count($_POST['table_fields']); $i++){
    if(intval($_POST['table_fields'][$i]) > 0) {
        $comparisonData[$i] = intval($_POST['table_fields'][$i]);
    }
}

$result = $reg->importPlan($_POST['fileName'], $_POST['table_begin'], $_POST['plan_name']);


if($result['result']){
    $messages = '<script>
        el_app.reloadMainContent();
        el_app.dialog_close("plan_import");
        </script>';
}

echo json_encode(array(
    'result' => $result,
    'resultText' => implode('<br>', $result['messages']),//.$messages,
    'errorFields' => []));