<?php

use Core\Db;
$optArr = [];
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$db = new Db;
$props = $db->select('documents', ' WHERE documentacial = ? ORDER BY name', [intval($_POST['docType'])]);
if($props){
    foreach ($props as $p){
        $optArr[] = '<option value="'.$p->id.'">'.$p->name.'</option>';
    }
}
echo implode("\n", $optArr);
