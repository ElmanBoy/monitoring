<?php
use Core\Db;
require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();
$db = new Db;

$table = $_POST['source'];
$search = addslashes($_POST['parent']);
$selected = (strlen($_POST['selected']) > 0) ? explode(',', $_POST['selected']) : [];

$result = $db->select($table, "? IN (parent) AND active = 1", [$search]);
//print_r($result);
$answerArr = array();
foreach ($result as $res){
    $sel = (in_array($res->id, $selected)) ? ' selected' : '';
    $answerArr[] = '<option value="'.$res->id.'"'.$sel.'>'.$res->name.'</option>';
}
if(count($answerArr) > 0) {
    echo implode("\n", $answerArr);
}
?>