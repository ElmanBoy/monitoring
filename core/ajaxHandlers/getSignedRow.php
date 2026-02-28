<?php
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db();
$auth = new Auth();

$table = $_POST['table'];
$fieldId = intval($_POST['fieldId']);
$out = [];
$table_name = addslashes($_POST['source']);
$row_id = intval($_POST['row_id']);


if($auth->isLogin()) {
    $hash = '';
    $hashArr = [];
    $doc = $db->selectOne($table_name, ' WHERE id = ?', [$row_id]);
    foreach($doc as $name => $value){
        if($name != 'sign') {
            $hashArr[$name] = $value;
            $hash .= $value;
        }
    }
    echo json_encode(array(
        'result' => true,
        'data' => $hashArr,
        'hash' => md5($hash),
        'errorFields' => []));
}else{
    echo json_encode(array(
        'result' => false,
        'resultText' => '<script>alert("Ваша сессия устарела.");document.location.href = "/"</script>',
        'errorFields' => []));
}
