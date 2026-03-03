<?php

use Core\Gui;
use Core\Auth;

$auth = new Auth();

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

if (isset($_POST['params']) && substr_count($_POST['params'], 'plan=') > 0) {
    include_once $_SERVER['DOCUMENT_ROOT'] . '/modules/plans/pages/view_plan.php';
} else {
    $gui = new Gui;
    $module_props = $gui->getModuleProps('plans');
    $regs = $gui->getTableData('checksplans');
    ?>
    <div class="nav">
        <div class="nav_01">
            <?
            echo $gui->buildTopNav([
                'title' => 'Планы проверок',
                //'registryList' => '',
                'renew' => 'Сбросить все фильтры',
                'create' => 'Новый план',
                //'clone' => 'Копия плана',
                'delete' => 'Удалить выделенные',
                //'list_props' => 'Поля справочников',
                'logout' => 'Выйти'
            ]
            );
            ?>
        </div>

    </div>
    <div class="scroll_wrap">
        <ul class='breadcrumb'>
            <li><a href='/plans'>Все планы</a></li>
        </ul>
        <form method="post" id="registry_delete" class="ajaxFrm">
            <table class="table_data" id="tbl_registry">
                <thead>
                <tr>
                    <th>
                        <div class='custom_checkbox'>
                            <label class='container' title='Выделить все'>
                                <input type='checkbox' id='check_all'><span class='checkmark'></span>
                            </label>
                        </div>
                    </th>
                    <th class="sort" style="width: 100px">
                        <?
                        echo $gui->buildSortFilter(
                            'registry',
                            '№',
                            'id',
                            'el_data',
                            []
                        );
                        ?>
                    </th>
                    <th class="sort" style='width: 100px'>
                        <?
                        echo $gui->buildSortFilter(
                            'registry',
                            'Статус',
                            'active',
                            'constant',
                            ['1' => 'На рссмотрении', '0' => 'Утверждён']
                        );
                        ?>
                    </th>
                    <th class="sort" style='width: 60%'>
                        <?
                        echo $gui->buildSortFilter(
                            'registry',
                            'Наименование',
                            'name',
                            'el_data',
                            []
                        );
                        ?>
                    </th>
                    <th class='sort'>
                        <?
                        echo $gui->buildSortFilter(
                            'registry',
                            'На год',
                            'year',
                            'el_data',
                            []
                        );
                        ?>
                    </th>
                    <th class='sort'>
                        <?
                        echo $gui->buildSortFilter(
                            'registry',
                            'Версия',
                            'version',
                            'el_data',
                            []
                        );
                        ?>
                    </th>
                </tr>
                </thead>


                <tbody>
                <!-- row -->
                <?
                //Выводим созданные справочники
                $tab = 10;
                foreach ($regs as $reg) {
                    $tab++;
                    switch ($reg->active) {
                        case 1:
                            $icon = 'task_alt';
                            $status = 'Утвержден';
                            $class = 'green';
                            break;
                        case 2:
                            $icon = 'back_hand';
                            $status = 'Отклонён';
                            $class = 'redText';
                            break;
                        default:
                            $icon = 'radio_button_unchecked';
                            $status = 'На рассмотрении';
                            $class = 'grey';
                    }
                    $reg->doc_number = strlen($reg->doc_number) > 0 ? ' №' . $reg->doc_number : '';
                    echo '<tr data-id="' . $reg->id . '" tabindex="0">'.
                        (($reg->active != 1 && $reg->approved != 1 && ($_SESSION['user_id'] == $reg->author || $auth->isAdmin())) ? '
                            <td>
                                <div class="custom_checkbox">
                                    <label class="container"><input type="checkbox" name="reg_id[]" tabindex="-1" value="' . $reg->id . '">
                                    <span class="checkmark"></span></label>
                                </div>
                            </td>
                            ' : '<td>&nbsp;</td>').'
                            <td>' . $reg->id . '</td>
                            <td class="status ' . $class . '"><span class="material-icons ' . $class . '">' . $icon . '</span> ' . $status . '</td>
                            <td class="link"><a href="/plans/?plan=' . $reg->id . '">' . $reg->short . $reg->doc_number.'</a></td>
                            <td>' . $reg->year . '</td>
                            <td>' . $reg->version . '</td>
                            <td class="link" style="justify-content: right">';
                    if($reg->active != 1 && $reg->approved != 1){
                        if($_SESSION['user_id'] == $reg->author || $auth->isAdmin()){
                            echo '<span class="material-icons reg_settings" title="Редактирование плана">edit</span>';
                        }
                    }else{
                        echo '<span class="material-icons reg_settings" title="Редактирование плана">edit</span>';
                    }
                        echo '
                            <!--span class="material-icons" title="Печать">print</span-->
                            <span class="material-icons viewDoc" data-value="' . $reg->id . '" data-type="3" title="Просмотр документа">picture_as_pdf</span>
                            <!--span class="material-icons" title="Расписание">edit_calendar</span-->
                            <span class="material-icons" title="Просмотр плана"><a href="/plans/?plan=' . $reg->id . '">pageview</a></span>
                            </td>
                        </tr>';
                }
                ?>
                </tbody>
            </table>
        </form>
        <?php
        echo $gui->paging();
        ?>
    </div>
    <script src="/modules/plans/js/registry.js?v=<?= $gui->genpass() ?>"></script>
    <?php
}
//[[{'stage': '1', 'urgent': '1', 'list_type': '2'}, {'id': '1', 'type': '2', 'vrio': '0', 'result': {'id': '3', 'date': '16.02.2026 19:49'}, 'urgent': '1'}, {'id': '2', 'type': '2', 'vrio': '0', 'urgent': '1'}], [{'stage': '', 'urgent': '1', 'list_type': '1'}, {'id': '2', 'role': '0', 'type': '1', 'vrio': '0', 'urgent': '1'}, {'id': '1', 'role': '1', 'type': '1', 'vrio': '0', 'urgent': '1'}]]
?>
