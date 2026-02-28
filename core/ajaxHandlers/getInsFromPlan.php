<?php
use Core\Db;
use Core\Date;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db();
$date = new Date();
$planId = intval($_POST['planId']);
$selected = intval($_POST['selected']);
$documentacial = intval($_POST['documentacial']);
$rowArr = ['<option value="0">&nbsp;</option>'];
$check_dates = [];
$datesArr = [];
$minDate = '';
$maxDate = '';
$reviewPeriods = [];
$tmplArr = [];
$templates = [];

$pl = $db->getRegistry('institutions', '', [], ['short']);

$plan = $db->selectOne('checksplans', " WHERE id = ?", [$planId]);
if($plan) {
    $check = json_decode($plan->addinstitution, true);

    foreach ($check as $ch) {
        $sel = ($selected == $ch['institutions']) ? ' selected' : '';
        $rowArr[] = '<option value="' . $ch['institutions'] . '"'.$sel.'>'.$pl['result'][$ch['institutions']]->short.'</option>';
    }
}
if($planId == 0){
    foreach ($pl['result'] as $p) {
        $sel = ($selected == $p->id) ? ' selected' : '';
        $rowArr[] = '<option value="' . $p->id . '"'.$sel.'>'.$p->short.'</option>';
    }

}else{
    if(intval($plan->checks) > 0) {
        $templates = $db->db::getAll('SELECT * FROM ' . TBL_PREFIX . 'documents 
    WHERE checks = ' . $plan->checks . ' AND documentacial = '.$documentacial.' ORDER BY name'
        );
    }
    if (count($templates) > 0) {

        foreach ($templates as $u) {
            $tmplArr[] = '<option value="' . $u['id'] . '"' . ($u['id'] == $selected ? ' selected' : '') . '>' .
                stripslashes(htmlspecialchars($u['name'])) . '</option>';
        }
    }
}



echo json_encode([
    'ins' => implode("\n", $rowArr),
    'uid' => $plan->uid == null ? '0' : $plan->uid,
    'checks' => $plan->checks,
    'order' => implode("\n", $tmplArr)
]);
