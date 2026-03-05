<?php

use Core\Date;
use Core\Db;
use Core\Auth;
use Core\Notifications;
use Core\Cache;
use Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

// Временное логирование для отладки напоминаний
function remind_log(string $msg): void {
    $logDir = $_SERVER['DOCUMENT_ROOT'] . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }
    file_put_contents($logDir . '/reminders.log', '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND);
}
//print_r($_POST);
$db = new Db();
$auth = new Auth();
$alert = new Notifications();
$cache = new Cache();
$reg = new Registry();
$date = new Date();

$plan_uid = !$_POST['uid'] ? '0' : $_POST['uid'];
$taskId = intval($_POST['task_id']);
$insId = intval($_POST['ins']);
$orderId = intval($_POST['order']);

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
$userTaskId = 0;


$users = $db->getRegistry("users", '', [], ['surname', 'name', 'middle_name', 'position']);
//$db->db::exec("DELETE FROM ".TBL_PREFIX."checkstaff WHERE check_uid = '$plan_uid' AND institution = ".$insId);
$exist_task = $db->select('checkstaff', " WHERE check_uid = '$plan_uid' AND institution = ".$insId);
$inspections = $db->getRegistry('inspections');

// Проверяем что приказ утверждён
$agreement_data = $db->selectOne('agreement', " WHERE source_table = 'checkinstitutions' AND source_id = " . $insId);
$order_approved = (intval($agreement_data->status) == 1 || intval($agreement_data->approved) == 1);

