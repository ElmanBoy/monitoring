<?php
include $_SERVER['DOCUMENT_ROOT'].'/modules/calendar/dialogs/assign_staff.php';
/*use Dompdf\Dompdf;
use Core\Gui;
use Core\Db;
use Core\Auth;
use Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
//print_r($_POST);
$gui = new Gui;
$db = new Db;
$auth = new Auth();
$reg = new Registry();

$insId = intval($_POST['params'][0]);
$uid = $_POST['params'][1];

if ($auth->isLogin()) {
    //Открываем транзакцию
    $busy = $db->transactionOpen('roles', 1);
    $trans_id = $busy['trans_id'];

    if ($busy != []) {

        $units = $db->getRegistry('units', 'where institution = 12 and active =1');


        $ins = $db->getRegistry('institutions');
        $tasks = $db->getRegistry('tasks');
        $users = $db->getRegistry('users', "where roles <> '2'", [], ['surname', 'name', 'middle_name']);
        */?><!--
        <div class='pop_up drag'>
            <div class='title handle'>
                <div class='name'>Назначение сотрудника на проверку</div>
                <div class='button icon close'><span class='material-icons'>close</span></div>
            </div>
            <div class='pop_up_body'>
                <form class='ajaxFrm' id='check_staff' onsubmit="return false">
                    <input type="hidden" name="uid" value="<?/*=$uid*/?>">
                    <input type='hidden' name='ins' value="<?/*= $insId */?>">
                    <div class='group'>
                        <div class="item w_100">
                            <div class="el_data">
                                <label>Объект проверки:</label>
                                <strong><?/*= stripslashes($ins['array'][$insId]) */?></strong>
                            </div>
                        </div>

                        <div class='group staff'>
                            <h5 class='item w_100 question_number'>Сотрудник №1</h5>
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <select data-label='Ведомство' name='institutions[]'>
                                        <?/*
                                        echo $gui->buildSelectFromRegistry($ins['result'], [12], true);
                                        */?>
                                    </select>
                                </div>
                            </div>
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <select data-label='Подразделение' name='units[]'>
                                        <?/*
                                        echo $gui->buildSelectFromRegistry($units['result'], [], true);
                                        */?>
                                    </select>
                                </div>
                            </div>
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <select data-label='Сотрудник' name='users[]'>
                                        <?/*
                                        echo $gui->buildSelectFromRegistry($users['result'], [], true,
                                            ['surname', 'name', 'middle_name'], ' '
                                        );
                                        */?>
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <select data-label='Задача' name='tasks[]'>
                                        <?/*
                                        echo $gui->buildSelectFromRegistry($tasks['result'], [], true);
                                        */?>
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <label>Период проверки</label>
                                    <input class='el_input range_date' type='date' name='dates[]'
                                           value="<?/*= date('Y-m-d') */?>">
                                </div>
                            </div>
                        </div>
                        <?/*/*button class='button icon text new_staff'><span class='material-icons'>add</span>Еще
                            сотрудник
                        </button*/?>
                    </div>
                    <div style="height: 200px"></div>
                    <div class='confirm'>
                        <button class='button icon text'><span class='material-icons'>save</span>Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
        <script src="/modules/plans/js/registry.js"></script>
        <script>
            el_registry.bindCalendar();
            el_app.bindGetUnitsByOrg();
            $("select[name='institutions[]']").trigger("change");
        </script>
        <?php
/*    } else {
        */?>
        <script>
            alert("Эта запись редактируется пользователем <?/*=$busy->user_name*/?>");
            el_app.dialog_close("role_edit");
        </script>
        --><?/*
    }

} else {
    echo '<script>alert("Ваша сессия устарела.");document.location.href = "/"</script>';
}*/

?>
