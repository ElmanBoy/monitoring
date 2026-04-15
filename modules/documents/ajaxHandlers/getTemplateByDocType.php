<?php

use Core\Db;
use Core\Auth;
$optArr = [];
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$db = new Db;
$auth = new Auth();
$props = $db->select('documents', ' WHERE documentacial = ?' . $auth->getDocumentMinistryFilter() . ' ORDER BY name', [intval($_POST['docType'])]);
if($props){
    foreach ($props as $p){
        $optArr[] = '<option value="'.$p->id.'">'.$p->name.'</option>';
    }
}
echo implode("\n", $optArr);
