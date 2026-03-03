<?php

use Core\Auth;

@session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$auth = new Auth();
$result = [];
$input = 0;

if (isset($_POST['cert']['inn']) && strlen(trim($_POST['cert']['inn'])) > 0) {
    $result = $auth->loginByInn($_POST);
    $input++;
}

if (isset($_POST['user']) && strlen(trim($_POST['user'])) > 0) {
    $result = $auth->login($_POST['user'], $_POST['password']);
    $input++;
}

if($input == 0){
    echo json_encode([
        'result' => false,
        'message' => 'Авторизуйтесь одним из предложенных способов.'
    ]);
    die();
}

if ($input > 0 && isset($result['result']) && $result['result'] == true) {
    $user_fio = $_SESSION['user_surname'] . ' ' . $_SESSION['user_name'] . ' ' . $_SESSION['user_middle_name'];
    echo json_encode(array(
        'container' => 'message_login',
        'result' => $result['result'],
        'resultText' => $result['message'] . '
            <script>document.location.href = "' . $auth->getDefaultPage() . '"</script>',
        'errorFields' => [])
    );
} else {
    echo json_encode(array(
        'container' => 'message_login',
        'result' => $result['result'],
        'resultText' => $result['message'],
        'errorFields' => [])
    );
}
?>