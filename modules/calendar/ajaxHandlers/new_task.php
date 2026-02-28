<?php
use Core\Db;
use Core\Auth;
use Core\Notifications;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
//print_r($_POST);
$db = new Db();
$auth = new Auth();
$alert = new Notifications();
$err = 0;
$errStr = [];


if($auth->isLogin()) {

    if ($auth->checkAjax()) {

        $users = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name', 'position']);

        if(intval($_POST['ins']) == 0){
            $err++;
            $errStr[] = 'Укажите объект проверки';
        }
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

                $userFio = trim($users['array'][$_POST['users'][$i]][0]).' '.
                    trim($users['array'][$_POST['users'][$i]][1]).' '.
                    trim($users['array'][$_POST['users'][$i]][2]);

                $ans = [
                    'created_at' => date('Y-m-d H:i:s'),
                    'author' => intval($_SESSION['user_id']),
                    'active' => 1,
                    'check_uid' => '0',
                    'user' => intval($_POST['users'][$i]),
                    'dates' => $_POST['dates'][$i],
                    'task_id' => $_POST['tasks'][$i],
                    'allowremind' => intval($_POST['allowremind'][$i]),
                    'institution' => intval($_POST['ins']),
                    'object_type' => intval($_POST['object'])
                ];
                $db->insert('checkstaff', $ans);

                if($alert->notificationTask(
                    $_SESSION['user_id'],
                    $_POST['users'][$i],
                    $db->last_insert_id,
                    'update',
                    intval($_POST['allowremind'][$i]) == 1,
                    $_POST['datetime'][$i],
                    htmlspecialchars($_POST['comment'][$i]))){
                    $alertMessage = 'Уведомление отправлено исполнителю '.$userFio.'.';
                }else{
                    $alertMessage = '<script>alert("Задание изменено, но уведомление не было отправлено.<br>" +
                            " У исполнителя '.$userFio.' не указан или неверный Email.")</script>';
                }
            }

            echo json_encode(array(
                'result' => true,
                'resultText' => 'Задача создана.
                                <script>
                                el_app.reloadMainContent();
                                el_app.dialog_close("registry_create");
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