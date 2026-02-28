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
$messages = '';


$comparisonData = [];
for($i = 0; $i < count($_POST['table_fields']); $i++){
    if(intval($_POST['table_fields'][$i]) > 0) {
        $comparisonData[$i] = intval($_POST['table_fields'][$i]);
    }
}

$result = $reg->importCsv($_POST['fileName'], $_POST['reg_id'], $comparisonData);
/*if(isset($_FILES['file']['type'])){
    $file_type = $_FILES['file']['type'];

    if($file_type == 'text/csv'){
        try {
            $reg->importCsv($_FILES['file']['tmp_name'], $regId);
        } catch (\RedBeanPHP\RedException\SQL $e) {
            $err++;
            $errStr[] = $e->getMessage();
        }
    }else{
        $err++;
        $errStr[] = 'Неподдерживаемый формат файла';
    }
}*/

if($result['result']){
    $messages = '<script>
        el_app.reloadMainContent();
        el_app.dialog_close("registry_items_import");
        </script>';
}

echo json_encode(array(
    'result' => $result['result'],
    'resultText' => implode('<br>', $result['messages']).$messages,
    'errorFields' => []));