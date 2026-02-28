<?php
use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$db = new Db;
$id = intval($_POST['id']);
$name = $db->getRegistry('plannames', ' where id = ?', [$id], ['longname']);
echo $name['array'][$id][0];