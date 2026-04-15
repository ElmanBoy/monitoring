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
    $_ministryFilter = '';
    $_activeMinistries = $auth->getActiveMinistries();
    if (!empty($_activeMinistries)) {
        $ids = implode(',', $_activeMinistries);
        $_ministryFilter = ' AND (ministry_id IN (' . $ids . ') OR ministry_id IS NULL)';
    }
    $regs = $gui->getTableData('checksplans', $_ministryFilter);

    // Загружаем даты утверждения планов из cam_agreement
    $db = new \Core\Db();
    $date = new \Core\Date();
    $planDates = [];
    $agreementDates = $db->select('agreement',
        " WHERE source_table = 'checksplans' AND status = 1"
    );
    foreach ($agreementDates as $agr) {
        $planDates[intval($agr->source_id)]['approved'] = $agr->docdate;
    }
    ?>
    <div class="nav">
        <div class="nav_01">
            <?
            $navItems = [
                'title'           => 'Планы проверок',
                'renew'           => 'Сбросить все фильтры',
                'create'          => 'Новый план',
                'ministry_filter' => 'Фильтр по управлению',
            ];
            if ($auth->isAdmin()) {
                $navItems['archive'] = 'Переместить в архив';
                $navItems['delete']  = 'Удалить безвозвратно';
            } else {
                $navItems['archive'] = 'Переместить в архив';
            }
            $navItems['logout'] = 'Выйти';
            echo $gui->buildTopNav($navItems);
            ?>
        </div>

    </div>
    <div class="scroll_wrap">
        <ul class='breadcrumb'>
            <li><a href='/plans'>Все планы</a></li>
        </ul>
        <form method="post" id="registry_archive" class="ajaxFrm"></form>
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
                            'checksplans',
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
                            'checksplans',
                            'Статус',
                            'active',
                            'constant',
                            ['0' => 'На рассмотрении', '1' => 'Утверждён', '2' => 'Отклонён']
                        );
                        ?>
                    </th>
                    <th class="sort" style='width: 40%'>
                        <?
                        echo $gui->buildSortFilter(
                            'checksplans',
                            'Наименование',
                            'short',
                            'el_data',
                            []
                        );
                        ?>
                    </th>
                    <th class='sort'>
                        <?
                        echo $gui->buildSortFilter(
                            'checksplans',
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
                            'checksplans',
                            'Версия',
                            'version',
                            'el_data',
                            []
                        );
                        ?>
                    </th>
                    <th class='sort'>
                        <?
                        echo $gui->buildSortFilter(
                            'checksplans',
                            'Дата создания',
                            'created_at',
                            'el_data',
                            []
                        );
                        ?>
                    </th>
                    <th style="white-space: nowrap;">Дата утверждения</th>
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
                            <td>' . (strlen($reg->created_at) > 0 ? $date->dateToString(explode(' ', $reg->created_at)[0]) : '—') . '</td>
                            <td>' . (isset($planDates[$reg->id]['approved']) && strlen($planDates[$reg->id]['approved']) > 0 ? $date->dateToString($planDates[$reg->id]['approved']) : '—') . '</td>
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