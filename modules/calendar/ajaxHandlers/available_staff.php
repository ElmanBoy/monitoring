<?php
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
//print_r($_POST);
$db = new Db();
$auth = new Auth();

$dates = $_POST['dates'];
$dateArr = explode(' - ', $dates);
$dateStart = $dateArr[0];
$dateEnd = $dateArr[1];
//$unit = intval($_POST['units']);
//$ministries = intval($_POST['ministries']);


if($auth->isLogin()) {

    //if ($auth->checkAjax()) {
        $subQuery = '';
        $user_selected = intval($_POST['user_selected']);
        if($user_selected > 0){
            $subQuery = " OR u.id = ".$user_selected;
        }
/*echo "WITH period AS (
    SELECT
        TO_DATE('$dateStart', 'YYYY-MM-DD') AS start_date,
        TO_DATE('$dateEnd', 'YYYY-MM-DD') AS end_date
)
SELECT u.id, u.name, u.surname, u.middle_name, u.institution, u.ministries, u.division, u.position
FROM " . TBL_PREFIX . "users u
         CROSS JOIN period p
WHERE (u.active = 1 
    AND institution = 1
    AND NOT u.roles @> '[\"2\"]'::jsonb
  AND NOT EXISTS (
        SELECT 1
        FROM " . TBL_PREFIX . "checkstaff cs
        WHERE cs.user = u.id
          AND cs.dates LIKE '%-%'
          AND TO_DATE(SUBSTRING(cs.dates FROM 1 FOR 10), 'YYYY-MM-DD') <= p.end_date
          AND TO_DATE(SUBSTRING(cs.dates FROM 14 FOR 10), 'YYYY-MM-DD') >= p.start_date
    ))
    $subQuery
ORDER BY u.surname, u.name;";*/

        $available = $db->db::getAll("WITH period AS (
    SELECT
        TO_DATE('$dateStart', 'YYYY-MM-DD') AS start_date,
        TO_DATE('$dateEnd', 'YYYY-MM-DD') AS end_date
)
SELECT u.id, u.name, u.surname, u.middle_name, u.institution, u.ministries, u.division, u.position
FROM " . TBL_PREFIX . "users u
         CROSS JOIN period p
WHERE (u.active = 1 
    AND institution = 1
    AND NOT u.roles @> '[\"2\"]'::jsonb
  AND NOT EXISTS (
        SELECT 1
        FROM " . TBL_PREFIX . "checkstaff cs
        WHERE cs.user = u.id
          AND cs.dates LIKE '%-%'
          AND TO_DATE(SUBSTRING(cs.dates FROM 1 FOR 10), 'YYYY-MM-DD') <= p.end_date
          AND TO_DATE(SUBSTRING(cs.dates FROM 14 FOR 10), 'YYYY-MM-DD') >= p.start_date
    ))
    $subQuery
ORDER BY u.surname, u.name;");



        $userList = ['<option value="0">&nbsp;</option>'];
        if(is_array($available) && count($available) > 0) {

            $ins = $db->getRegistry('institutions', '', [], ['short']);
            $mins = $db->getRegistry('ministries');
            $units = $db->getRegistry('units');

            foreach ($available as $key => $user) {
                $user_fio = trim($user['surname']).' '.trim($user['name']).' '.trim($user['middle_name']);

                $userTitle = ' title="'.$ins['result'][$user['institution']]->short.
                    (intval($user['ministries']) > 0 ? '<br>'.$mins['array'][$user['ministries']] : '').
                    (intval($user['division']) > 0 ? '<br>'.$units['array'][$user['division']] : '').
                    (strlen($user['position']) > 0 ? '<br>'.$user['position'] : '').'"';

                $userList[] = '<option value="'.$user['id'].'"'.($user_selected == $user['id'] ? ' selected' : '').
                    $userTitle.'>'.$user_fio.'</option>';
            }
        }
        echo implode("\n", $userList);
   //}
}else{
    echo json_encode(array(
        'result' => false,
        'resultText' => '<script>alert("Ваша сессия устарела.");document.location.href = "/"</script>',
        'errorFields' => []));
}