<?php
use Core\Db;
use Core\Auth;
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
//print_r($_POST);
$db = new Db();
$auth = new Auth();
$err = 0;
$errStr = [];
$ins = $_POST['institution'];
$period = json_decode($_POST['period']);
$year = intval($_POST['year']);
$currentPlan = intval($_POST['plan']);
$out = [];


if($auth->isLogin()) {

    $insReg = $db->getRegistry('institutions');

    /*$result = $db->db::getAll("SELECT
    cp.id as plan_id,
    cp.year as plan_year,
    cp.active,
    cp.short,
    cp.doc_number,
    (inst->>'institutions')::INTEGER as institution_id,
    inst->>'periods' as existing_quarter,
    inst->>'check_periods' as date_range,
    inst->'periods_hidden' as hidden_months,
    ARRAY['".implode("','", $period)."'] as search_months,
    $year as search_year,
    $ins as search_institution,
    'Пересечение по месяцам' as match_type
FROM cam_checksplans cp,
LATERAL jsonb_array_elements(cp.addinstitution) AS inst
WHERE 
    cp.year = $year
    AND (inst->>'institutions')::INTEGER = $ins
    AND EXISTS (
        SELECT 1 FROM jsonb_array_elements_text(
            (inst->>'periods_hidden')::jsonb
        ) 
        WHERE value IN ('".implode("','", $period)."')
    );");

    if(count($result) > 0) {
        foreach ($result as $r) {
            $out['plans'][] = $r['short'] . ' № ' . $r['doc_number'].($r['active'] == 1
                    ? " <span class='greenText'><span class='material-icons green'>task_alt</span> утверждён</span>" :
                    " <span class='greyText'><span class='material-icons grey'>radio_button_unchecked</span>на рассмотрении</span>");
            $out['name'] = $insReg['result'][$r['institution_id']]->short;
            $out['quarters'] = $r['existing_quarter'];
        }
        echo json_encode($out);
    }*/

    $result = $db->db::getAll("SELECT
    (inst->>'institutions')::INTEGER as institution_id,
    COUNT(cp.id) as total_plans,
    JSONB_AGG(
            JSONB_BUILD_OBJECT(
                    'plan_id', cp.id,
                    'plan_year', cp.year,
                    'active', cp.active,
                    'short', cp.short,
                    'doc_number', cp.doc_number,
                    'existing_quarter', inst->>'periods',
                    'date_range', inst->>'check_periods',
                    'hidden_months', inst->'periods_hidden'
                )
        ) as plans_details
FROM cam_checksplans cp,
     LATERAL jsonb_array_elements(cp.addinstitution) AS inst
WHERE
  cp.year = $year
  AND cp.id <> $currentPlan
  AND EXISTS (
        SELECT 1 FROM jsonb_array_elements_text(
                (inst->>'periods_hidden')::jsonb
            )
        WHERE value IN ('".implode("','", $period)."')
    )
GROUP BY institution_id
ORDER BY institution_id;");

    if(count($result) > 0) {
        $i = 0;
        foreach ($result as $r) {
            if(in_array($r['institution_id'], $ins)) {
                $plan = json_decode($r['plans_details'], true);
                if (count($plan) > 0) {
                    foreach ($plan as $pl) {
                        $out[$i]['plans'][] = $pl['short'] . ' № ' . $pl['doc_number'] . ($pl['active'] == 1
                                ? " <span class='greenText'><span class='material-icons green'>task_alt</span> утверждён</span>" :
                                " <span class='greyText'><span class='material-icons grey'>radio_button_unchecked</span>на рассмотрении</span>");
                        $out[$i]['quarters'][] = $pl['existing_quarter'];
                        $out[$i]['quarters'] = array_unique($out[$i]['quarters']);
                    }
                }

                $out[$i]['name'][] = $insReg['result'][$r['institution_id']]->short;
                $i++;
            }
        }
        echo json_encode($out);
    }


}else{
    echo json_encode(array(
        'result' => false,
        'resultText' => '<script>alert("Ваша сессия устарела.");document.location.href = "/"</script>',
        'errorFields' => []));
}