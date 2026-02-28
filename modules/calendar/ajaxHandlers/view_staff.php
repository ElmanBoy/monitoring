<?php

use Core\Date;
use Core\Db;
use Core\Auth;
use Core\Notifications;
use Core\Cache;
use Core\Registry;
use Core\Templates;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
//print_r($_POST);
$db = new Db();
$temp = new Templates();
$auth = new Auth();
$alert = new Notifications();
$cache = new Cache();
$reg = new Registry();
$date = new Date();

$plan_uid = $_POST['uid'];
$taskId = intval($_POST['task_id']);
$insId = intval($_POST['ins']);

$tmpl = $db->selectOne('documents', ' where id = ?', [intval($_POST['order'])]);
$ins = $db->selectOne('institutions', ' WHERE id = ?', [$insId]);
$insName = $ins->name;//$temp->phraseToGenitive($ins->name, 'nominative');
$executors = [];
$executor_head = '';
$err = 0;
$errStr = [];
$errorFields = [];
$dateResults = [];
$alertMessage = '';


$users = $db->getRegistry("users", '', [], ['surname', 'name', 'middle_name', 'position']);
$exist_task = $db->select('checkstaff', " WHERE check_uid = '$plan_uid' AND institution = ".$insId);
$inspections = $db->getRegistry('inspections');

if($auth->isLogin()) {

    if ($auth->checkAjax()) {
        if(!isset($_POST['executors'])){
            $err++;
            $errStr[] = 'Укажите сотрудника или структурное подразделение';
        }else {
            $is_head_count = 0;
            for ($i = 0; $i < count($_POST['executors']); $i++) {
                if (intval($_POST['executors'][$i]) == 0 && intval($_POST['ousr'][$i]) == 0) {
                    $err++;
                    $errStr[] = 'Укажите сотрудника или структурное подразделение';
                    $errorFields[] = 'executors['.$i.']';
                }
                if (intval($_POST['dates'][$i]) == 0) {
                    $err++;
                    $errStr[] = 'Укажите даты проверки для сотрудника №'.($i + 1);
                    $errorFields[] = 'dates['.$i.']';
                }
                if (intval($_POST['tasks'][$i]) == 0) {
                    $err++;
                    $errStr[] = 'Укажите шаблон задачи для сотрудника №'.($i + 1);
                    $errorFields[] = 'tasks['.$i.']';
                }

            }
            reset($_POST['executors']);

        }
        if($err == 0) {
            reset($_POST['executors']);
            reset($_POST['dates']);

            for ($i = 0; $i < count($_POST['executors']); $i++) {

                $userFio = trim($users['array'][$_POST['executors'][$i]][0]).' '.
                    trim($users['array'][$_POST['executors'][$i]][1]).' '.
                    trim($users['array'][$_POST['executors'][$i]][2]);
                if(isset($_POST['is_head'][$i]) && intval($_POST['is_head'][$i]) == 1){
                    $executor_head = $userFio.', '.$users['array'][$_POST['executors'][$i]][3];
                }else {
                    $executors[] = $userFio . ', ' . $users['array'][$_POST['executors'][$i]][3];
                }

                $ans = [
                    'created_at' => date('Y-m-d H:i:s'),
                    'author' => intval($_SESSION['user_id']),
                    'active' => 1,
                    'check_uid' => $_POST['uid'],
                    'user' => intval($_POST['executors'][$i]),
                    'dates' => $_POST['dates'][$i],
                    'task_id' => $_POST['tasks'][$i],
                    'institution' => intval($_POST['ins']),
                    'is_head' => intval($_POST['is_head'][$i]),
                    'allowremind' => intval($_POST['allowremind']),
                    'ministry' => intval($_POST['ministries'][$i]),
                    'unit' => $_POST['units'][$i],
                    'ousr' => $_POST['ousr'][$i]
                ];


                $dateResults[] = $date->getMinMaxDates($_POST['dates'][$i]);

                try {
                    if(count($exist_task) > 0){ //Редактирование существующего
                        $userTaskId = 0;
                        foreach($exist_task as $t){
                            //if($t->user == $_POST['executors'][$i]){
                                $userTaskId = $t->id;
                            //}
                        }
                        $db->update('checkstaff', $userTaskId, $ans);
                        if($alert->notificationTask(
                            $_SESSION['user_id'],
                            $_POST['executors'][$i],
                            $userTaskId,
                            'update',
                            intval($_POST['allowremind']) == 1,
                            $_POST['datetime'],
                            htmlspecialchars($_POST['comment'])
                        )){
                            $alertMessage = 'Уведомление отправлено исполнителю '.$userFio.'.';
                        }else{
                            $alertMessage = '<script>alert("Задание изменено, но уведомление не было отправлено.<br>" +
                            " У исполнителя '.$userFio.' не указан или неверный Email.")</script>';
                        }

                    }else {//Создание нового

                        $db->insert('checkstaff', $ans);

                        $newTaskId = $db->last_insert_id;
                        if ($alert->notificationTask(
                            $_SESSION['user_id'],
                            $_POST['executors'][$i],
                            $newTaskId,
                            'new',
                            intval($_POST['allowremind']) == 1,
                            $_POST['datetime'],
                            htmlspecialchars($_POST['comment']))) {
                            $alertMessage = 'Уведомление отправлено исполнителю ' . $userFio . '.';
                        } else {
                            $alertMessage = '<script>alert("Задание создано, но уведомление не было отправлено.<br>" +
                        " У исполнителя ' . $userFio . ' не указан или неверный Email.")</script>';
                        }
                    }
                    //Создаем список задач в кэше для мобильного приложения
                    $reg->buildTasksListsToCache($_POST['executors'][$i]);

                    $assignmentHtml = $reg->buildAssignment($newTaskId ?? $userTaskId, 0)['html'];
                    $cache->saveToCache($assignmentHtml, 'tasks', $_POST['executors'][$i], $userTaskId);

                } catch (\RedBeanPHP\RedException | Exception $e) {
                    $err++;
                    $errStr[] = 'Ошибка: ' . $e->getMessage();
                }
            }



        }

        if($err == 0) {
            echo json_encode(array(
                'result' => true,
                'resultText' => 'Сотрудники назначены.'.$alertMessage.'
                                <script>'.
                    (isset($_POST['in_calendar']) && intval($_POST['in_calendar']) == 1 ? '' : 'el_app.reloadMainContent();')
                                .' el_app.dialog_close("view_staff");
                                el_app.updateNotifications();
                                </script>',
                'post' => $_POST,
                'errorFields' => []));
        }else{
            echo json_encode(array(
                'result' => false,
                'resultText' => implode('<br>', $errStr),
                'post' => $_POST,
                'errorFields' => $errorFields));
        }

    }
}else{
    echo json_encode(array(
        'result' => false,
        'resultText' => '<script>alert("Ваша сессия устарела.");document.location.href = "/"</script>',
        'errorFields' => []));
}