<?php
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db();
$auth = new Auth();
$types = [];
$org = null;

if($auth->isLogin()) {

    $checkType = intval($_POST['check_type']);
    $type = $db->db::getAll('SELECT * FROM '.TBL_PREFIX.'orgtypes WHERE checks @> \'["'.$checkType.'"]\'::jsonb');

    foreach($type as $i => $t){
        $types[] = $t['id'];
    }
    if(count($types) > 0) {
        echo '<option value=""></option>';
        $org = $db->select('institutions', '');//' WHERE orgtype IN ('.implode(', ', $types).') ORDER BY short'
        foreach($org as $o){
            echo '<option value="'.$o->id.'"'.($o->id == $_POST['selected'] ? ' selected' : '').'>'.
                stripslashes(htmlspecialchars($o->short)).'</option>';
        }
    }
}else{

}


