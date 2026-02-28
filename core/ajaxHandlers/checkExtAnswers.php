<?php

use Core\Db;
use Core\Auth;
require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db();
$auth = new Auth();

if($auth->isLogin() && ($auth->haveUserRole(3) || $auth->haveUserRole(1))) {

    //if ($auth->checkAjax()) {

        $ea = $db->select('ext_answers');

        echo json_encode(array(
            'result' => count($ea) > 0,
            'resultText' => count($ea),
            'errorFields' => []));
    /*}else{
        echo 'Fuck!';
    }*/
}
?>
