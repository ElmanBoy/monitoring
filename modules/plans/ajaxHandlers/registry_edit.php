<?php
use Core\Date;
use Core\Db;
use Core\Registry;
use Core\Templates;
use Core\Notifications;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();
$regId = 38; //План проверок
$rowId = intval($_POST['reg_id']); // ID плана
$insArr = [];
$uniqueIns = [];

$db = new Db();
$temp = new Templates();
$reg = new Registry();
$date = new Date();
$alert = new Notifications();

$plan = $db->selectOne('checksplans', ' where id = ?', [$rowId]);
$signs = $db->select('signs', ' where doc_id = ?', [$rowId]);
$tmpl = $db->selectOne('documents', ' where id = ?', [intval($_POST['document'])]);
$inst = $db->getRegistry('institutions');
$insp = $db->getRegistry('inspections');
$units = $db->getRegistry('units');
$users = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name']);
$checks = json_decode($plan->addinstitution, true);

$regProps = $db->db::getAll('SELECT
            ' . TBL_PREFIX . 'regfields.prop_id AS fId, 
            ' . TBL_PREFIX . 'regfields.required AS required, 
            ' . TBL_PREFIX . 'regprops.*
            FROM ' . TBL_PREFIX . 'regfields, ' . TBL_PREFIX . 'regprops
            WHERE ' . TBL_PREFIX . 'regfields.prop_id = ' . TBL_PREFIX . 'regprops.id AND 
            ' . TBL_PREFIX . 'regfields.reg_id = ? ORDER BY ' . TBL_PREFIX . 'regfields.sort', [$regId]
);

//Проверяем обязательные поля
if(!isset($_POST['institutions']) || count($_POST['institutions']) == 0){
    $err++;
    $errStr[] = 'Укажите проверяемые учреждения';
    $errorFields[] = 'institutions[]';
}else{
    $insArr = [];
    $uniqueIns = [];
    for($i = 0; $i < count($_POST['institutions']); $i++){
        if(in_array(intval($_POST['institutions'][$i]), $uniqueIns)){
            $key = array_search(intval($_POST['institutions'][$i]), $uniqueIns);
            $err++;
            $errStr[] = 'Учреждение №'.($i + 1).' дублирует учреждение №'.($key + 1);
            $errorFields[] = 'institutions['.$i.']';
        }else {
            if (intval($_POST['institutions'][$i]) == 0) {
                $err++;
                $errStr[] = 'Укажите учреждение №' . ($i + 1);
                $errorFields[] = 'institutions[' . $i . ']';
            } else {
                $uniqueIns[] = intval($_POST['institutions'][$i]);
            }
        }
        if(strlen($_POST['periods'][$i]) == 0){
            $err++;
            $errStr[] = 'Укажите период проверки для учреждения №'.($i + 1);
            $errorFields[] = 'periods['.$i.']';
        }
        if(strlen($_POST['check_periods'][$i]) == 0){
            $err++;
            $errStr[] = 'Укажите проверяемый период для учреждения №'.($i + 1);
            $errorFields[] = 'check_periods['.$i.']';
        }
        /*if(intval($_POST['inspections'][$i]) == 0){
            $err++;
            $errStr[] = 'Укажите предмет проверки для учреждения №'.($i + 1);
            $errorFields[] = 'inspections['.$i.']';
        }*/
        $insArr[$i] = [
            'check_types' => $_POST['checks'],
            'institutions' => $_POST['institutions'][$i],
            'units' => $_POST['units'][$i],
            'periods' => $_POST['periods'][$i],
            'periods_hidden' => $_POST['periods_hidden'][$i],
            'inspections' => $_POST['inspections'],
            'check_periods' => $_POST['check_periods'][$i],
        ];
    }
    $_POST['addinstitution'] = $insArr;
    $_POST['active'] = 0;
}
//print_r($insArr);
foreach ($regProps as $f) {
    $check = $reg->checkRequiredField($regId, $f, $_POST);
    if(!$check['result']){
        $err++;
        $errStr[] = $check['message'];
        $errorFields[] = $check['errField'];
    }
}

