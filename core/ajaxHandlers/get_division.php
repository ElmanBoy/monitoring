<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();

$table = TBL_PREFIX.$_POST['source'];
$search = addslashes($_POST['parent']);
$selected = (strlen($_POST['selected']) > 0) ? explode(',', $_POST['selected']) : [];

$result = R::find($table, " ? IN (parent_items) AND active = 1", [$search]);

$answerArr = array();
foreach ($result as $res){
    $sel = (in_array($res->id, $selected)) ? ' selected' : '';
    $answerArr[] = '<option value="'.$res->id.'"'.$sel.'>'.$res->name.'</option>';
}
if(count($answerArr) > 0) {
    echo implode("\n", $answerArr);
}
?>