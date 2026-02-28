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
        echo json_encode($notes->getRecordsToPanel($_SESSION['user_id']));
}
?>
