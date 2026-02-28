<?php
session_start();

use Core\Auth;
use Core\Files;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$auth = new Auth();
$files = new Files();

if($auth->isLogin()) {
    $result = $files->deleteFile(intval($_POST['file_id']));
    echo json_encode($result);
}