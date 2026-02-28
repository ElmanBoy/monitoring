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

$plan_uid = !$_POST['uid'] ? '0' : $_POST['uid'];
$plan_id = intval($_POST['plan_id']);
$doc_id = intval($_POST['doc_id']);
$taskId = intval($_POST['task_id']);
$insId = intval($_POST['ins']);
$doc_status = intval($_POST['doc_status']);


$executors = [];
$executor_head = '';
$err = 0;
$errStr = [];
$errorFields = [];
$dateResults = [];
$alertMessage = '';
$userTaskId = 0;




//echo '$exist_task ';print_r($exist_task);echo "<hr>\n";
if ($auth->isLogin()) {

    if ($auth->checkAjax()) {
        $tmpl = $db->selectOne('documents', ' where id = ?', [intval($_POST['document'])]);
        $ins = $db->selectOne('institutions', ' WHERE id = ?', [$insId]);
        $inspections = $db->getRegistry('inspections');
        $source_id = $db->selectOne('checkinstitutions', ' WHERE plan_uid = ? AND institution = ?', [$plan_uid, $insId]);
        $insName = $ins->name;//$temp->phraseToGenitive($ins->name, 'nominative');
        $users = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name', 'position']);
        $exist_task = $db->select('checkstaff', " WHERE check_uid = '$plan_uid' AND institution = " . $insId);
        /*if (strlen($_POST['order_number']) == 0) {
            $err++;
            $errStr[] = 'Укажите номер приказа';
            $errorFields[] = 'order_number';
        }
        if (strlen($_POST['order_date']) == 0) {
            $err++;
            $errStr[] = 'Укажите дату приказа';
            $errorFields[] = 'order_date';
        }*/
        if (strlen(trim($_POST['check_period'])) == 0) {
            $err++;
            $errStr[] = 'Укажите проверяемый период';
            $errorFields[] = 'check_period';
        }
        if (strlen($_POST['action_period']) == 0) {
            $err++;
            $errStr[] = 'Срок проведения проверки';
            $errorFields[] = 'action_period';
        }
        if (intval($_POST['document']) == 0) {
            $err++;
            $errStr[] = 'Укажите шаблон приказа';
            $errorFields[] = 'document';
        }
        if (intval($_POST['executors_head']) == 0) {
            $err++;
            $errStr[] = 'Укажите руководителя проверки';
            $errorFields[] = 'executors_head';
        }
        if (!isset($_POST['executors_list']) || count($_POST['executors_list']) == 0) {
            $err++;
            $errStr[] = 'Укажите проверяющих';
            $errorFields[] = 'executors_list[]';
        }
        if (!isset($_POST['agreementlist']) || count($_POST['agreementlist']) == 0) {
            $err++;
            $errStr[] = 'Заполните лист согласования';
            $errorFields[] = '';
        }else{
            foreach($_POST['agreementlist'] as $sec){
                if(is_null($sec) || strlen(trim($sec)) == 0){
                    $err++;
                    $errStr[] = 'Заполните лист согласования';
                }
            }
        }


        if ($err == 0) {
            reset($_POST['executors_list']);

            if (isset($_FILES['files']) && count($_FILES['files']) > 0 && is_array($_POST['custom_names'])) {
                $files = new \Core\Files();
                $existFilesIds = [];
                if($doc_id > 0) {
                    $fileExist = $db->selectOne('agreement', " WHERE id = ?", [$doc_id]);
                    if(!is_null($fileExist->file_ids)) {
                        $existFilesIds = is_string($fileExist->file_ids)
                            ? json_decode($fileExist->file_ids) : $fileExist->file_ids;
                    }
                }
                $fileIds = $files->attachFiles($_FILES['files'], $_POST['custom_names']);
                if ($fileIds['result']) {
                    if(!is_null($existFilesIds)) {
                        $_POST['file_ids'] = json_encode(array_merge($existFilesIds, $fileIds['ids']));
                    }else{
                        $_POST['file_ids'] = json_encode($fileIds['ids']);
                    }
                    $reg->insertTaskLog($doc_id, 'Приложение файлов &laquo;'.
                        implode('&raquo;, &laquo;', $_POST['custom_names']).'&raquo;', 'calendar', 'order_staff');
                } else {
                    echo $fileIds['message'];
                }
            }

            /*
             * TODO: Добавить статус всем учреждениям из плана статус "Ожидается создания приказа" + может быть посылать уведомление руководителю управления (?)
             * TODO: Неподписанный приказ пишем в agreement и ставим статус у учреждения "Ожидание подписания приказа", а подписанный уже в checkstaff
             * TODO: Направлять уведомление руководителю ПОСЛЕ подписания приказа - СДЕЛАНО
             * TODO: Добавить проверку списка согласовантов, их наличие
            */

            //Если приказ уже подписан
            /*if($doc_status > 0) {

                for ($i = 0; $i < count($_POST['executors']); $i++) {

                    $userFio = trim($users['array'][$_POST['executors'][$i]][0]) . ' ' .
                        trim($users['array'][$_POST['executors'][$i]][1]) . ' ' .
                        trim($users['array'][$_POST['executors'][$i]][2]);
                    if (isset($_POST['is_head'][$i]) && intval($_POST['is_head'][$i]) == 1) {
                        $executor_head = $userFio . ', ' . $users['array'][$_POST['executors'][$i]][3];
                    } else {
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
                        'institution' => $insId,
                        'is_head' => intval($_POST['is_head'][$i]),
                        'ministry' => intval($_POST['ministries'][$i]),
                        'unit' => $_POST['units'][$i],
                        'ousr' => $_POST['ousr'][$i]
                    ];


                    $dateResults[] = $date->getMinMaxDates($_POST['dates'][$i]);

                    try {
                        if (count($exist_task) > 0) { //Редактирование существующего

                            foreach ($exist_task as $t) {
                                if ($t->user == $_POST['executors'][$i]) {
                                    $userTaskId = $t->id;
                                }
                            }
                            $db->update('checkstaff', $userTaskId, $ans);

                            if ($alert->notificationTask($_SESSION['user_id'], $_POST['executors'][$i], $userTaskId, 'update')) {
                                $alertMessage = 'Уведомление отправлено исполнителю ' . $userFio . '.';
                            } else {
                                $alertMessage = '<script>alert("Задание изменено, но уведомление не было отправлено.<br>" +
                            " У исполнителя ' . $userFio . ' не указан или неверный Email.")</script>';
                            }

                        } else {//Создание нового

                            $db->insert('checkstaff', $ans);

                            $newTaskId = $db->last_insert_id;
                            if ($alert->notificationTask($_SESSION['user_id'], $_POST['executors'][$i], $newTaskId, 'new')) {
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
            }*/

            //Создаём/редактируем документ приказа
            $plan = $db->selectOne('checksplans', " WHERE id = ?", [$plan_id]);
            $addinstitution = json_decode($plan->addinstitution, true);
            $inspectionsList = [];

            if(count($dateResults) > 0) {
                $allMinDates = array_column($dateResults, 'min');
                $allMaxDates = array_column($dateResults, 'max');
                $globalMin = $date->dateToString(min($allMinDates));
                $globalMax = $date->dateToString(max($allMaxDates));
            }else{
                $globalMin = $date->dateToString($_POST['minDate']);
                $globalMax = $date->dateToString($_POST['maxDate']);
            }

            if (is_array($addinstitution) && count($addinstitution) > 0) {
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
            $agreement_header = '';
            $order_date = date('Y-m-d');
            $header_vars = [
                'order_date' => $order_date,//$date->correctDateFormatFromMysql($_POST['order_date']),
                'order_number' => $_POST['order_number']
            ];
//print_r($header_vars);
            $agreement_header .= $temp->twig_parse($tmpl->header, $header_vars);

            /*$actionPeriods = $date->getReviewPeriodsFromJson($plan->addinstitution, $plan->year);
            $actionPeriod = $actionPeriods[$insId]['actionPeriod'];
            $action_period_start = $date->dateToString($actionPeriods[$insId]['action_start_date']);
            $action_period_end = $date->dateToString($actionPeriods[$insId]['action_end_date']);
            $check_period_start = $date->dateToString($actionPeriods[$insId]['check_start_date']);
            $check_period_end = $date->dateToString($actionPeriods[$insId]['check_end_date']);*/

            /*$actionPeriodArr = explode(' - ', $date->getMonthDateRange(json_decode($_POST['action_period_hidden'][0])));
            $checkPeriodArr = explode(' - ', $_POST['check_period']);*/
            $actionPeriodArr = [];
            $checkPeriodArr = [];
            if(strlen($_POST['action_period_hidden'][0]) > 0) {
                if(substr_count($_POST['action_period_hidden'][0], '[') > 0) {
                    $actionPeriodArr = explode(' - ', $date->getMonthDateRange(json_decode($_POST['action_period_hidden'][0])));
                }
            }else{
                $actionPeriodArr = explode(' - ', $_POST['actionPeriod']);
            }
            $checkPeriodArr = explode(' - ', $_POST['check_period']);
            $action_period_start = $date->dateToString($actionPeriodArr[0]);
            $action_period_end = $date->dateToString($actionPeriodArr[1]);
            $check_period_start = $date->dateToString($checkPeriodArr[0]);
            $check_period_end = $date->dateToString($checkPeriodArr[1]);

            $body_vars['institution'] = $insName;
            $body_vars['institution_inn'] = $ins->inn;
            $body_vars['institution_ogrn'] = $ins->ogrn;
            $body_vars['institution_legal'] = $ins->legal;
            $body_vars['institution_agreement_number'] = $ins->agreements_number;
            $body_vars['institution_agreement_date'] = @explode(" ", $ins->agreements)[0];
            $body_vars['plan_year'] = $plan->year;
            $body_vars['order_date'] = $order_date;//$date->correctDateFormatFromMysql($_POST['order_date']);
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
            $bottom_vars['order_date'] = $order_date;//$date->correctDateFormatFromMysql($_POST['order_date']);
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
            if (is_array($_POST['signers']) && count($_POST['signers']) > 0) {

                $signers = [];
                for ($s = 0; $s < count($_POST['signers']); $s++) {
                    $signers[] = '{"id":' . $_POST['signers'][$s] . ',"type":1, "urgent": 1}';
                }
                $last_signers = end($_POST['agreementlist']);
                $last_signersArr = json_decode($last_signers, true);
                if ($last_signersArr[0]['stage'] == '') {
                    //Если последний элемент - это секция с подписантами, то меняем её содержимое
                    $last_signers = '[{"stage":"","list_type":"1","urgent":"1"},' . implode(',', $signers) . ']';
                } else {
                    //Иначае добавляем секцию с подписантами
                    $_POST['agreementlist'][] = '[{"stage":"","list_type":"1","urgent":"1"},' . implode(',', $signers) . ']';
                }
            }

            $_POST['active'] = 1;
            $_POST['name'] = $tmpl->name;// . ' № ' . $_POST['order_number'];
            $_POST['header'] = $agreement_header;
            $_POST['body'] = $agreement_body;
            $_POST['bottom'] = $agreement_bottom;
            $_POST['documentacial'] = 1; //Приказ
            //$_POST['doc_number'] = $_POST['order_number'];
            //$_POST['docdate'] = $_POST['order_date'];
            $_POST['signators'] = $_POST['signers'];
            $_POST['status'] = 0;
            $_POST['source_id'] = $_POST['ins'];//$source_id->id;
            $_POST['source_table'] = 'checkinstitutions';
            $_POST['plan_id'] = $_POST['plan'];
            $_POST['ins_id'] = $_POST['ins'];
            $_POST['prev_ins_id'] = $_POST['ins'];


            $docCreateResult = $reg->createDocument($_POST, $doc_id);

            if ($docCreateResult['result']) {
                $alertMessage = ' Приказ о проверке создан.';
                $log_action = $doc_id > 0 ? 'Сохранены изменения в приказе' : 'Создан новый приказ';
                $reg->insertTaskLog($doc_id, $log_action, 'calendar', 'order_staff');
            } else {
                $err++;
                $errStr[] = $docCreateResult['resultText'];
            }

        }

        if ($err == 0) {

            echo json_encode(array(
                'result' => true,
                'resultText' => $alertMessage . '
                                <script>
                                el_app.reloadMainContent();
                                el_app.dialog_close("order_staff");
                                el_app.dialog_close("check_staff");
                                el_app.dialog_close("registry_create");
                                </script>',
                'post' => $_POST,
                'errorFields' => [])
            );
        } else {
            echo json_encode(array(
                'result' => false,
                'resultText' => implode('<br>', $errStr),
                'post' => $_POST,
                'errorFields' => $errorFields)
            );
        }

    }
} else {
    echo json_encode(array(
        'result' => false,
        'resultText' => '<script>alert("Ваша сессия устарела.");document.location.href = "/"</script>',
        'errorFields' => [])
    );
}