<?php
use Core\Db;
use Core\Auth;
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
//print_r($_POST);
$db = new Db();
$auth = new Auth();
$err = 0;
$errStr = [];


if($auth->isLogin()) {

    if ($auth->checkAjax()) {

        for($i = 0; $i < count($_POST['users']); $i++) {
            $ans = [];
            if (intval($_POST['users'][$i]) == 0) {
                $err++;
                $errStr[] = 'Укажите сотрудника №' . ($i + 1);
            }
            if (intval($_POST['dates'][$i]) == 0) {
                $err++;
                $errStr[] = 'Укажите даты для сотрудника №' . ($i + 1);
            }
        }
        if($err == 0) {
            reset($_POST['users']);
            reset($_POST['dates']);
            for($i = 0; $i < count($_POST['users']); $i++){
                $ans = [];

                $ans = [
                    'created_at' => date('Y-m-d H:i:s'),
                    'author' => intval($_SESSION['user_id']),
                    'active' => 1,
                    'check_uid' => $_POST['plan'],
                    'user' => intval($_POST['users'][$i]),
                    'dates' => $_POST['dates'][$i],
                    'institution' => intval($_POST['ins'])
                ];
                $db->insert('checkstaff', $ans);
            }

            echo json_encode(array(
                'result' => true,
                'resultText' => 'Сотрудники назначены.
                                <script>
                                el_app.reloadMainContent();
                                el_app.dialog_close("assign_staff");
                                </script>',
                'post' => $_POST,
                'errorFields' => []));
        }else{
            echo json_encode(array(
                'result' => false,
                'resultText' => implode('<br>', $errStr),
                'post' => $_POST,
                'errorFields' => []));
        }

    }
}else{
    echo json_encode(array(
        'result' => false,
        'resultText' => '<script>alert("Ваша сессия устарела.");document.location.href = "/main"</script>',
        'errorFields' => []));
}