<?php
$_POST['params']['taskId'] = '0_0';
$_POST['params']['insId'] = '0_0';
include_once $_SERVER['DOCUMENT_ROOT'].'/modules/calendar/dialogs/order_staff.php';
exit();
use Dompdf\Dompdf;
use Core\Gui;
use Core\Db;
use Core\Auth;
use Core\Registry;
use Core\Date;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
//print_r($_POST);
$gui = new Gui;
$db = new Db;
$auth = new Auth();
$reg = new Registry();
$date = new Date();

$taskStr = 0;
$insStr = 0;
$task = 0;
$prevDate = date('Y-m-d');
$dates = date('Y-m-d').' - '.date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
$in_calendar = isset($_POST['params']['in_calendar']) && intval($_POST['params']['in_calendar']) == 1;

$plans = $db->getRegistry('checksplans');

if($in_calendar){
    $dates = $_POST['params']['startDate'] .' - '. $_POST['params']['endDate'];
    $prevDate = date('Y-m-d', strtotime($_POST['params']['startDate'] .' -1 day'));
}

if ($auth->isLogin()) {

    $ins = $db->getRegistry('institutions');
    $persons = $db->getRegistry('persons', '', [], ['surname', 'first_name', 'middle_name', 'birth']);
    $insector = $db->getRegistry('institutions', 'WHERE inspectors = 1');
    $tasks = $db->getRegistry('tasks');
    $ousr = $db->getRegistry('ousr');
    $users = $db->getRegistry('users', "where roles <> '2'", [], ['surname', 'name', 'middle_name']);
        ?>
        <div class='pop_up drag' style='width: 60vw;'>
            <div class='title handle'>
                <div class='name'>Создание новой задачи</div>
                <div class='button icon close'><span class='material-icons'>close</span></div>
            </div>
            <div class='pop_up_body'>
                <form class='ajaxFrm' id='new_task' onsubmit="return false">
                    <input type='hidden' name='path' value="calendar">
                    <div class='group'>
                        <div class='item w_100'>
                            <div class='el_data'>
                                <label>Объект проверки:</label>
                                <div class='custom_checkbox'>
                                    <label class='container' style='margin-left: 130px;'> Учреждение
                                        <input type='radio' name='object' value='1'><span
                                                class='checkmark radio'></span></label>
                                </div>
                                <div class='custom_checkbox'>
                                    <label class='container' style='margin-left: 280px;'> Физическое лицо
                                        <input type='radio' name='object' value='0'><span
                                                class='checkmark radio'></span></label>
                                </div>
                            </div>
                        </div>
                        <div class="item w_50" id="ins_select" style="display: none">
                            <div class="el_data">
                                <select data-label='Учреждение'>
                                <?
                                echo $gui->buildSelectFromRegistry($ins['result'], [], true);
                                ?>
                                </select>
                            </div>
                        </div>
                        <div class='item w_50' id="person_select" style='display: none'>
                            <div class='el_data'>
                                <select data-label='Физическое лицо'>
                                    <?
                                    echo $gui->buildSelectFromRegistry($persons['result'], [],
                                        true, ['surname', 'first_name', 'middle_name', 'birth'], ' ');
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="group w_100"><label>Кто проверяет:</label></div>
                        <div class='group staff'>
                            <h5 class='item w_100 question_number'>Сотрудник №1</h5>
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <label>Период проверки</label>
                                    <input class='el_input range_date' type='date' name='dates[]'
                                           value="<?= $dates ?>">
                                </div>
                            </div>
                            <?/*div class='item w_50'>
                                <div class='el_data'>
                                    <select data-label='Ведомство' name='institutions[]'>
                                        <?
                                        echo $gui->buildSelectFromRegistry($insector['result'], [1], true);
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <input type="hidden" name="units_hidden[]" value="<?=$chStaff->unit?>">
                                    <select data-label='Подразделение' name='units[]'>
                                        <? /*
                                        echo $gui->buildSelectFromRegistry($units['result'], [$chStaff->unit], true);
                                        ?>
                                    </select>
                                </div>
                            </div*/?>

                            <div class='item w_50'>
                                <div class='el_data'>
                                    <input type='hidden' name='users_hidden[]' value="<?= $chStaff->user ?>">
                                    <select data-label='Сотрудник' name='users[]'>
                                        <?
                                        /*echo $gui->buildSelectFromRegistry($users['result'], [$chStaff->user], true,
                                            ['surname', 'name', 'middle_name'], ' '
                                        );*/
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class='item w_50'>
                                <div class='custom_checkbox'>
                                    <label class='container' style='top: 12px;'>
                                        <span class='label-text'>Является руководителем проверки</span>
                                        <input type='radio' name='is_head[]' class='is_claim'
                                               tabindex='-1'
                                               value='1'<?= (intval($chStaff->is_head) == 1 ? ' checked="checked"' : '') ?>>
                                        <span class='checkmark radio'></span>
                                    </label>
                                </div>
                            </div>
                            <div class='item w_50' style="display: none">
                                <div class='el_data'>
                                    <select data-label='Структурное подразделение' name='ousr[]'>
                                        <?
                                        echo $gui->buildSelectFromRegistry($ousr['result'], [$chStaff->ousr], true);
                                        ?>
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <select data-label='Шаблон задачи' name='tasks[]'>
                                        <?
                                        echo $gui->buildSelectFromRegistry($tasks['result'], [$task], true);
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <div class='custom_checkbox'>
                                        <label class='container' style='left: 4px;'>
                                            <span class='label-text'>Включить напоминание</span>
                                            <input type='checkbox' name='allowremind[]'
                                                   class='is_claim' tabindex='-1' value='1'>
                                            <span class='checkmark'></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class='group reminder' style="margin-top: -10px; display: none">
                                <div class='item w_50'>
                                    <div class='el_data'>
                                        <label>Дата и время</label>
                                        <input class='el_input single_date_time' type='date'
                                               name='datetime[]' value='<?=$prevDate . ' 10:00'?>'>
                                    </div>
                                </div>
                                <div class='item w_100'>
                                    <div class='el_data'>
                                        <label>Примечания</label>
                                        <textarea class='el_textarea' name='comment[]'></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button class='button icon text new_staff'><span class='material-icons'>add</span>Еще
                            сотрудник
                        </button>
                    </div>



                    <div style="height: 100px"></div>
                    <div class='confirm'>
                        <button class='button icon text'><span class='material-icons'>save</span>Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
        <script src="/modules/calendar/js/registry.js"></script>
        <script>
            $(document).ready(function(){
                el_registry.bindCalendar();

                $("[name='allowremind[]']").off('change').on('change', function () {
                    let $reminder = $(this).closest(".group").find(".reminder");
                    if ($(this).prop('checked')) {
                        $reminder.show();
                        $reminder.find('input, textarea').attr('disabled', false);
                    } else {
                        $reminder.hide();
                        $reminder.find('input, textarea').attr('disabled', true);
                    }
                });

                let $staff = $(".staff");
                for(let i = 0; i < $staff.length; i++) {
                    el_registry.bindSetExecutorByDates($($staff[i]));
                }
                $("input[name='dates[]']").trigger("change");

                $("[name='object']").on("change", function(){
                    let val = $(this).val();
                    $("#ins_select, #person_select").hide().find('select').attr("name", "");
                    if(val === "1"){
                        $("#ins_select").show().find('select').attr('name', 'ins');
                    }else{
                        $('#person_select').show().find("select").attr('name', 'ins');
                    }
                });
                //$("select[name='institutions[]']").trigger("change");
            });
        </script>
        <?php

} else {
    echo '<script>alert("Ваша сессия устарела.");document.location.href = "/"</script>';
}

?>
