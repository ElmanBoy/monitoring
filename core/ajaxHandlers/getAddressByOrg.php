<?php
use Core\Db;
require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();
$answerArr = array();

$db = new Db();

$search = intval($_POST['insId']);
$inn = $db->selectOne('institutions', " WHERE id = ?", [$search]);
if(strlen(trim($inn->legal)) > 0){
    $answerArr[] = '<option value="0">' . trim($inn->legal) . '</option>';
}
if(strlen(trim($inn->location)) > 0 && $inn->legal != $inn->location){
    $answerArr[] = '<option value="1">' . trim($inn->location) . '</option>';
}
//Если есть адреса с найденным ИНН, например, адреса отделений, то добавляем в список
if(intval($inn->inn) > 0) {
    $result = $db->select('insadress', " WHERE inn = ? AND active = 1", [$inn->inn]);
    foreach ($result as $res) {
        $sel = $res->basic == 1 ? ' selected="selected"' : '';
        $title = strlen($res->name) > 0 ? ' title="'.htmlspecialchars($res->name).'"' : '';
        $answerArr[] = '<option value="' . $res->id . '"'.$sel.$title.'>' . $res->target_address . '</option>';
    }
}
if (count($answerArr) > 0) {
    echo implode("\n", array_unique($answerArr));
}