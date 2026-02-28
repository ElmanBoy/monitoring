<?php
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db();
$auth = new Auth();
$regId = intval($_POST['regId']);
$selected = intval($_POST['selected']);
$regName = null;

if($auth->isLogin()) {
    
    if($regId > 0){
        $regName = $db->selectOne("registry", "WHERE id = ?", [$regId]);
    }

    echo '<option value=""></option>';
    $org = $db->select($regName->table_name, '');
    foreach($org as $o){
        echo '<option value="'.$o->id.'"'.($o->id == $selected ? ' selected' : '').'>'.
            stripslashes(htmlspecialchars($o->name)).'</option>';
    }
}


