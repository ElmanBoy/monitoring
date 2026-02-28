<?php
use Core\Db;
use \Core\Registry;
use Core\Gui;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$err = 0;
$errStr = array();
$result = false;
$errorFields = array();
$regId = intval($_POST['parent']);
$separator = trim($_POST['separator']);
$db = new Db();
$reg = new Registry();
$gui = new Gui();
$resultHtml = '';
//print_r($_FILES);
//print_r($_POST);


if(isset($_FILES['excelFile']['type'])){
    $file_type = $_FILES['excelFile']['type'];

    if($file_type == 'text/csv'){
        try {
            $result = $reg->comparisonImportCsv($_FILES['excelFile']['tmp_name'], $regId, $separator);
            $errStr = $result;
            $resultHtml = '<input type="hidden" name="fileName" value="'.$result['fileName'].'">';
            //Нераспознанные
            if(count($result['unmatchedFields']) > 0){
                $resultHtml .= '<div class="item w_100"><strong>Сопоставьте нераспознанные поля:</strong></div>';

                $fields = $db->getRegistry("regfields", ' WHERE reg_id = ? ', [$regId], ['label', 'prop_id']);

                $fieldsSelect = '<option value="">&nbsp;</option>';
                foreach($fields['result'] as $f){
                    //В селектах не должно быть уже распознанных полей
                    if(!array_search(trim($f->label), array_column($result['matchedFields'], 'label'))) {
                        $fieldsSelect .= '<option value="' . $f->prop_id . '">' . $f->label . '</option>';
                    }
                }

                foreach($result['unmatchedFields'] as $i => $h){
                    $resultHtml .= '<div class="item w_50"><div class="el_data"> '.trim($h).'</div>'.
                        '<input type="hidden" name="file_fields['.$i.']" value="'.$h.'"> </div>'.
                        '<div class="item w_50"><div class="el_data">'.
                        '<select data-label="Поле в справочнике" name="table_fields['.$i.']">'.$fieldsSelect.'</select>'.
                        '</div></div>';
                }
                $resultHtml .= '<div class="item w_100"><i>Несопоставленные поля не будут импортированы.</i></div>';
            }else{
                $resultHtml .= '<div class="item w_50"><div class="el_data">Все поля распознаны. Можно импортировать.</div></div>';
            }
            $resultHtml .= '<div class="item w_50">
                <div class="el_data">
                    <div class="custom_checkbox">
                        <label class="container">
                            <span class="label-text">Обновить данные найденных аналогичных элементов справочника</span>
                            <input type="checkbox" checked="checked" name="rewriteData" id="rewriteData" class="is_claim" tabindex="-1" value="1">
                            <span class="checkmark"></span>
                        </label>
                    </div>
                </div>
            </div>';
            //Распознанные
            if(count($result['matchedFields']) > 0){
                foreach($result['matchedFields'] as $i => $h){
                    $resultHtml .= '<input type="hidden" name="file_fields['.$i.']" value="'.trim($h['label']).'">'.
                        '<input type="hidden" name="table_fields['.$i.']" value="'.$h['prop_id'].'">';
                }
            }
        } catch (\RedBeanPHP\RedException\SQL $e) {
            $err++;
            $errStr[] = $e->getMessage();
        }
    }else{
        $err++;
        $errStr[] = 'Неподдерживаемый формат файла';
    }
}

echo json_encode(array(
    'result' => $err == 0,
    'resultHtml' => $resultHtml,
    'resultText' => $errStr,
    'errorFields' => ['file']));