<?php
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db();
$auth = new Auth();
$org = null;

if($auth->isLogin()) {

    echo '<option value=""></option>';
    $org = $db->select('institutions', '');
    foreach($org as $o){
        echo '<option value="'.$o->id.'"'.($o->id == $_POST['selected'] ? ' selected' : '').'>'.
            stripslashes(htmlspecialchars($o->short)).'</option>';
    }
}


