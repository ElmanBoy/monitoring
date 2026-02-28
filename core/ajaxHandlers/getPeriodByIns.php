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
$unit_selected = intval($_POST['unit_selected']);
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
    $units = $db->select('insadress',  " WHERE inn = ? ORDER BY basic DESC", [$inn->inn] );

    if(count($units) > 0) {
        //$unitsArr[] = '<option value="">&nbsp;</option>';
        foreach($units as $u){
            $unitsArr[] = '<option value="'.$u->id.'"'.($u->id == $unit_selected ? ' selected' : '').'>'.
                htmlspecialchars(stripslashes($u->target_address)).'</option>';
        }
    }

    echo json_encode([
        'minDate' => $minDate,
        'maxDate' => $maxDate,
        'actionPeriod' => $reviewPeriod,
        'checkPeriod' => $checkPeriod,
        'units' => implode("\n", $unitsArr)
    ]
    );
}
