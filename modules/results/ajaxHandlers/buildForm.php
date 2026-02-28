<?php

use Core\Db;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$reg = new Registry();
$regId = intval($_POST['regId']);

echo $reg->buildForm($regId, $_POST['props']);