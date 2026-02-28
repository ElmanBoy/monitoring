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
$db = new Db();
$reg = new Registry();
$gui = new Gui();
$resultHtml = '';
//print_r($_FILES);
//print_r($_POST);


if(isset($_FILES['excelFile']['type'])){

    // 1. Проверка расширения
    $ext = pathinfo($_FILES['excelFile']['name'], PATHINFO_EXTENSION);
    if (strtolower($ext) !== 'xlsx') {
        $err++;
        $errStr[] = 'Неверный формат файла.';
    }

    if($reg->isXLSX( $_FILES['excelFile']['tmp_name']) && $err == 0){
        try {
            $result = $reg->importPlan($_FILES['excelFile']['tmp_name'], $_POST['table_begin'], $_POST['plan_name']);
            //$reg->comparisonImportCsv($_FILES['excelFile']['tmp_name'], $regId);
            $errStr = $result;
            //Нераспознанные
            if(is_array($result['unmatched']) && count($result['unmatched']) > 0){
                $resultHtml = '<div class="item w_100"><strong>Сопоставьте нераспознанные учреждения:</strong></div>';

                $fields = $db->getRegistry("institutions", '', [], ['name', 'id']);

                $fieldsSelect = '<option value="">&nbsp;</option>';
                foreach($fields['result'] as $f){
                    //В селектах не должно быть уже распознанных полей
                    if(!array_search(trim($f->label), array_column($result['unmatched'], 'name'))) {
                        $fieldsSelect .= '<option value="' . $f->id . '">' . $f->name . '</option>';
                    }
                }

                foreach($result['unmatched'] as $i => $h){
                    $resultHtml .= '<div class="item w_50"><div class="el_data"> №'.$h['number'].'. '.trim($h['name']).'</div>'.
                        '<input type="hidden" name="institution['.$h['number'].']" value="'.$h['name'].'"> </div>'.
                        '<div class="item w_50"><div class="el_data">'.
                        '<select data-label="Учреждение в реестре" name="institution['.$h['number'].']">'.$fieldsSelect.'</select>'.
                        '</div></div>';
                }
                $resultHtml .= '<div class="item w_100"><i>Несопоставленные учреждения не будут импортированы.</i></div>';
            }else{
                $resultHtml = '<div class="item w_50"><div class="el_data">Все учреждения распознаны. Можно импортировать.</div></div>';
            }
            //Распознанные
            if(is_array($result['matched']) && count($result['matched']) > 0){
                foreach($result['matched'] as $i => $h){
                    $resultHtml .= '<input type="hidden" name="institution['.$h['number'].']" value="'.$h['id'].'">'.
                        '<input type="hidden" name="check_periods['.$h['number'].']" value="'.$h['check_periods'].'">'.
                        '<input type="hidden" name="periods_hidden['.$h['number'].']" value=\''.json_encode($h['periods_hidden']).'\'>'.
                        '<input type="hidden" name="periods['.$h['number'].']" value="'.$h['periods'].'">';
                }
            }
            $resultHtml .= '<input type="hidden" name="full_name" value="'.htmlspecialchars($result['plan_name']).'">'.
                '<input type="hidden" name="plan_year" value="'.intval($result['year']).'">';
        } catch (\RedBeanPHP\RedException\SQL $e) {
            $err++;
            $errStr[] = $e->getMessage();
        }
    }else{
        $err++;
        $errStr[] = 'Неподдерживаемый формат файла'.$_FILES['excelFile']['tmp_name'];
    }
}

echo json_encode(array(
    'data' => $result,
    'result' => $err == 0,
    'resultHtml' => $resultHtml,
    'resultText' => $errStr,
    'errorFields' => ['file']));