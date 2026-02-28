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
$keyfield = $columnName;
if($columnName == 'id'){
    $_POST['column'] = 'appeal_id';
    $keyfield = 'appeal_id';
}


$where_field = '';
//print_r($fields);
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
            $whereQueryArr[] = $_POST['column'].' = ?';
            $searchSlots[] = intval($search);
            break;
        case 'time without time zone':
        case 'date':
            break;
            $whereQueryArr[] = $_POST['column'].' = ?';
            $searchSlots[] = $search;
            break;
        case 'smallint':
            break;
        default:
            $whereQueryArr[] = 'LOWER('.$_POST['column'].') LIKE ?';
            $searchSlots[] = '%'.mb_strtolower($search).'%';
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
            $where_field = $_POST['column'].' = ?';
            $slots = [intval($search)];
            break;
        case 'time without time zone':
        case 'date':
        case 'smallint':
            break;
            $where_field = $_POST['column'].' = ?';
            $slots = [$search];
            break;
        default:
            $where_field = 'LOWER('.$_POST['column'].') LIKE ?';
            $slots = ['%'.mb_strtolower($search).'%'];
    }

    if(isset($_POST['parent']) && strlen($_POST['parent']) > 0){
        $where_field .= ' AND parent = '.intval($_POST['parent']);
    }
    if(strlen($_POST['ext_option']) > 0){
        //$where_field .=  $_POST['ext_option'];
    }

    if(strlen($where_field) > 0){
        $where_field = ' AND '.$where_field;
    }

}
$view_field = $_POST['value'];

$result = R::getAll( '
        SELECT a.id AS id, m.*, a.* FROM '.TBL_PREFIX.'main m
        INNER JOIN '.TBL_PREFIX.'appeals a ON a.appeal_id = m.id
        WHERE a.is_claim = 1 '.$where_field.' ORDER BY a.id DESC', $slots );


//print_r($result);
/*echo 'SELECT a.id AS id, m.*, a.* FROM '.TBL_PREFIX.'main m
        INNER JOIN '.TBL_PREFIX.'appeals a ON a.appeal_id = m.id
        WHERE a.is_claim = 1 '.$where_field.' ORDER BY a.id DESC'; print_r($slots);*/

$answerArr = array();

foreach ($result as $res){
    $answerArr[] = array('id' => $res['id'], 'value' => $res[$keyfield], 'text' => $res[$keyfield]);
}

echo json_encode($answerArr);
?>