<?php
use Core\Db;
use Core\Auth;
use Core\Gui;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db();
$auth = new Auth();
$gui = new Gui();

if($auth->isLogin()) {

    $content = file_get_contents('http://10.12.127.3:8090/rpgu/rest/spr/org/uson');
    $json = json_decode($content, TRUE);
    //print_r($json);
    $total = 0;
    $find = 0;
    $new = 0;
    $err = 0;
    $messages = [];
    foreach($json as $ins) {
        if ($ins['shortname'] != 'Пример') {
            $searchQuery = " short = '" . $ins['shortname'] . "'";
            if ($ins['inn'] != null) {
                $searchQuery .= " AND inn = " . $ins['inn'];
            }
            if ($ins['branchId'] != null) {
                $searchQuery .= ' AND branchid = ' . $ins['branchId'];
            }

            $exist = $db->selectOne('institutions', " WHERE $searchQuery ");

            $phoneStr = '';
            if($ins['institutionPhone'] != null){
                $phoneArr = [];
                $phArr = [];
                if(substr_count($ins['institutionPhone'], ';') > 0) {
                    $phoneArr = explode(';', $ins['institutionPhone']);
                }elseif(substr_count($ins['institutionPhone'], ',') > 0) {
                    $phoneArr = explode(',', $ins['institutionPhone']);
                }elseif(substr_count($ins['institutionPhone'], '/') > 0) {
                    $phoneArr = explode('/', $ins['institutionPhone']);
                }elseif(substr_count($ins['institutionPhone'], ' +') > 0) {
                    $phoneArr = explode(' +', $ins['institutionPhone']);
                }else{
                    $phoneArr = [$ins['institutionPhone']];
                }
                foreach($phoneArr as $ph){
                    $phArr[] = $gui->formatPhone(trim($ph));
                }
                $phoneStr = implode(', ', $phArr);
            }

            $insData = [
                'created_at' => date('Y-m-d H:i:s'),
                'author' => $_SESSION['user_id'],
                'active' => 1,
                'eaisid' => $ins['guid'],
                'short' => stripslashes($ins['shortname']),
                'name' => stripslashes($ins['shortname']),
                'inn' => $ins['inn'],
                'jar' => $ins['bank_bik'],
                'calculated' => $ins['accountNumber'],
                'legal' => str_replace('building', 'д.', stripslashes($ins['institutionAddress'])),
                'phones' => $phoneStr,
                'branchid' => $ins['branchId'],
                'branchname' => stripslashes($ins['branchName']),
                'branch_adress' => str_replace('building', 'д.', stripslashes($ins['branch_address'])),
                'email' => str_replace(';', ',', $ins['institutionEmail']),
                'leader' => $ins['directorName'],
                'capacity' => $ins['institutionCapacity'],
                'vehicles' => $ins['institutionVehicles'] != null ? 1 : 0
            ];

            $bName = $ins['branchName'] != null ? ', Отделение: ' . $ins['branchName'] : '';

            if ($exist->id) {
                $messages[] = 'Обновлено учреждение - ' . $ins['shortname'] . ', ИНН: ' . $ins['inn'] . $bName;
                $db->update('institutions', $exist->id, $insData);
                $find++;
            } else {
                $messages[] = 'Новое учреждение - ' . $ins['shortname'] . ', ИНН: ' . $ins['inn'] . $bName.' '.$phoneStr;
                try {
                    $db->insert('institutions', $insData);
                } catch (\RedBeanPHP\RedException $e) {
                    $err++;
                    $messages[] = $e->getMessage();
                }
                $new++;
            }
            $total++;
        }
    }
    //Удаление дубликатов
    $db->db::exec("DELETE FROM ".TBL_PREFIX."institutions
        WHERE id NOT IN (
            SELECT DISTINCT ON (name, short, inn, COALESCE(branchid::text, branchname)) 
                id
            FROM cam_institutions
            ORDER BY 
                name, 
                short,
                inn, 
                COALESCE(branchid::text, branchname),
                branchid DESC NULLS LAST,
                id DESC
        )");

    $messages[] = 'Всего: '.$total.' учреждений. Из них '.$find.' обновлено в реестре. Добавлено '.$new;
    echo json_encode([
        'result' => $err == 0,
        'resultText' => '<ol><li>'.implode('</li><li>', $messages).'</li></ol>'
    ]);
}else{
    echo json_encode([
        'result' => false,
        'resultText' => 'Требуется авторизация'
    ]);
}