<?php
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db();
$auth = new Auth();
$orgId = intval($_POST['orgId']);
$selected = intval($_POST['selected']);

if($auth->isLogin()) {

    $units = $db->db::getAll('SELECT * FROM '.TBL_PREFIX.'ministries WHERE institution = '.$orgId." ORDER BY id" );

    if(count($units) > 0) {
        echo '<option value="">&nbsp;</option>';
        foreach($units as $u){
            echo '<option value="'.$u['id'].'"'.($u['id'] == $selected ? ' selected' : '').'>'.
                stripslashes(htmlspecialchars($u['name'])).'</option>';
        }
    }


}