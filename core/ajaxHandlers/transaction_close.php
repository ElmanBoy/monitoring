<?php
use Core\Db;
session_start();
if(isset($_SESSION['login'])){
    require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
    $db = new Db;
    $db->transactionClose(intval($_POST['id']));
}
?>
