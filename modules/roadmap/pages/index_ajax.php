<?php

use Core\Gui;
use Core\Db;
use Core\Auth;
use Core\Date;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$regId = 66;

$gui = new Gui;
$db = new Db;
$auth = new Auth();
$date = new Date();

$is_object = $auth->haveUserRole(5);

$gui->set('module_id', 19);

$table = $db->selectOne('registry', ' where id = ?', [$regId]);
$items = $db->getRegistry($table->table_name);

// Для ОК (role=5) показываем только документы его учреждения.
// ins_id в agreement хранит ID учреждения объекта контроля.
// source_id для графиков (documentacial=5) ссылается на акт, поэтому
// для них фильтруем через подзапрос по связанному акту.
$insFilter = '';
if ($is_object) {
    $userInsId = intval($_SESSION['user_institution']);
    if ($userInsId > 0) {
        // Акты: ins_id = учреждение напрямую
        // Графики: source_id = id акта, у которого ins_id = учреждение
        $insFilter = " AND (
            (documentacial = 2 AND ins_id = {$userInsId})
            OR
            (documentacial = 5 AND source_id IN (
                SELECT id FROM " . TBL_PREFIX . "agreement
                WHERE documentacial = 2 AND ins_id = {$userInsId}
            ))
        )";
    } else {
        // Учреждение не определено — ничего не показываем
        $insFilter = ' AND 1 = 0';
    }
}

$regs = $gui->getTableData($table->table_name, ' AND (documentacial = 2 OR documentacial = 5)' . $insFilter);

// Для каждого документа считаем прогресс устранения из agreementlist
// Акт (documentacial=2) — смотрим есть ли связанный график (documentacial=5 с тем же source_id)
// График (documentacial=5) — читаем agreementlist и считаем строки по fix_status
$roadProgress = [];
$roadIds = [];  // act_id => road_id

foreach ($regs as $r) {
    $r = (object)$r;
    if (intval($r->documentacial) === 5) {
        $schedule = json_decode($r->agreementlist ?? '[]', true) ?: [];
        $total = count($schedule);
        $done = count(array_filter($schedule, fn($s) => intval($s['fix_status'] ?? 0) === 2));
        $onCheck = count(array_filter($schedule, fn($s) => intval($s['fix_status'] ?? 0) === 1));
        $overdue = 0;
        foreach ($schedule as $s) {
            $dl = $s['deadline_extended'] ?? $s['schedule_deadlines'] ?? null;
            if ($dl && strtotime($dl) < time() && intval($s['fix_status'] ?? 0) < 2) {
                $overdue++;
            }
        }
        $roadProgress[$r->id] = compact('total', 'done', 'onCheck', 'overdue');
        // Сопоставляем с актом по source_id / header
        // График хранит source_id акта в поле source_id
        if (!empty($r->source_id)) {
            $roadIds[intval($r->source_id)] = $r->id;
        }
    }
}
?>

<div class="nav">
    <div class="nav_01">
        <?
        echo $gui->buildTopNav([
            'title' => 'Акты и Графики устранения нарушений',
            'renew' => 'Сбросить все фильтры',
            'filter_panel' => 'Открыть панель фильтров',
            'logout' => 'Выйти'
        ]
        );
        ?>
    </div>
</div>

