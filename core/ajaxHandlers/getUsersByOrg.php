<?php
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db();
$auth = new Auth();
$orgId = intval($_POST['orgId']);
$ministriesId = intval($_POST['ministriesId']);
$division = intval($_POST['unitsId']);
$selected = intval($_POST['selected']);

if($auth->isLogin()) {

    $units = $db->db::getAll('SELECT * FROM '.TBL_PREFIX.'users WHERE institution = '.$orgId .
        ' AND ministries = '.$ministriesId. ' AND division = '.$division);

    if(count($units) > 0) {
        echo '<option value="">&nbsp;</option>';
        foreach($units as $u){
            $user_fio = stripslashes(htmlspecialchars(trim($u['surname']))).' '.
                stripslashes(htmlspecialchars(trim($u['name']))).' '.
                stripslashes(htmlspecialchars(trim($u['middle_name']))).' <div>'.
                stripslashes(htmlspecialchars(trim($u['position']))).'</div>';
            echo '<option value="'.$u['id'].'"'.($u['id'] == $selected ? ' selected' : '').'>'.$user_fio.'</option>';
        }
    }
}