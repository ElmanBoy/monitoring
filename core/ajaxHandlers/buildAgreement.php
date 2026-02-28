<?php

use Core\Registry;
use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$reg = new Registry();
$db = new Db();

$f['field_name'] = 'agreementlist';
$editData[$f['field_name']] = $_POST['agreementlist'];
$editData['oneSignOnly'] = intval($_POST['oneSignOnly']);
echo $reg->renderAddAgreement($f, $editData, '');

