<?php
use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db;

$table = trim($_POST['tableName']);
$fNames = [];
$field_name = $table == 'checklists' || $table == 'registry' ? 'table_name' : 'field_name';

$exist = $db->select($table);

foreach($exist as $e){
    $fNames[] = $e->$field_name;
}

echo json_encode($fNames);