<div class="scroll_wrap">
    <form method="post" id="registry_items_delete" class="ajaxFrm">
        <input type="hidden" name="registry_id" id="registry_id" value="<?= $regId ?>">
        <table class="table_data" id="tbl_registry_items">
            <thead>
            <tr class="fixed_thead">
                <th>
                    <div class="custom_checkbox">
                        <label class="container" title="Выделить все">
                            <input type="checkbox" id="check_all"><span class="checkmark"></span>
                        </label>
                    </div>
                </th>
                <th class="sort">
                    <?= $gui->buildSortFilter('agreement', '№', 'id', 'el_data', []) ?>
                </th>
                <th class="sort">
                    <?= $gui->buildSortFilter('agreement', 'Статус', 'status', 'constant',
                        ['1' => 'Согласован', '0' => 'На согласовании']
                    ) ?>
                </th>
                <th class="sort">
                    <?= $gui->buildSortFilter('agreement', 'Тип', 'documentacial', 'constant',
                        ['2' => 'Акт', '5' => 'График устранения']
                    ) ?>
                </th>
                <th class="sort">
                    <?= $gui->buildSortFilter('agreement', 'Дата', 'docdate', 'el_data', [], 'suggest', 'date') ?>
                </th>
                <th class="sort">
                    <?= $gui->buildSortFilter('agreement', 'Наименование', 'name', 'el_data', []) ?>
                </th>
                <th>
                    <div class="head_sort_filter">Устранение</div>
                </th>
                <th>
                    <div class="head_sort_filter">Действия</div>
                </th>
            </tr>
            </thead>

            <tbody>
            <?php
            foreach ($regs as $reg) {
                if ($regId == 14 && ($auth->haveUserRole(3) || $auth->haveUserRole(1))) {
                    $reg = (object)$reg;
                }

                if ($reg->status == 1) {
                    $icon = 'task_alt';
                    $status = 'Согласован';
                    $class = 'green';
                } else {
                    $icon = 'radio_button_unchecked';
                    $status = 'На согласовании';
                    $class = 'grey';
                }

                // Прогресс устранения
                $progressHtml = '—';
                $rowStyle = '';
                $roadId = null;

                if (intval($reg->documentacial) === 5 && isset($roadProgress[$reg->id])) {
                    $p = $roadProgress[$reg->id];
                    $roadId = $reg->id;
                    $barColor = $p['overdue'] > 0 ? '#c62828' : ($p['done'] === $p['total'] && $p['total'] > 0 ? '#2e7d32' : '#1565c0');
                    $rowStyle = $p['overdue'] > 0 ? ' style="background:rgba(198,40,40,0.04)"' : '';
                    $pct = $p['total'] > 0 ? round($p['done'] / $p['total'] * 100) : 0;

                    $progressHtml = '
                        <div style="font-size:12px; white-space:nowrap">
                            ' . $p['done'] . '/' . $p['total'] . ' устранено'
                        . ($p['onCheck'] > 0 ? ' · <span style="color:#1565c0">' . $p['onCheck'] . ' на пров.</span>' : '')
                        . ($p['overdue'] > 0 ? ' · <span style="color:#c62828">' . $p['overdue'] . ' просроч.</span>' : '') . '
                        </div>
                        <div style="height:4px; background:var(--color_06); border-radius:2px; margin-top:4px; width:100px">
                            <div style="height:4px; border-radius:2px; background:' . $barColor . '; width:' . $pct . '%"></div>
                        </div>';

                } elseif (intval($reg->documentacial) === 2 && isset($roadIds[$reg->id])) {
                    // Для акта — показываем прогресс связанного графика
                    $roadId = $roadIds[$reg->id];
                    if (isset($roadProgress[$roadId])) {
                        $p = $roadProgress[$roadId];
                        $barColor = $p['overdue'] > 0 ? '#c62828' : ($p['done'] === $p['total'] && $p['total'] > 0 ? '#2e7d32' : '#1565c0');
                        $rowStyle = $p['overdue'] > 0 ? ' style="background:rgba(198,40,40,0.04)"' : '';
                        $pct = $p['total'] > 0 ? round($p['done'] / $p['total'] * 100) : 0;

                        $progressHtml = '
                            <div style="font-size:12px; white-space:nowrap">
                                ' . $p['done'] . '/' . $p['total'] . ' устранено'
                            . ($p['onCheck'] > 0 ? ' · <span style="color:#1565c0">' . $p['onCheck'] . ' на пров.</span>' : '')
                            . ($p['overdue'] > 0 ? ' · <span style="color:#c62828">' . $p['overdue'] . ' просроч.</span>' : '') . '
                            </div>
                            <div style="height:4px; background:var(--color_06); border-radius:2px; margin-top:4px; width:100px">
                                <div style="height:4px; border-radius:2px; background:' . $barColor . '; width:' . $pct . '%"></div>
                            </div>';
                    }
                }

                echo '<tr data-id="' . $reg->id . '" data-parent="' . $regId . '" tabindex="0"' . $rowStyle . '>
                    <td>
                        <div class="custom_checkbox">
                            <label class="container">
                                <input type="checkbox" name="reg_id[]" tabindex="-1" value="' . $reg->id . '">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                    </td>
                    <td>' . $reg->id . '</td>
                    <td class="status ' . $class . '">
                        <span class="material-icons ' . $class . '">' . $icon . '</span> ' . $status . '
                    </td>
                    <td>' . (intval($reg->documentacial) === 2 ? 'Акт' : 'График устранения') . '</td>
                    <td style="white-space:nowrap">' .
                    (strlen($reg->docdate ?? '') > 0
                        ? $date->correctDateFormatFromMysql($reg->docdate)
                        : '<span style="color:var(--color_04);font-size:12px">не подписан</span>') .
                    '</td>
                    <td class="group">' . stripslashes($reg->name) . '</td>
                    <td>' . $progressHtml . '</td>
                    <td class="link">';

                // Кнопка создать/открыть график — только для актов у которых ОК ознакомился
                if (intval($reg->documentacial) === 2) {
                    if (intval($reg->act_agree) > 0 && !isset($roadIds[$reg->id])) {
                        // Акт подписан, графика ещё нет — показываем создать
                        echo '<span class="material-icons addRoad"
                                data-id="' . $reg->id . '"
                                data-ins="' . $reg->source_id . '"
                                title="Создать график устранения нарушений">add_road</span> ';
                    } elseif (isset($roadIds[$reg->id])) {
                        // График уже есть — открыть просмотр
                        echo '<span class="material-icons viewRoad"
                                data-id="' . $roadIds[$reg->id] . '"
                                title="Открыть график устранения нарушений"
                                style="color:var(--color_03)">rule</span> ';
                    }
                } elseif (intval($reg->documentacial) === 5) {
                    // Строка самого графика — открыть
                    echo '<span class="material-icons viewRoad"
                            data-id="' . $reg->id . '"
                            title="Открыть график устранения нарушений"
                            style="color:var(--color_03)">rule</span> ';
                }

                if ($is_object) {
                    echo '<span class="material-icons agreementDoc"
                            data-id="' . $reg->id . '"
                            title="Направить возражения">call_missed</span> ';
                } else {
                    echo '<span class="material-icons agreementDoc"
                            data-id="' . $reg->id . '"
                            title="Просмотр переписки">pageview</span> ';
                }

                echo '<span class="material-icons viewDoc"
                        data-id="' . $reg->id . '"
                        title="Ознакомление с актом">picture_as_pdf</span>';

                echo '</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </form>
    <?= $gui->paging() ?>
</div>

<script src="/js/assets/agreement_list.js"></script>
<script src="/modules/roadmap/js/registry_items.js?v=<?= $gui->genpass() ?>"></script>
<script>
    $('.agreementDoc').off('click').on('click', function () {
        let taskId = $(this).data('id');
        el_app.dialog_open('agreement', {docId: taskId}, 'roadmap');
    });
    $('.viewDoc').off('click').on('click', function () {
        let taskId = $(this).data('id');
        el_app.dialog_open('pdf', {docId: taskId, is_inst: true}, 'roadmap');
    });
    <?php
    $open_dialog = 0;
    if (isset($_POST['params'])) {
        $postArr = explode('=', $_POST['params']);
        if ($postArr[0] == 'open_dialog') {
            $open_dialog = intval($postArr[1]);
        }
    } elseif (isset($_GET['open_dialog']) && intval($_GET['open_dialog']) > 0) {
        $open_dialog = intval($_GET['open_dialog']);
    }
    if ($open_dialog > 0) {
        echo 'el_app.dialog_open("agreement", {"docId": ' . $open_dialog . ', "taskId": ' . $open_dialog . '}, "roadmap");';
    }
    ?>
</script>