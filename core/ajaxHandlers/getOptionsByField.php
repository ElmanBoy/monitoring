<?php
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db();
$auth = new Auth();

$table = $_POST['table'];
$fieldId = intval($_POST['fieldId']);
$out = [];


if($auth->isLogin()) {
    $options = $db->select($table, ' WHERE id = '.$fieldId); //print_r($options);
    switch($options[$fieldId]->type){
        case 'select':
        case 'multiselect':
            $opts = json_decode($options[$fieldId]->options_list, true);
            foreach($opts as $o){
                $out[] = '<option value="'.$o['value'].'">'.$o['label'].'</option>';
            }
            break;
        case 'checkbox':
            $opts = json_decode($options[$fieldId]->checkbox_values, true);
            foreach($opts as $o){
                $out[] = '<option value="'.$o['value'].'">'.$o['label'].'</option>';
            }
            break;
        case 'radio':
            $opts = json_decode($options[$fieldId]->radio_values, true);
            foreach($opts as $o){
                $out[] = '<option value="'.$o['value'].'">'.$o['label'].'</option>';
            }
            break;
        case 'list_fromdb':
        case 'list_fromdb_multi':
            $regTable = $db->selectOne('registry', ' WHERE id = '.intval($options[$fieldId]->from_db));
            $regItems = $db->select($regTable->table_name);
            foreach($regItems as $item){
                $out[] = '<option value="'.$item->id.'">'.$item->name.'</option>';
            }
            break;
    }
    echo implode("\n", $out);
}