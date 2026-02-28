<?php

use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db = new Db();
$auth = new Auth();
$tempId = intval($_POST['tempId']);
$selected = intval($_POST['selected']);

if ($auth->isLogin()) {

    $temp = $db->selectOne('documents', " WHERE id = ?", [$tempId]);
    $checks = $db->select('checks',  ' ORDER BY name');

    if (count($checks) > 0) {
        echo '<option value="0">&nbsp;</option>';
        foreach ($checks as $u) {
            echo '<option value="' . $u->id . '"' . ($u->id == $selected || $u->id == $temp->checks ? ' selected' : '') . '>' .
                stripslashes(htmlspecialchars($u->name)) . '</option>';
        }
    }

}