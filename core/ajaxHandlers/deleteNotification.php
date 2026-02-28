<?php
session_start();
use Core\Db;
use Core\Auth;
use Core\Notifications;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db();
$auth = new Auth();
$notes = new Notifications();

if($auth->isLogin()) {
        $notes->deleteRecordById($_SESSION['user_id'], intval($_POST['id']));
}
?>
