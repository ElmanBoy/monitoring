<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();

$table = TBL_PREFIX.$_POST['source'];
$search = addslashes($_POST['search']);

$fields = R::inspect( $table );


$whereQueryArr = [];
$searchSlots = [];
$slots = [];
if(substr_count($_POST['column'], '.') > 0){
    $columnNameArr = explode('.', $_POST['column']);
    $columnName = $columnNameArr[1];
}else{
    $columnName = $_POST['column'];
}

$where_field = '';

foreach ($fields as $field => $type){
    /*if($type == 'integer' || $type == 'smallint'){
        $whereQueryArr[] = $field." = ? ";
        $searchSlots[] = intval($search);
    }else{
        $whereQueryArr[] = 'LOWER('.$field.') LIKE ? ';
        $searchSlots[] = '%'.mb_strtolower($search).'%';
    }*/

    switch($type){
        case 'integer':
        case 'bigint':
            $whereQueryArr[] = $field.' = ?';
            $searchSlots[] = intval($search);
            break;
        case 'time without time zone':
        case 'date':
            break;
            $whereQueryArr[] = $field.' = ?';
            $searchSlots[] = $search;
            break;
        case 'smallint':
            break;
        default:
            if($field == 'user_fio'){
                $whereQueryArr[] = 'LOWER(name) LIKE ?';
                $searchSlots[] = '%' . mb_strtolower($search) . '%';
                $whereQueryArr[] = 'LOWER(surname) LIKE ?';
                $searchSlots[] = '%' . mb_strtolower($search) . '%';
                $whereQueryArr[] = 'LOWER(middle_name) LIKE ?';
            }else {
                $whereQueryArr[] = 'LOWER(' . $field . ') LIKE ?';
            }
            $searchSlots[] = '%' . mb_strtolower($search) . '%';
    }
}
$whereAll = implode(' OR ', $whereQueryArr);


if($_POST['column'] == 'all') {
    $where_field = $whereAll;
    $slots = $searchSlots;
}else{
    switch($fields[$columnName]){
        case 'integer':
        case 'bigint':
            $where_field = $columnName.' = ?';
            $slots = [intval($search)];
            break;
        case 'time without time zone':
        case 'date':
        case 'smallint':
            break;
            $where_field = $columnName.' = ?';
            $slots = [$search];
            break;
        default:

            if($columnName == 'user_fio'){
                $where_field = 'LOWER(name) LIKE ? OR LOWER(surname) LIKE ? OR LOWER(middle_name) LIKE ? ';
                $slots = ['%' . mb_strtolower($search) . '%', '%' . mb_strtolower($search) . '%', '%' . mb_strtolower($search) . '%'];
            }else {
                $where_field = 'LOWER(' . $columnName . ') LIKE ?';
                $slots = ['%' . mb_strtolower($search) . '%'];
            }
    }

    if(isset($_POST['parent']) && strlen($_POST['parent']) > 0){
        $where_field .= ' AND parent = '.intval($_POST['parent']);
    }
    if(strlen($_POST['ext_option']) > 0){
        $where_field .=  $_POST['ext_option'];
    }

}
$view_field = $_POST['value'];
//echo $table.' '.$where_field; print_r($slots);
$result = R::findALL($table, $where_field." ORDER BY id DESC", $slots);
//$result = R::find($table, $where_field." ORDER BY id DESC", $slots);
//$appeals = R::loadJoined( $result, TBL_PREFIX.'appeals' );
//print_r($result); //print_r($appeals);
$answerArr = array();

foreach ($result as $res){
    $values = array_column($answerArr, 'value');
    if($columnName == 'user_fio'){
        $answerArr[] = array(
            'id' => $res->id,
            'value' => trim($res->name).' '.trim($res->middle_name).' '.trim($res->surname),
            'text' => $res->name.' '.$res->middle_name.' '.$res->surname);
    }else {
        if (!in_array($res->{$columnName}, $values)) {
            $answerArr[] = array('id' => $res->id, 'value' => htmlspecialchars($res->{$columnName}), 'text' => $res->{$columnName});
        }
    }
}

echo json_encode($answerArr);
?>