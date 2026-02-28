<?php

use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db = new Db();
$auth = new Auth();
$checkId = intval($_POST['checkId']);
$selected = intval($_POST['selected']);

if ($auth->isLogin()) {

    $checks = $db->select('inspections',  ' ORDER BY name');

    if (count($checks) > 0) {
        echo '<option value="0">&nbsp;</option>';
        foreach ($checks as $u) {
            echo '<option value="' . $u->id . '"' . ($u->id == $selected || $u->checks == $checkId ? ' selected' : '') . '>' .
                stripslashes(htmlspecialchars($u->name)) . '</option>';
        }
    }


}