if($err == 0) {

    $row = $db->selectOne('checksplans', ' where id = ?', [$rowId]);

    $last = $db->db::getRow('SELECT MAX(version) AS last_version, uid FROM ' . TBL_PREFIX . "checksplans 
    WHERE uid = '" . $row->uid . "' GROUP BY uid");
    reset($regProps);
    $registry = [
        'created_at' => date('Y-m-d H:i:s'),
        'author' => $_SESSION['user_id'],
        'active' => 0,
        'uid' => $last['uid']
    ];

    //Подгатавливаем данные для ввода в БД
    foreach ($regProps as $f) {
        $value = $reg->prepareValues($f, $_POST);
        $registry[$f['field_name']] = $value;
    }

    //Обновляем проверяемые учреждения. Удаляем старые и добавляем по новой.
    $db->db::exec('DELETE FROM ' . TBL_PREFIX . "checkinstitutions WHERE 
    plan_uid = '" . addslashes($_POST['uid']) . "' AND plan_version = " . intval($_POST['version'])
    );
    //Добавляем учреждения в cam_checkinstitutions
    $new_ins_ids = [];
    foreach ($insArr as $ins) {
        //Получаем новый массив учреждений
        $new_ins_ids[] = $ins['institutions'];
        $ch_periods = explode(' - ', $ins['check_periods']);
        $ai = $reg->addInstitutionToPlan(
            $_POST['uid'],
            $_POST['version'],
            $ins['institutions'],
            $ins['check_types'],
            $ins['periods'],
            $ins['periods_hidden'],
            $ins['inspections'],
            $ch_periods[0],
            $ch_periods[1],
            intval($ins['units'])
        );

        if (!$ai['result']) {
            $err++;
            $errStr[] = implode('<br>', $ai['errors']);
        }
    }

    //Ищем приказы к этому плану
    $orders = $db->select('agreement', ' WHERE plan_id = ? AND documentacial = 1', [$rowId]);
    if (count($orders) > 0) {
        //Получаем приказы на отсутствующие в плане учреждения
        $order_ids = [];
        foreach ($orders as $order) {
            //Если в приказе учреждение, которого нет в новой версии плана
            if (!in_array($order->ins_id, $new_ins_ids)) {
                $order_ids[] = $order->id;
            }
        }
        //Удаляем приказы и задания на отсутствующие учреждения
        if (count($order_ids) > 0) {
            $db->delete('agreement', $order_ids);
            $db->db->exec('DELETE FROM ' . TBL_PREFIX . 'checkstaff WHERE order_id IN (' . implode(', ', $order_ids) . ')');
        }
    }

    if($err == 0) {

        if (isset($_FILES['files']) && count($_FILES['files']) > 0 && is_array($_POST['custom_names'])) {
            $files = new \Core\Files();
            $existFilesIds = [];
            if ($rowId > 0) {
                $fileExist = $db->selectOne('agreement', ' WHERE id = ?', [$rowId]);
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
                $reg->insertTaskLog($rowId, 'Приложение файлов &laquo;' .
                    implode('&raquo;, &laquo;', $_POST['custom_names']) . '&raquo;', 'plans', 'registry_edit'
                );
            } else {
                echo $fileIds['message'];
            }
        }

        //Если план еще не утвержден, то можно редактировать. Иначе создаем новую версию плана
        if (intval($row->active) == 0) {
            $result = $db->update('checksplans', $rowId, $registry);

            //Изменяем документ плана
            $agreement_header = '';
            $header_vars = [
                'today_date' => $date->dateToString(date('Y-m-d')),
                //'sign_kiriuhin' => $temp->getSign(json_decode($sign_vars[1], 'true')['certificate_info'])
            ];

            $agreement_header .= $temp->twig_parse($tmpl->header, $header_vars);

            $agreement_header .= $temp->twig_parse($_POST['longname'], ['curr_year' => date('Y')]);

            $body_vars = [];
            $check_number = 1;
            reset($insArr);
            if (is_array($insArr) && count($insArr) > 0) {

                foreach ($insArr as $ch) {
                    $dateArr = explode(' - ', $ch['check_periods']);
                    $check_period = $date->correctDateFormatFromMysql($dateArr[0]).' - '.$date->correctDateFormatFromMysql($dateArr[1]);
                    $body_vars[] = [
                        'check_number' => $check_number,
                        'institution' => stripslashes($inst['result'][$ch['institutions']]->short),
                        'unit' => stripslashes($units['array'][$ch['units']]),
                        'inspections' => stripslashes($insp['array'][$ch['inspections']]),
                        'period' => $ch['periods'],
                        'check_periods' => $check_period
                    ];
                    $check_number++;
                }
            }
            $agreement_body = $temp->twig_parse($tmpl->body, ['checks' => $body_vars]);

            $bottom_vars = [
                'today_date' => $date->dateToString(date('Y-m-d')),
                //'sign_liahova' => $temp->getSign(json_decode($sign_vars[4], true)['certificate_info']),
                //'approval_sheet' => ''
            ];
            $agreement_bottom = $temp->twig_parse($tmpl->bottom, $bottom_vars);


            //$_POST['agreementList'] = $reg->fixJsonArray($_POST['agreementList']);
            $last_agreement_row = json_decode(end($_POST['agreementlist']), true);

            $clear_agreement = [];
            //Очищаем от пустых секций
            for($s = 0; $s < count($_POST['agreementlist']); $s++){
                if(strlen(trim($_POST['agreementlist'][$s])) > 0){
                    $clear_agreement[] = $_POST['agreementlist'][$s];
                }
            }
            $_POST['agreementlist'] = $reg->fixJsonArray($clear_agreement);

            $docNumber = strlen($_POST['doc_number']) > 0 ? ' № ' . $_POST['doc_number'] : '';
            $_POST['active'] = 1;
            $_POST['name'] = $_POST['short'] . $docNumber;
            $_POST['header'] = $agreement_header;
            $_POST['body'] = $agreement_body;
            $_POST['bottom'] = $agreement_bottom;
            $_POST['documentacial'] = 3;
            $_POST['status'] = 0;
            $_POST['source_id'] = $rowId;
            $_POST['source_table'] = 'checksplans';
            //Создание документа плана в cam_agreement
            $docCreateResult = $reg->createDocument($_POST, $rowId);



            //TODO: Добавить отправку уведомленний подписанту и объекту проверки
            /*foreach ($clear_agreement as $ag) {
                $agRow = json_decode($ag, true);
                for($s = 0; $s < count($agRow); $s++) {
                    if (!isset($agRow[$s]['stage'])) {
                        $alert->notificationSigner(
                            $agRow[$s]['id'],
                            $agRow[$s]['type'],
                            $docCreateResult['documentId'],
                            $_POST['short'] . ' № ' . $_POST['doc_number']);
                    }
                }
            }*/

            if (!$docCreateResult['result']) {
                $result = false;
                $message = $docCreateResult['resultText'];
            } else {
                $reg->insertTaskLog($rowId, 'Сохранены изменения в плане', 'plans', 'registry_edit');
                $result = true;
                $message = 'План успешно изменён.<script>
            el_app.reloadMainContent();
            el_app.dialog_close("registry_edit");
            el_app.updateNotifications();
            </script>';
            }

        } else {
            //Новая версия плана
            $registry['version'] = $last['last_version'] + 1;
            $registry['approved'] = 0;
            $result = $db->insert('checksplans', $registry);
            $new_plan_id = $db->last_insert_id;

            //Создаём документ плана
            $agreement_header = '';
            $header_vars = [
                'today_date' => $date->dateToString(date('Y-m-d')),
                //'sign_kiriuhin' => $temp->getSign(json_decode($sign_vars[1], 'true')['certificate_info'])
            ];

            $agreement_header .= $temp->twig_parse($tmpl->header, $header_vars);

            $agreement_header .= $temp->twig_parse($_POST['longname'], ['curr_year' => date('Y')]);

            $body_vars = [];
            $check_number = 1;
            if (is_array($insArr) && count($insArr) > 0) {
                foreach ($insArr as $ch) {
                    $body_vars[] = [
                        'check_number' => $check_number,
                        'institution' => stripslashes($inst['array'][$ch['institutions']]),
                        'unit' => stripslashes($units['array'][$ch['units']]),
                        'inspections' => stripslashes($insp['array'][$ch['inspections']]),
                        'period' => $ch['periods'],
                        'check_periods' => $ch['check_periods']
                    ];
                    $check_number++;
                }
            }
            $agreement_body = $temp->twig_parse($tmpl->body, ['checks' => $body_vars]);

            $bottom_vars = [
                'today_date' => $date->dateToString(date('Y-m-d')),
                //'sign_liahova' => $temp->getSign(json_decode($sign_vars[4], true)['certificate_info']),
                'approval_sheet' => ''
            ];
            $agreement_bottom = $temp->twig_parse($tmpl->bottom, $bottom_vars);

            $db->insert('agreement', [
                    'active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'author' => $_SESSION['user_id'],
                    'documentacial' => 3,
                    'document' => $_POST['document'],
                    //'doc_number' => $docNumber,
                    'name' => $_POST['short'],
                    'header' => $agreement_header,
                    'body' => $agreement_body,
                    'bottom' => $agreement_bottom,
                    'signators' => json_encode($_POST['signators']),
                    'status' => 0,
                    'source_id' => $new_plan_id
                ]
            );
            $reg->insertTaskLog($new_plan_id, 'Создана новая версия плана', 'plans', 'registry_edit');
            $message = 'Создана новая версия плана.
            <script>
            el_app.reloadMainContent();
            el_app.dialog_close("registry_edit");
            </script>';
        }

    }else{
        $result = false;
        $message = implode('<br>', $errStr);
    }
}else{
    $message = '<strong>Ошибка:</strong><br> '.implode('<br>', $errStr);
}
echo json_encode(array(
    'result' => $result,
    'resultText' => $message,
    'errorFields' => $errorFields));
?>