if($auth->isLogin()) {

    if ($auth->checkAjax()) {
        // Блокируем назначение если приказ не утверждён
        if (!$order_approved) {
            echo json_encode([
                'result' => false,
                'resultText' => 'Назначение проверяющих невозможно: приказ о проведении проверки ещё не утверждён.',
                'errorFields' => []
            ]);
            exit;
        }
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
                if(isset($_POST['is_head'][$i]) && intval($_POST['is_head'][$i]) == 1){
                    $is_head_count++;
                }else{
                    $_POST['is_head'][$i] = 0;
                }
                //echo 'is_head: '.intval($_POST['is_head'][$i]).' $i: '.$i."\n";
            }
            reset($_POST['executors']);
            /*reset($_POST['dates']);
            reset($_POST['tasks']);
            reset($_POST['is_head']);*/
            //print_r($_POST['is_head']);

            /*if($is_head_count == 0){
                $err++;
                $errStr[] = 'Укажите руководителя проверки';
                $errorFields[] = '^is_head[';
            }
            if($is_head_count > 1){
                $err++;
                $errStr[] = 'Укажите одного руководителя проверки';
                $errorFields[] = '^is_head[';
            }

            if (strlen($_POST['order_number']) == 0) {
                $err++;
                $errStr[] = 'Укажите номер приказа';
                $errorFields[] = 'order_number';
            }
            if (strlen($_POST['order_date']) == 0) {
                $err++;
                $errStr[] = 'Укажите дату приказа';
                $errorFields[] = 'order_date';
            }
            if (intval($_POST['order']) == 0) {
                $err++;
                $errStr[] = 'Укажите шаблон приказа';
                $errorFields[] = 'order';
            }
            if (!isset($_POST['signers']) || count($_POST['signers']) == 0) {
                $err++;
                $errStr[] = 'Укажите подписантов приказа';
                $errorFields[] = 'signers[]';
            }*/
        }
        if($err == 0) {
            reset($_POST['executors']);
            reset($_POST['dates']);

            if (isset($_FILES['files']) && count($_FILES['files']) > 0 && is_array($_POST['custom_names'])) {
                $files = new \Core\Files();
                $fileIds = $files->attachFiles($_FILES['files'], $_POST['custom_names']);
                if ($fileIds['result']) {
                    $_POST['file_ids'] = json_encode($fileIds['ids']);
                    $reg->insertTaskLog($taskId, 'Приложение файлов');
                } else {
                    echo $fileIds['message'];
                }
            }

            // Логируем полный POST перед основным циклом
            remind_log('POST: ' . json_encode([
                'executors'        => $_POST['executors'] ?? 'NOT SET',
                'allowremind_flag' => $_POST['allowremind_flag'] ?? 'NOT SET',
                'remind_id'        => $_POST['remind_id'] ?? 'NOT SET',
                'datetime'         => $_POST['datetime'] ?? 'NOT SET',
                'comment'          => $_POST['comment'] ?? 'NOT SET',
                'user_task'        => $_POST['user_task'] ?? 'NOT SET',
            ], JSON_UNESCAPED_UNICODE));

            for ($i = 0; $i < count($_POST['executors']); $i++) {

                $userFio = trim($users['array'][$_POST['executors'][$i]][0]).' '.
                    trim($users['array'][$_POST['executors'][$i]][1]).' '.
                    trim($users['array'][$_POST['executors'][$i]][2]);
                if(isset($_POST['is_head'][$i]) && intval($_POST['is_head'][$i]) == 1){
                    $executor_head = $userFio.', '.$users['array'][$_POST['executors'][$i]][3];
                }else {
                    $executors[] = $userFio . ', ' . $users['array'][$_POST['executors'][$i]][3];
                }

                // allowremind_actual[] — скрытое поле, JS синхронизирует его с чекбоксом.
                // Один элемент на сотрудника, читается напрямую по $i.
                $flagValues  = isset($_POST['allowremind_actual']) ? array_values($_POST['allowremind_actual']) : [];
                $allowRemind = isset($flagValues[$i]) && intval($flagValues[$i]) === 1;
                $remindId      = intval($_POST['remind_id'][$i] ?? 0);
                $remindDateRaw = trim($_POST['datetime'][$i] ?? '');
                // datetime-local передаёт "yyyy-MM-ddTHH:mm" — нормализуем в "yyyy-MM-dd HH:mm"
                $remindDateTime = str_replace('T', ' ', $remindDateRaw);
                $remindComment  = htmlspecialchars($_POST['comment'][$i] ?? '');

                $ans = [
                    'created_at' => date('Y-m-d H:i:s'),
                    'author' => intval($_SESSION['user_id']),
                    'active' => 1,
                    'check_uid' => $_POST['uid'],//Это приходит из формы
                    'order_id' => $orderId,//Это приходит из формы
                    'user' => intval($_POST['executors'][$i]),//Это приходит из формы
                    'dates' => $_POST['dates'][$i],//Это приходит из формы
                    'task_id' => intval($_POST['tasks'][$i]),//Это приходит из формы
                    'institution' => $insId,//Это приходит из формы
                    'is_head' => intval($_POST['is_head'][$i]),//Это приходит из формы
                    //'ministry' => intval($_POST['ministries'][$i]),
                    'unit' => intval($_POST['unit']), //Это приходит из формы
                    'ousr' => $_POST['ousr'][$i], //(?)
                    'allowremind' => $allowRemind ? 1 : 0,
                ];


                $dateResults[] = $date->getMinMaxDates($_POST['dates'][$i]);

                try {
                    // Определяем id существующей записи — из hidden поля user_task[] формы
                    $userTaskId = intval($_POST['user_task'][$i] ?? 0);

                    // Дополнительная проверка: если user_task не передан — ищем по user в exist_task
                    if ($userTaskId == 0 && count($exist_task) > 0) {
                        foreach ($exist_task as $t) {
                            if (intval($t->user) == intval($_POST['executors'][$i])) {
                                $userTaskId = $t->id;
                                break;
                            }
                        }
                    }

                    if ($userTaskId > 0) { //Редактирование существующего
                        $oldRecord = $db->selectOne('checkstaff', ' WHERE id = ?', [$userTaskId]);
                        $db->update('checkstaff', $userTaskId, $ans);

                        if ($remindId > 0 && !$allowRemind) {
                            $alert->removeRemind($remindId);
                        }

                        // Напоминание сохраняем всегда при $allowRemind, независимо от изменений задания
                        remind_log('CHECK_STAFF DEBUG: allowRemind=' . ($allowRemind ? '1' : '0') . ' userTaskId=' . $userTaskId . ' flagValues=' . json_encode($flagValues) . ' i=' . $i);
                        if ($allowRemind) {
                            $finalRemindDateTime = strlen(trim($remindDateTime)) > 0
                                ? $remindDateTime
                                : date('Y-m-d H:i:s', strtotime('+1 day'));
                            $executor = $db->selectOne('users', ' WHERE id = ?', [intval($_POST['executors'][$i])]);
                            remind_log('CHECK_STAFF DEBUG: calling setRemind, taskId=' . $userTaskId . ' executorId=' . intval($_POST['executors'][$i]) . ' datetime=' . $finalRemindDateTime);
                            $alert->setRemind(
                                intval($_SESSION['user_id']),
                                $userTaskId,
                                intval($_POST['executors'][$i]),
                                $finalRemindDateTime,
                                'Кликните по уведомлению для просмотра задачи',
                                'https://monitoring.msr.mosreg.ru/assigned?open_dialog=' . $userTaskId,
                                'Напоминание о задаче № ' . $userTaskId,
                                $remindComment,
                                $executor->email ?? '',
                                trim($executor->surname . ' ' . $executor->name . ' ' . $executor->middle_name),
                                ''
                            );
                        }

                        // Уведомление по email — только если изменились значимые поля задания
                        $hasChanges = (
                            trim($oldRecord->dates ?? '') !== trim($ans['dates']) ||
                            intval($oldRecord->task_id ?? 0) !== intval($ans['task_id'])
                        );
                        if ($hasChanges) {
                            if ($alert->notificationTask($_SESSION['user_id'], intval($_POST['executors'][$i]), $userTaskId, 'update', false, '', '')) {
                                $alertMessage = 'Уведомление отправлено исполнителю ' . $userFio . '.';
                            } else {
                                $alertMessage = '<script>alert("Задание изменено, но уведомление не было отправлено.<br>" +
                                " У исполнителя ' . $userFio . ' не указан или неверный Email.")</script>';
                            }
                        }
                    } else { //Создание нового
                        $db->insert('checkstaff', $ans);
                        $newTaskId = $db->last_insert_id;
                        $userTaskId = $newTaskId;

                        // Напоминание сохраняем сразу, независимо от наличия email
                        if ($allowRemind) {
                            $finalRemindDateTime = strlen(trim($remindDateTime)) > 0
                                ? $remindDateTime
                                : date('Y-m-d H:i:s', strtotime('+1 day'));
                            $executor = $db->selectOne('users', ' WHERE id = ?', [intval($_POST['executors'][$i])]);
                            $alert->setRemind(
                                intval($_SESSION['user_id']),
                                $newTaskId,
                                intval($_POST['executors'][$i]),
                                $finalRemindDateTime,
                                'Кликните по уведомлению для просмотра задачи',
                                'https://monitoring.msr.mosreg.ru/assigned?open_dialog=' . $newTaskId,
                                'Напоминание о задаче № ' . $newTaskId,
                                $remindComment,
                                $executor->email ?? '',
                                trim($executor->surname . ' ' . $executor->name . ' ' . $executor->middle_name),
                                ''
                            );
                        }

                        if ($alert->notificationTask($_SESSION['user_id'], intval($_POST['executors'][$i]), $newTaskId, 'new', false, '', '')) {
                            $alertMessage = 'Уведомление отправлено исполнителю ' . $userFio . '.';
                        } else {
                            $alertMessage = '<script>alert("Задание создано, но уведомление не было отправлено.<br>" +
                        " У исполнителя ' . $userFio . ' не указан или неверный Email.")</script>';
                        }
                    }
                    //Создаем список задач в кэше для мобильного приложения
                    $reg->buildTasksListsToCache($_POST['executors'][$i]);

                    $assignmentHtml = $reg->buildAssignment($userTaskId, 0)['html'];
                    $cache->saveToCache($assignmentHtml, 'tasks', $_POST['executors'][$i], $userTaskId);

                } catch (\RedBeanPHP\RedException | Exception $e) {
                    $err++;
                    $errStr[] = 'Ошибка: ' . $e->getMessage();
                }
            }
