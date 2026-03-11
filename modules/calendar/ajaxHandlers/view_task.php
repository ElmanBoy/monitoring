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
$auth = new Auth();
$alert = new Notifications();
$cache = new Cache();
$reg = new Registry();
$temp = new Templates();
$date = new Date();

$plan_uid = $_POST['uid'];
$taskId = intval($_POST['task_id']);
$insId = intval($_POST['ins']);

$err = 0;
$errStr = [];
$alertMessage = [];
$checklistValues = [];
$checkstaffValues = [];
$clear_agreement = [];
$act_body_own = '';
$act_body_other = '';


if ($auth->isLogin()) {

    if ($auth->checkAjax()) {

        $users = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name', 'position', 'ministries']);
        //Находим задачу в checkstaff
        $check = $db->selectOne('checkstaff', ' WHERE id = ?', [$taskId]);
        //Находим шаблон задачи
        $task = $db->selectOne('tasks', ' WHERE id = ?', [$check->task_id]);
        //Из шаблона задачи получаем id чек-листа и выбираем название таблицы
        $check_table = $db->select('checklists', ' WHERE id IN (' .
            implode(', ', json_decode($task->sheet)) . ')'
        );
        //Удаляем собственные нарушения и потом записываем заново
        $db->db::exec('DELETE FROM ' . TBL_PREFIX . 'checksviolations WHERE tasks = ' . $taskId);
        //Листаем чек-листы
        $block_number = 1;
        foreach ($check_table as $ch_table) {
            $table_name = $ch_table->table_name;
            //и поля чек-листа
            $check_fields = $db->select('checkfields', 'WHERE reg_id = ?', [$ch_table->id]);
            $field_ids = [];
            foreach ($check_fields as $fi) {
                $field_ids[] = $fi->prop_id;
            }
            //Проверка обязательных полей
            if (count($field_ids) > 0) {
                $prop_fields = $db->select('checkitems', ' WHERE id IN (' . implode(', ', $field_ids) . ')');
                foreach ($prop_fields as $cf) {
                    if ($cf->required == 1 && !isset($_POST[$cf->field_name])) {
                        $err++;
                        $errStr[] = 'Заполните поле &laquo;' . $cf->label . '&raquo;';
                    } else {
                        $checklistValues[$cf->field_name] = $_POST[$cf->field_name];
                    }
                }
            }

            if (count($checklistValues) > 0 && $err == 0) {
                $checklistValues['active'] = 1;
                $checklistValues['author'] = $_SESSION['user_id'];
                $checklistValues['created_at'] = date('Y-m-d H:i:s');
                try {
                    $checklist_exist = $db->selectOne($table_name, ' WHERE id = ?', [$check->record_id]);
                    if (intval($checklist_exist->id) > 0) {
                        $db->update($table_name, $check->record_id, $checklistValues);
                        $last_id = $check->record_id;
                    } else {
                        //Вставляем данные в таблицу чек-листа
                        $db->insert($table_name, $checklistValues);
                        $last_id = $db->last_insert_id;
                    }

                    //Редактируем задачу в checkstaff
                    $checkstaffValues['done'] = strlen($_POST['sign']) > 0 ? 1 : 0;;
                    $checkstaffValues['longitude'] = $_POST['longitude'];
                    $checkstaffValues['latitude'] = $_POST['latitude'];
                    $checkstaffValues['arrival'] = $_POST['arrival'];
                    $checkstaffValues['ending'] = date('Y-m-d H:i:s');
                    $checkstaffValues['record_id'] = intval($last_id);
                    $checkstaffValues['sign'] = $_POST['sign'];
                    $checkstaffValues['geo_comment'] = $_POST['geo_comment'];
                    /*if (isset($_FILES['files']) && count($_FILES['files']) > 0 && is_array($_POST['custom_names'])) {
                        $files = new \Core\Files();
                        $fileIds = $files->attachFiles($_FILES['files'], $_POST['custom_names']);
                        if ($fileIds['result']) {
                            $checkstaffValues['file_ids'] = json_encode($fileIds['ids']);
                            $reg->insertTaskLog($taskId, 'Приложение файлов');
                        } else {
                            echo $fileIds['message'];
                        }
                    }*/
                    //TODO: Выделить этот фрагмент в отдельный универсальный метод
                    if (isset($_FILES['files']) && count($_FILES['files']) > 0 && is_array($_POST['custom_names'])) {
                        $files = new \Core\Files();
                        $existFilesIds = [];
                        if ($taskId > 0) {
                            $fileExist = $db->selectOne('checkstaff', ' WHERE id = ?', [$taskId]);
                            if (!is_null($fileExist->file_ids)) {
                                $existFilesIds = is_string($fileExist->file_ids)
                                    ? json_decode($fileExist->file_ids) : $fileExist->file_ids;
                            }
                        }
                        $fileIds = $files->attachFiles($_FILES['files'], $_POST['custom_names']);
                        if ($fileIds['result']) {
                            if (!is_null($existFilesIds)) {
                                $_POST['file_ids'] = json_encode(array_merge($existFilesIds, $fileIds['ids']));
                            } else {
                                $_POST['file_ids'] = json_encode($fileIds['ids']);
                            }
                            $reg->insertTaskLog($taskId, 'Приложение файлов', 'assigned', 'view_task');
                        } else {
                            echo $fileIds['message'];
                        }
                    }
                    //Сохраняем выявленные нарушения
                    if (isset($_POST['violation_text'])) {
                        $violation_items = [];
                        for ($v = 0; $v < count($_POST['violation_text']); $v++) {
                            $violation_data = [];
                            $violation_items[] = ['name' => $_POST['violation_text'][$v]];

                            if ($_POST['violation_checklist_id'][$v] == $ch_table->id) {
                                //пишем только для текущего чек-листа, иначе записывается несколько раз
                                $violation_data = [
                                    'active' => 1,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'author' => $_SESSION['user_id'],
                                    'tasks' => $taskId,
                                    'checklist' => $_POST['violation_checklist_id'][$v],
                                    'violations' => $_POST['violation_type'][$v],
                                    'name' => $_POST['violation_text'][$v]
                                ];
                                //Если пришли чужие нарушения, их надо обновить
                                if (intval($_POST['otherAuthor'][$v]) > 0 && intval($_POST['violation_id'][$v]) > 0) {
                                    $violation_update = [
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'violations' => $_POST['violation_type'][$v],
                                        'name' => $_POST['violation_text'][$v]
                                    ];
                                    $db->update('checksviolations', $_POST['violation_id'][$v], $violation_update);
                                } else {
                                    $db->insert('checksviolations', $violation_data);
                                }
                            }
                        }
                    }
                    $db->update('checkstaff', $taskId, $checkstaffValues);

                    $reg->insertTaskLog($taskId, 'Редактирование задачи');

                    $act_body_own .= $reg->renderCheckResult($ch_table->id, $checklistValues, $block_number);
                    $block_number++;


                    //Перестраиваем кэш заданий
                    $reg->buildTasksListsToCache($check->user);
                    $cache->deleteFromCache('tasks', $check->user, $taskId);

                } catch (\RedBeanPHP\RedException $e) {
                    $err++;
                    $errStr[] = $e->getMessage();
                } catch (Exception $e) {
                }
            }
        }


        //Если нажата кнопка "Подписать и сформировать акт"
        if (isset($_POST['sign']) && strlen($_POST['sign']) > 0) {

            $reg->insertTaskLog($taskId, 'Подписание выполнения');

            //Получаем данные плана, в рамках которого делается акт
            $plan = $db->selectOne('checksplans', ' WHERE uid = ? ORDER BY version DESC LIMIT 1', [$check->check_uid]);
            $addInstitution = json_decode($plan->addinstitution, true);
            foreach ($addInstitution as $ads) {
                if ($ads['institutions'] == $insId) {
                    $check_periodArr = explode(' - ', $ads['check_periods']);
                    $check_period_start = $check_periodArr[0];
                    $check_period_end = $check_periodArr[1];
                }
            }

            //Получение названия учреждения
            $ins = $db->selectOne('institutions', ' WHERE id = ?', [$insId]);
            $insName = $ins->name;

            //Получение дат проведения проверки по всем проверяющим данной группы
            $checkArr = $db->select('checkstaff', ' WHERE check_uid = ? AND institution = ?',
                [$check->check_uid, $insId]
            );
            $dateResults = [];
            foreach ($checkArr as $chr) {
                $dateResults[] = $date->getMinMaxDates($chr->dates);
            }
            $allMinDates = array_column($dateResults, 'min');
            $allMaxDates = array_column($dateResults, 'max');
            $globalMin = $date->dateToString(min($allMinDates));
            $globalMax = $date->dateToString(max($allMaxDates));

            //Получение данных о приказе на проверку (documentacial=1 — приказ, не акт)
            $order = $db->selectOne('agreement', ' WHERE documentacial = 1 AND plan_id = ? AND ins_id = ?',
                [$plan->id, $insId]
            );
            $order_number = $order->doc_number ?? '';
            $order_date = $order->docdate ?? '';

            //Получение данных об исполнителях проверки (только текущая группа по check_uid и учреждению)
            $check_executors = [];
            $shUsers = $db->select('checkstaff', ' WHERE check_uid = ? AND institution = ?',
                [$check->check_uid, $insId]
            );
            foreach ($shUsers as $ch) {
                $head = $db->selectOne('users', ' WHERE id = ?', [$ch->user]);
                if ($ch->is_head == 1) {
                    $head_fio = mb_substr(trim($head->name), 0, 1) . '. ' .
                        mb_substr(trim($head->middle_name), 0, 1) . '. ' . $head->surname;
                    $head_position = $head->position;
                    $institutionsArr = $db->selectOne('institutions', ' WHERE id = ?', [$head->institution]);
                    $head_institution = $institutionsArr->name;
                    $ministriesArr = $db->selectOne('ministries', ' WHERE id = ?', [$head->ministries]);
                    $head_ministries = $ministriesArr->name;
                    $unitsArr = $db->selectOne('units', ' WHERE id = ?', [$head->division]);
                    $head_unit = $unitsArr->name;
                } else {
                    $check_executors[] = mb_substr(trim($head->name), 0, 1) . '. ' .
                        mb_substr(trim($head->middle_name), 0, 1) . '. ' . $head->surname . ' - ' . $head->position;
                }
            }

            //Сохранение акта и листа согласования в agreement
            //Получаем шаблон акта
            $tmpl = $db->selectOne('documents', ' where id = ?', [intval($_POST['document'])]);
            $agreement_header = '';
            $header_vars = [
                'order_date' => $date->correctDateFormatFromMysql($order_date),
                'order_number' => $order_number,
                'check_period_start' => $globalMin,
                'check_period_end' => $globalMax,
                'act_number' => $_POST['doc_number'],
                'act_date' => $date->correctDateFormatFromMysql($_POST['docdate']),
                'institution' => $insName,
                'list_executors' => implode(',<br>', $check_executors)
            ];
            /*
             * {{ order_date }}
             * {{ order_number }}
             * {{ check_period_start }}
             * {{ check_period_end }}
             * {{ act_date }}
             * {{ act_number }}
             * {{ institution }}
             * */
            $agreement_header .= $temp->twig_parse($tmpl->header, $header_vars);


            //Получение результатов чек-листов остальных сотрудников
            //Находим задачу в checkstaff
            $other_check = $db->select('checkstaff', ' WHERE check_uid = ? AND institution = ? AND "user" != ?',
                [$check->check_uid, $insId, $check->user]
            );
            foreach ($other_check as $och) {
                //Находим шаблон задачи
                $task = $db->selectOne('tasks', ' WHERE id = ?', [$och->task_id]);
                //Из шаблона задачи получаем id чек-листа и выбираем название таблицы
                $check_table = $db->select('checklists', ' WHERE id IN (' .
                    implode(', ', json_decode($task->sheet)) . ')'
                );
                //Листаем чек-листы
                foreach ($check_table as $ch_table) {
                    $table_name = $ch_table->table_name;
                    $checklistValues = $db->selectOne($table_name, ' WHERE id = ?', [$och->record_id]);
                    $act_body_other .= $reg->renderCheckResult($ch_table->id, (array)$checklistValues, $block_number);
                    $block_number++;
                }
            }


            $body_vars = [
                'institution' => $insName,
                'check_period_start' => $globalMin,
                'check_period_end' => $globalMax,
                'verifiable_start' => $date->correctDateFormatFromMysql($check_period_start),
                'verifiable_end' => $date->correctDateFormatFromMysql($check_period_end),
                'order_number' => $order_number,
                'order_date' => $order_date,
                'head_fio' => $head_fio,
                'head_position' => $head_position,
                'act_body' => $act_body_own . $act_body_other,
                'violations' => $violation_items,
                'list_executors' => implode(',<br>', $check_executors)
            ];
            /*$body_vars['institution'] = $insName;
            $body_vars['act_body'] = $act_body;*/
            $check_number = 1;
            /*
             * {{ institution }}
             * {{ act_body }}
             * */

            $agreement_body = $temp->twig_parse($tmpl->body, $body_vars);

            $bottom_vars = [
                'control_institution' => $head_institution,
                'control_ministries' => $head_ministries,
                'control_unit' => $head_unit,
                'head_position' => $head_position,
                'head_short' => $head_fio
            ];
            /*
             * {{ control_institution }}
             * {{ control_ministries }}
             * {{ control_unit }}
             * {{ head_position }}
             * {{ head_short }}
             * */
            $agreement_bottom = $temp->twig_parse($tmpl->bottom, $bottom_vars);


            //Добавляем подписантов в отдельную последнюю секцию-этап
            /*if (count($_POST['signers']) > 0) {
                $signers = [];
                for ($s = 0; $s < count($_POST['signers']); $s++) {
                    $signers[] = '{"id":' . $_POST['signers'][$s] . ',"type":1, "urgent": 1}';
                }
                $_POST['agreementlist'][] = '[{"stage":"","list_type":"1","urgent":"1"},' . implode(',', $signers) . ']';
            }*/

            $_POST['active'] = 1;
            $_POST['name'] = 'Акт проверки № ' . $_POST['doc_number'];
            $_POST['header'] = $agreement_header;
            $_POST['body'] = $agreement_body;
            $_POST['bottom'] = $agreement_bottom;
            $_POST['documentacial'] = 2;
            $_POST['status'] = 0;
            $_POST['source_id'] = $insId;
            $_POST['signators'] = $_POST['signers'];
            $_POST['source_table'] = 'checkinstitutions';

            // Проверяем — акт уже существует?
            $existingAct = $db->selectOne('agreement',
                " WHERE documentacial = 2 AND source_table = 'checkinstitutions' AND source_id = ? AND plan_id = ?",
                [$insId, $plan->id]
            );
            $existingActId = ($existingAct && intval($existingAct->id) > 0) ? intval($existingAct->id) : 0;

            $_POST['plan_id'] = $plan->id;
            $docCreateResult = $reg->createDocument($_POST, $existingActId);

            if (is_array($_POST['agreementlist']) && count($_POST['agreementlist']) > 0) {
                for ($s = 0; $s < count($_POST['agreementlist']); $s++) {
                    if (strlen(trim($_POST['agreementlist'][$s])) > 0) {
                        $clear_agreement[] = $_POST['agreementlist'][$s];

                        //Выявляем подписантов
                        $sections = json_decode($_POST['agreementlist'][$s], true);
                        $signers = [];
                        foreach ($sections as $sec) {
                            if (intval($sec['type']) == 1) { //подписание
                                $signers[] = $sec['id'];
                            }
                        }
                        $signer_1 = $signers[0];
                        $signer_1_position = $users['result'][$signer_1]->position;
                        $signer_2 = $signers[1];
                        $signer_2_position = $users['result'][$signer_2]->position;
                    }
                }
                $_POST['agreementlist'] = $clear_agreement;
                foreach ($clear_agreement as $ag) {
                    $agRow = json_decode($ag, true);
                    for ($s = 0; $s < count($agRow); $s++) {
                        if (!isset($agRow[$s]['stage'])) {
                            $alert->notificationSigner(
                                $agRow[$s]['id'],
                                $agRow[$s]['type'],
                                $docCreateResult['documentId'],
                                $_POST['short'] . ' № ' . $_POST['doc_number']
                            );
                        }
                    }
                }
            }

            //Ищем руководителя объекта (роль 5 — директор учреждения, roles хранится как JSON)
            $director = $db->selectOne('users',
                " WHERE institution = ? AND (roles::jsonb @> '[5]' OR roles = '5')",
                [$insId]
            );
            if ($director->id) {
                $alert->notificationObject(
                    $director->id,
                    2,
                    $docCreateResult['documentId'],
                    $_POST['short'] . ' № ' . $_POST['doc_number']
                );
            } else {
                //$err++;
                $alertMessage[] = 'Не найден руководитель объекта проверки для оповещения';
            }


            if ($docCreateResult['result']) {
                $alertMessage[] = 'Акт проверки создан.';
            } else {
                $err++;
                $errStr[] = $docCreateResult['resultText'];
            }

        } // конец if (sign)


        if ($err == 0) {
            echo json_encode(array(
                    'result' => true,
                    'resultText' => (strlen($_POST['sign']) > 0 ? 'Задание выполнено.' : 'Результаты сохранены.') . '
                                <script>
                                el_app.reloadMainContent();
                                el_app.dialog_close("view_task");
                                el_app.updateNotifications();' .
                        (count($alertMessage) > 0 ? 'alert("' . implode('<br>', $alertMessage) . '");' : '') . '
                                </script>',
                    'post' => $_POST,
                    'errorFields' => [])
            );
        } else {
            echo json_encode(array(
                    'result' => false,
                    'resultText' => implode('<br>', $errStr),
                    'post' => $_POST,
                    'errorFields' => [])
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