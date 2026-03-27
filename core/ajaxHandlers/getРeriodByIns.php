<?php
use Core\Db;
use Core\Date;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db();
$date = new Date();
$plan_uid = $_POST['uid'];
$insId = intval($_POST['insId']);
$minDate = '';
$maxDate = '';
$reviewPeriods = [];
$reviewPeriod = '';
$checkPeriod = '';
$unit_selected = $_POST['unit_selected'] ?? ''; // Оставляем как строку, т.к. теперь может быть "legal_123"
$unitsArr = [];

if($insId > 0) {
    $plan = $db->selectOne('checksplans', " WHERE uid = '$plan_uid' ORDER BY version DESC LIMIT 1");
    if (strlen($plan->addinstitution) > 0) {
        $reviewPeriods = $date->getReviewPeriodsFromJson($plan->addinstitution, $plan->year);

        $reviewPeriod = $reviewPeriods[$insId]['actionPeriod'];
        $checkPeriod = $reviewPeriods[$insId]['checkPeriod'];
        $minDate = $date->correctDateFormatToMysql($reviewPeriods[$insId]['action_start_date']);
        $maxDate = $date->correctDateFormatToMysql($reviewPeriods[$insId]['action_end_date']);
    }

    $inn = $db->selectOne("institutions", " WHERE id = ?", [$insId]);

    // Проверяем, что учреждение найдено
    if (!$inn) {
        echo json_encode([
            'minDate' => $minDate,
            'maxDate' => $maxDate,
            'actionPeriod' => $reviewPeriod,
            'checkPeriod' => $checkPeriod,
            'units' => ''
        ]);
        exit;
    }

    // Сначала добавляем адреса из самой таблицы institutions
    $addedAddresses = []; // Для отслеживания уже добавленных адресов

    if (!empty($inn->legal)) {
        $unitsArr[] = '<option value="legal_' . $inn->id . '"' .
            ($unit_selected == 'legal_' . $inn->id ? ' selected' : '') . '>' .
            'Юр. адрес: ' . htmlspecialchars(stripslashes($inn->legal)) . '</option>';
        $addedAddresses[] = trim($inn->legal);
    }

    if (!empty($inn->target_address) && !in_array(trim($inn->target_address), $addedAddresses)) {
        $unitsArr[] = '<option value="target_' . $inn->id . '"' .
            ($unit_selected == 'target_' . $inn->id ? ' selected' : '') . '>' .
            'Факт. адрес: ' . htmlspecialchars(stripslashes($inn->target_address)) . '</option>';
        $addedAddresses[] = trim($inn->target_address);
    }

    if (!empty($inn->location) && !in_array(trim($inn->location), $addedAddresses)) {
        $unitsArr[] = '<option value="location_' . $inn->id . '"' .
            ($unit_selected == 'location_' . $inn->id ? ' selected' : '') . '>' .
            'Адрес местонахождения: ' . htmlspecialchars(stripslashes($inn->location)) . '</option>';
        $addedAddresses[] = trim($inn->location);
    }

    // Затем добавляем адреса из таблицы insadress (если есть ИНН)
    if (!empty($inn->inn)) {
        $units = $db->select('insadress',  " WHERE inn = ? ORDER BY basic DESC", [$inn->inn] );

        if(count($units) > 0) {
            foreach($units as $u){
                $unitsArr[] = '<option value="insadress_' . $u->id . '"' .
                    ($unit_selected == 'insadress_' . $u->id ? ' selected' : '') . '>' .
                    htmlspecialchars(stripslashes($u->target_address)) . '</option>';
            }
        }
    }

    // Логирование для отладки
    error_log("getPeriodByIns: insId=$insId, inn=" . ($inn->inn ?? 'null') .
              ", legal=" . ($inn->legal ?? 'null') .
              ", target_address=" . ($inn->target_address ?? 'null') .
              ", location=" . ($inn->location ?? 'null') .
              ", units count=" . count($unitsArr));

    echo json_encode([
        'minDate' => $minDate,
        'maxDate' => $maxDate,
        'actionPeriod' => $reviewPeriod,
        'checkPeriod' => $checkPeriod,
        'units' => implode("\n", $unitsArr),
        '_debug' => [ // Временная отладка
            'insId' => $insId,
            'inn' => $inn->inn ?? null,
            'legal' => $inn->legal ?? null,
            'target_address' => $inn->target_address ?? null,
            'location' => $inn->location ?? null,
            'unitsCount' => count($unitsArr)
        ]
    ]
    );
}