/*
            //Создаём/редактируем документ приказа
            //Получаем id приказа
            $order = $db->selectOne('agreement', " WHERE source_id = ? AND source_table = ?", [$insId, 'checkinstitutions']);
            $plan = $db->selectOne('checksplans', " WHERE uid = ?  AND active = 1 ORDER BY version DESC", [$plan_uid]);
            $addinstitution = json_decode($plan->addinstitution, true);
            $inspectionsList = [];

            $allMinDates = array_column($dateResults, 'min');
            $allMaxDates = array_column($dateResults, 'max');
            $globalMin = $date->dateToString(min($allMinDates));
            $globalMax = $date->dateToString(max($allMaxDates));

            if(is_array($addinstitution) && count($addinstitution) > 0) {
                foreach ($addinstitution as $ch) {
                    if ($ch['institutions'] == $insId) {
                        if (substr_count($ch['inspections'], '[') > 0) {
                            $inspectionArr = json_decode($ch['inspections']);
                            foreach ($inspectionArr as $in) {
                                $inspectionsList[] = $inspections['array'][$in];
                            }
                        } else {
                            $inspectionsList[] = $inspections['array'][$ch['inspections']];
                        }
                        $check_periodArr = explode(' - ', $ch['check_periods']);
                        $check_period_start = $date->dateToString($check_periodArr[0]);
                        $check_period_end = $date->dateToString($check_periodArr[1]);
                    }
                }
            }
            //'periods': 'I квартал', 'check_types': '3', 'inspections': '1', 'check_periods': '2025-01-01 - 2025-12-31', 'periods_hidden': "[\"01\",\"02\",\"03\"]"
            $orderId = intval($order->id);
            $agreement_header = '';
            $header_vars = [
                'order_date' => $date->correctDateFormatFromMysql($_POST['order_date']),
                'order_number' => $_POST['order_number']
            ];
//print_r($header_vars);
            $agreement_header .= $temp->twig_parse($tmpl->header, $header_vars);

            $actionPeriods = $date->getReviewPeriodsFromJson($plan->addinstitution, $plan->year);
            $actionPeriod = $actionPeriods[$insId]['actionPeriod'];
            $action_period_start = $date->dateToString($actionPeriods[$insId]['action_start_date']);
            $action_period_end = $date->dateToString($actionPeriods[$insId]['action_end_date']);
            $check_period_start = $date->dateToString($actionPeriods[$insId]['check_start_date']);
            $check_period_end = $date->dateToString($actionPeriods[$insId]['check_end_date']);

            $body_vars['institution'] = $insName;
            $body_vars['institution_inn'] = $ins->inn;
            $body_vars['institution_ogrn'] = $ins->ogrn;
            $body_vars['institution_legal'] = $ins->legal;
            $body_vars['institution_agreement_number'] = $ins->agreements_number;
            $body_vars['institution_agreement_date'] = @explode(" ", $ins->agreements)[0];
            $body_vars['plan_year'] = $plan->year;
            $body_vars['order_date'] = $date->correctDateFormatFromMysql($_POST['order_date']);
            $body_vars['inspection'] = implode(';<br>', $inspectionsList);
            $body_vars['periods'] = $globalMin . ' - ' . $globalMax;
            $body_vars['check_period_start'] = $check_period_start;
            $body_vars['check_period_end'] = $check_period_end;
            $body_vars['action_period_start'] = $action_period_start;
            $body_vars['action_period_end'] = $action_period_end;
            $body_vars['executor_head'] = $executor_head;//$temp->phraseToGenitive($executor_head, 'nominative');
            $body_vars['executors'] = implode(";<br>", $executors);
            //print_r($body_vars);
            $agreement_body = $temp->twig_parse($tmpl->body, $body_vars);

            $bottom_vars['today_date'] = $date->dateToString(date('Y-m-d'));
            $bottom_vars['institution'] = $insName;
            $bottom_vars['plan_year'] = $plan->year;
            $bottom_vars['order_date'] = $date->correctDateFormatFromMysql($_POST['order_date']);
            $bottom_vars['check_period_start'] = $check_period_start;
            $bottom_vars['check_period_end'] = $check_period_end;
            $bottom_vars['action_period_start'] = $action_period_start;
            $bottom_vars['action_period_end'] = $action_period_end;
            $bottom_vars['institution_agreement_number'] = $ins->agreements_number;
            $bottom_vars['institution_agreement_date'] = $ins->agreements;
            $bottom_vars['order_number'] = $_POST['order_number'];
            //print_r($bottom_vars);
            $agreement_bottom = $temp->twig_parse($tmpl->bottom, $bottom_vars);

            //Добавляем подписантов в отдельную последнюю секцию-этап
            if(is_array($_POST['signers']) && count($_POST['signers']) > 0){

                $signers = [];
                for($s = 0; $s < count($_POST['signers']); $s++){
                    $signers[] = '{"id":'.$_POST['signers'][$s].',"type":1, "urgent": 1}';
                }
                $last_signers = end($_POST['agreementlist']);
                $last_signersArr = json_decode($last_signers, true);
                if($last_signersArr[0]['stage'] == ''){
                    //Если последний элемент - это секция с подписантами, то меняем её содержимое
                    $last_signers = '[{"stage":"","list_type":"1","urgent":"1"},'.implode(',', $signers).']';
                }else {
                    //Иначае добавляем секцию с подписантами
                    $_POST['agreementlist'][] = '[{"stage":"","list_type":"1","urgent":"1"},' . implode(',', $signers) . ']';
                }
            }

            $_POST['active'] = 1;
            $_POST['name'] = $tmpl->name.' № '.$_POST['order_number'];
            $_POST['header'] = $agreement_header;
            $_POST['body'] = $agreement_body;
            $_POST['bottom'] = $agreement_bottom;
            $_POST['documentacial'] = 1; //Приказ
            $_POST['document'] = $_POST['order'];
            $_POST['doc_number'] = $_POST['order_number'];
            $_POST['docdate'] = $_POST['order_date'];
            $_POST['signators'] = $_POST['signers'];
            $_POST['status'] = 0;
            $_POST['source_id'] = $insId;
            $_POST['source_table'] = 'checkinstitutions';

            $docCreateResult = $reg->createDocument($_POST, $orderId);

            if($docCreateResult['result']){
                $alertMessage = ' Приказ о проверке создан.';
            }else{
                $err++;
                $errStr[] = $docCreateResult['resultText'];
            }*/

        }

        if($err == 0) {
            $reg->insertTaskLog($orderId, 'Сохранены изменения в назначении', 'calendar', 'assign_staff');
            echo json_encode(array(
                'result' => true,
                'resultText' => 'Сотрудники назначены.'.$alertMessage.'
                                <script>
                                el_app.reloadMainContent();
                                el_app.dialog_close("assign_staff");
                                el_app.dialog_close("check_staff");
                                el_app.dialog_close("registry_create");
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