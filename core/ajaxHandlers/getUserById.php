<?php
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db();
$auth = new Auth();
$userId = intval($_POST['user_id']);
$mode = $_POST['mode'];

if($auth->isLogin()) {

    $user = $db->selectOne('users', 'WHERE id = ?', [$userId]);

    if($mode == 'short'){
        echo trim($user->surname).' '.mb_substr(trim($user->name), 0, 1).'. '.mb_substr(trim($user->middle_name), 0, 1).'.';
    }else {
        echo trim($user->surname).' '.trim($user->name).' '.trim($user->middle_name);
    }
}