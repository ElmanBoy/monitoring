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
$regId = 38;
$insArr = [];
$uniqueIns = [];
$clear_agreement = [];

$db = new Db();
$temp = new Templates();
$reg = new Registry();
$date = new Date();
$alert = new Notifications();

$plan = $db->selectOne('checksplans', ' where id = ?', [$regId]);
$signs = $db->select('signs', ' where doc_id = ?', [$regId]);
$tmpl = $db->selectOne('documents', ' where id = ?', [intval($_POST['document'])]);
$inst = $db->getRegistry('institutions');
$insp = $db->getRegistry('inspections');
$units = $db->getRegistry('units');
$users = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name', 'position', 'ministries']);
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
$_POST['addinstitution'] = '1';
if(!is_array($_POST['institutions']) || count($_POST['institutions']) == 0 || strlen(trim($_POST['institutions'][0])) == 0) {
    $err++;
    $errStr[] = 'Укажите проверяемые учреждения';
    $errorFields[] = 'institutions[]';
}elseif (!isset($_POST['inspections']) || intval($_POST['inspections']) == 0){
    $err++;
    $errStr[] = 'Укажите предмет проверки';
    $errorFields[] = 'inspections[]';
}else{
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
    $_POST['uid'] = uniqid();

}

foreach ($regProps as $f) {
    $check = $reg->checkRequiredField($regId, $f, $_POST);
    if(!$check['result']){
        $err++;
        $errStr[] = $check['message'];
        $errorFields[] = $check['errField'];
    }
}

if($err == 0) {

    reset($regProps);
    $registry = [
        'created_at' => date('Y-m-d H:i:s'),
        'author' => $_SESSION['user_id'],
        'uid' => $_POST['uid']
    ];

    foreach($insArr as $ins){
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

        if(!$ai['result']){
            $err++;
            $errStr[] = implode('<br>', $ai['errors']);
        }
    }

    if($err == 0) {

        foreach ($regProps as $f) {
            $value = $reg->prepareValues($f, $_POST);
            $registry[$f['field_name']] = $value;
        }
        try {
            $db->insert('checksplans', $registry);
            $new_plan_id = $db->last_insert_id;
            $signer_1 = 0;
            $signer_2 = 0;
            $signer_1_position = '';
            $signer_2_position = '';


            for($s = 0; $s < count($_POST['agreementlist']); $s++){
                if(strlen(trim($_POST['agreementlist'][$s])) > 0){
                    $clear_agreement[] = $_POST['agreementlist'][$s];

                    //Выявляем подписантов
                    $sections = json_decode($_POST['agreementlist'][$s], true);
                    $signers = [];
                    foreach($sections as $sec){
                        if(intval($sec['type']) == 1){ //подписание
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

            //Создаём документ плана
            $agreement_header = '';
            $header_vars = [
                'agreement_date' => '_________',
                'signer_1' => $signer_1,
                'signer_1_position' => $signer_1_position
            ];

            $agreement_header .= $temp->twig_parse($tmpl->header, $header_vars);

            $agreement_header .= $temp->twig_parse($_POST['longname'], ['curr_year' => date('Y')]);

            $body_vars = [];
            $check_number = 1;
            if(is_array($insArr) && count($insArr) > 0) {
                foreach ($insArr as $ch) {
                    $body_vars[] = [
                        'check_number' => $check_number,
                        'institution' => stripslashes($inst['result'][$ch['institutions']]->name),
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
                'agreement_date' => '_________',
                'signer_1' => $signer_2,
                'signer_1_position' => $signer_2_position
            ];
            $agreement_bottom = $temp->twig_parse($tmpl->bottom, $bottom_vars);


            $docNumber = strlen($_POST['doc_number']) > 0 ? ' № '.$_POST['doc_number'] : '';
            $_POST['active'] = 1;
            $_POST['name'] = $_POST['short'].$docNumber;
            $_POST['header'] = $agreement_header;
            $_POST['body'] = $agreement_body;
            $_POST['bottom'] = $agreement_bottom;
            $_POST['documentacial'] = 3;
            $_POST['status'] = 0;
            $_POST['source_id'] = $new_plan_id;
            $_POST['source_table'] = 'checksplans';

            if (isset($_FILES['files']) && count($_FILES['files']) > 0 && is_array($_POST['custom_names'])) {
                $files = new \Core\Files();
                $existFilesIds = [];
                if ($new_plan_id > 0) {
                    $fileExist = $db->selectOne('agreement', ' WHERE id = ?', [$new_plan_id]);
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
                    $reg->insertTaskLog($new_plan_id, 'Приложение файлов &laquo;' .
                        implode('&raquo;, &laquo;', $_POST['custom_names']) . '&raquo;', 'plans', 'registry_edit'
                    );
                } else {
                    echo $fileIds['message'];
                }
            }

            $docCreateResult = $reg->createDocument($_POST);

            //TODO: Добавить отправку уведомленний подписанту и объекту проверки
            foreach ($clear_agreement as $ag) {
                $agRow = json_decode($ag, true);
                /*for($s = 0; $s < count($agRow); $s++) {
                    if (!isset($agRow[$s]['stage'])) {
                        $alert->notificationSigner(
                            $agRow[$s]['id'],
                            $agRow[$s]['type'],
                            $docCreateResult['documentId'],
                            $_POST['short'] . $docNumber);
                    }
                }*/
                for($s = 0; $s < count($agRow); $s++) {
                    if (!isset($agRow[$s]['stage']) && isset($agRow[$s]['id'])) {
                        $alert->notificationSigner(
                            $agRow[$s]['id'],
                            $agRow[$s]['type'],
                            $docCreateResult['documentId'],
                            $_POST['short'] . $docNumber);
                        break;
                    }
                }
            }


            if(!$docCreateResult['result']){
                $result = false;
                $message = $docCreateResult['resultText'];
            }else {
                $reg->insertTaskLog($new_plan_id, 'Создан новый план', 'plans', 'registry_edit');
                $result = true;
                $message = 'План успешно создан.
                <script>
                el_app.reloadMainContent();
                el_app.dialog_close("registry_create");
                el_app.updateNotifications();
                </script>';
            }

        } catch (\RedBeanPHP\RedException $e) {
            $result = false;
            $message = $e->getMessage();
        }
    } else {
        $result = false;
        $message = implode('<br>', $errStr);
    }

}else{
    $message = '<strong>Ошибка:</strong><br>'.implode('<br>', $errStr);
}
echo json_encode(array(
    'result' => $result,
    'agreement' => $clear_agreement,
    'resultText' => $message,
    'errorFields' => $errorFields));
?>