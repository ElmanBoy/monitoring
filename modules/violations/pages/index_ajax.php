<?php
/**
 * modules/violations/pages/index_ajax.php
 *
 * Реестр нарушений — сводный список всех строк из графиков устранения (documentacial=5).
 * Каждая строка agreementlist отображается отдельной записью в таблице.
 *
 * Роли:
 *   Все авторизованные — просмотр.
 *   ОК (role=5) — видит только своё учреждение.
 *   Фильтр по министерству применяется для администраторов как в roadmap.
 */

use Core\Gui;
use Core\Db;
use Core\Auth;
use Core\Date;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$gui  = new Gui();
$db   = new Db();
$auth = new Auth();
$date = new Date();

$module_id = 22;
$gui->set('module_id', $module_id);

$is_object = $auth->haveUserRole(5);

// Фильтр по учреждению для ОК
$insFilter = '';
if ($is_object) {
    $userInsId = intval($_SESSION['user_institution'] ?? 0);
    if ($userInsId > 0) {
        $insFilter = " AND a.ins_id = {$userInsId}";
    } else {
        $insFilter = ' AND 1 = 0';
    }
}

// Фильтр по управлению (министерству) для не-ОК
// ministry_id хранится в акте (documentacial=2), у графика берём через JOIN act.ministry_id
$ministryFilter = '';
if (!$is_object) {
    $_activeMinistries = $auth->getActiveMinistries();
    if (!empty($_activeMinistries)) {
        $ids = implode(',', array_map('intval', $_activeMinistries));
        $ministryFilter = ' AND (act.ministry_id IN (' . $ids . ') OR act.ministry_id IS NULL OR a.ministry_id IN (' . $ids . '))';
    }
}

// Фильтр по статусу устранения из params (формат "filter_fix_status=N")
$filterStatus = -1;
if (!empty($_POST['params'])) {
    parse_str($_POST['params'], $paramsArr);
    if (isset($paramsArr['filter_fix_status'])) {
        $filterStatus = intval($paramsArr['filter_fix_status']);
    }
}
// -1 = все, 0 = не устранено, 1 = на проверке, 2 = снято, 4 = просрочено

// Загружаем все графики (documentacial=5)
$roads = $db->db::getAll(
    'SELECT a.id, a.ins_id, a.source_id, a.ministry_id, a.agreementlist,
            a.name AS road_name, a.created_at,
            act.doc_number AS act_number, act.docdate AS act_date, act.name AS act_name
     FROM ' . TBL_PREFIX . 'agreement a
     LEFT JOIN ' . TBL_PREFIX . 'agreement act ON act.id = a.source_id AND act.documentacial = 2
     WHERE a.documentacial = 5 AND a.active = 1' . $insFilter . $ministryFilter . '
     ORDER BY a.id DESC'
);

// Загружаем учреждения для отображения
$institutions = $db->getRegistry('institutions', '', [], ['short', 'name']);

// Метки статусов
$fixStatusLabels = [
    0 => ['text' => 'Не устранено',  'color' => '#757575', 'icon' => 'radio_button_unchecked'],
    1 => ['text' => 'На проверке',   'color' => '#1565c0', 'icon' => 'pending'],
    2 => ['text' => 'Снято',         'color' => '#2e7d32', 'icon' => 'check_circle'],
    3 => ['text' => 'Возврат',       'color' => '#e65100', 'icon' => 'replay'],
    4 => ['text' => 'Просрочено',    'color' => '#c62828', 'icon' => 'alarm_off'],
];

// Раскрываем строки графиков в плоский список
// Сначала — все строки (для счётчиков сводки), затем фильтруем для отображения
$allRows = [];
$now     = time();
foreach ($roads as $road) {
    $schedule = json_decode($road['agreementlist'] ?? '[]', true) ?: [];
    $insId = intval($road['ins_id']);
    $insName = '';
    if ($insId > 0 && isset($institutions['array'][$insId])) {
        $insName = $institutions['array'][$insId][0] ?: $institutions['array'][$insId][1] ?: '';
    }

    foreach ($schedule as $idx => $item) {
        $fixSt = intval($item['fix_status'] ?? 0);
        $dl    = $item['deadline_extended'] ?? $item['schedule_deadlines'] ?? null;
        $isOverdue = $dl && strtotime($dl) < $now && $fixSt < 2;

        $allRows[] = [
            'road_id'       => $road['id'],
            'row_idx'       => $idx,
            'ins_id'        => $insId,
            'ins_name'      => $insName,
            'act_number'    => $road['act_number'],
            'act_date'      => $road['act_date'],
            'act_name'      => $road['act_name'],
            'offer'         => $item['schedule_offers'] ?? '',
            'actions'       => $item['schedule_actions'] ?? '',
            'deadline'      => $dl,
            'responsible'   => $item['schedule_responsible'] ?? '',
            'fix_status'    => $fixSt,
            'display_status'=> $isOverdue && $fixSt < 2 ? 4 : $fixSt,
            'is_overdue'    => $isOverdue,
            'fix_comment'   => $item['fix_comment'] ?? '',
            'check_comment' => $item['check_comment'] ?? '',
        ];
    }
}

// Глобальные счётчики для сводной панели (всегда по всем данным, не зависят от фильтра)
$totalRows    = count($allRows);
$totalDone    = count(array_filter($allRows, fn($r) => $r['fix_status'] === 2));
$totalCheck   = count(array_filter($allRows, fn($r) => $r['fix_status'] === 1));
$totalOverdue = count(array_filter($allRows, fn($r) => $r['is_overdue']));
$totalOpen    = $totalRows - $totalDone;

// Применяем фильтр статуса для отображаемых строк
$rows = $allRows;
if ($filterStatus !== -1) {
    $rows = array_values(array_filter($allRows, function ($r) use ($filterStatus) {
        if ($filterStatus === 4) {
            return $r['is_overdue'];
        }
        return $r['display_status'] === $filterStatus;
    }));
}
?>

<div class="nav">
    <div class="nav_01">
        <?php
        echo $gui->buildTopNav([
            'title'           => 'Реестр нарушений',
            'renew'           => 'Сбросить все фильтры',
            'ministry_filter' => 'Фильтр по управлению',
            'logout'          => 'Выйти',
        ]);
        ?>
    </div>
</div>

<div class="scroll_wrap">

    <?php if ($totalRows > 0): ?>
        <div class="violations-summary">
            <div class="violations-summary__item">
                <span class="material-icons">rule</span>
                Всего: <strong><?= $totalRows ?></strong>
            </div>
            <div class="violations-summary__item violations-summary__item--open">
                <span class="material-icons">pending_actions</span>
                Открытых: <strong><?= $totalOpen ?></strong>
            </div>
            <div class="violations-summary__item violations-summary__item--check">
                <span class="material-icons">pending</span>
                На проверке: <strong><?= $totalCheck ?></strong>
            </div>
            <?php if ($totalOverdue > 0): ?>
                <div class="violations-summary__item violations-summary__item--overdue">
                    <span class="material-icons">alarm_off</span>
                    Просрочено: <strong><?= $totalOverdue ?></strong>
                </div>
            <?php endif; ?>
            <div class="violations-summary__item violations-summary__item--done">
                <span class="material-icons">check_circle</span>
                Устранено: <strong><?= $totalDone ?></strong>
            </div>
        </div>
    <?php endif; ?>

    <div class="violations-filter-bar">
        <span class="violations-filter-bar__label">Статус:</span>
        <button type="button" class="button violations-filter__btn<?= $filterStatus === -1 ? ' active' : '' ?>" data-status="-1">Все</button>
        <button type="button" class="button violations-filter__btn<?= $filterStatus === 0  ? ' active' : '' ?>" data-status="0">Не устранено</button>
        <button type="button" class="button violations-filter__btn<?= $filterStatus === 1  ? ' active' : '' ?>" data-status="1">На проверке</button>
        <button type="button" class="button violations-filter__btn<?= $filterStatus === 4  ? ' active' : '' ?>" data-status="4">Просрочено</button>
        <button type="button" class="button violations-filter__btn<?= $filterStatus === 2  ? ' active' : '' ?>" data-status="2">Устранено</button>
    </div>

    <table class="table_data" id="tbl_violations">
        <thead>
        <tr class="fixed_thead">
            <th style="width:32px">#</th>
            <th>Учреждение</th>
            <th>Акт / Документ</th>
            <th>Нарушение (предложение)</th>
            <th>Действия по устранению</th>
            <th style="width:100px">Срок</th>
            <th style="width:160px">Ответственный</th>
            <th style="width:130px">Статус</th>
            <th style="width:100px">Действия</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr>
                <td colspan="9" style="text-align:center; padding:40px; color:var(--color_04)">
                    <span class="material-icons" style="font-size:48px; display:block; margin-bottom:8px">rule_folder</span>
                    Нарушений не найдено
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($rows as $num => $row):
                $badge   = $fixStatusLabels[$row['display_status']] ?? $fixStatusLabels[0];
                $rowStyle = $row['is_overdue'] ? ' style="background:rgba(198,40,40,0.04)"' : '';
                ?>
                <tr data-road-id="<?= $row['road_id'] ?>" data-row-idx="<?= $row['row_idx'] ?>"<?= $rowStyle ?>>
                    <td style="color:var(--color_04); font-size:11px"><?= $num + 1 ?></td>
                    <td style="font-size:13px"><?= htmlspecialchars($row['ins_name'] ?: '—') ?></td>
                    <td style="font-size:12px; white-space:nowrap">
                        <?php if (!empty($row['act_number'])): ?>
                            №&nbsp;<?= htmlspecialchars($row['act_number']) ?>
                        <?php endif; ?>
                        <?php if (!empty($row['act_date'])): ?>
                            <br><span style="color:var(--color_04)"><?= $date->correctDateFormatFromMysql($row['act_date']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:13px; max-width:260px"><?= htmlspecialchars($row['offer'] ?: '—') ?></td>
                    <td style="font-size:12px; color:var(--color_04); max-width:220px"><?= htmlspecialchars($row['actions'] ?: '—') ?></td>
                    <td style="white-space:nowrap; font-size:13px">
                        <?php if ($row['deadline']): ?>
                            <span<?= $row['is_overdue'] ? ' style="color:#c62828; font-weight:600"' : '' ?>>
                                <?= date('d.m.Y', strtotime($row['deadline'])) ?>
                            </span>
                            <?php if ($row['is_overdue']): ?>
                                <br><small style="color:#c62828">Срок истёк</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:var(--color_04)">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px"><?= htmlspecialchars($row['responsible'] ?: '—') ?></td>
                    <td>
                        <span class="violations-status-badge" style="color:<?= $badge['color'] ?>">
                            <span class="material-icons" style="font-size:14px; vertical-align:middle"><?= $badge['icon'] ?></span>
                            <?= $badge['text'] ?>
                        </span>
                        <?php if ($row['fix_status'] === 3 && !empty($row['check_comment'])): ?>
                            <div style="font-size:11px; color:#c62828; margin-top:3px">
                                <?= htmlspecialchars(mb_strimwidth($row['check_comment'], 0, 60, '…')) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="link">
                        <span class="material-icons btn-open-road"
                              data-road-id="<?= $row['road_id'] ?>"
                              title="Открыть график устранения"
                              style="cursor:pointer; color:var(--color_04)">rule</span>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
    .violations-summary {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        padding: 0.75rem 1.25rem;
        background: var(--color_07);
        border-bottom: 1px solid var(--color_06);
        font-size: 13px;
    }
    .violations-summary__item {
        display: flex;
        align-items: center;
        gap: 4px;
        color: var(--color_04);
    }
    .violations-summary__item .material-icons { font-size: 16px; }
    .violations-summary__item strong { color: var(--color_01); }
    .violations-summary__item--overdue { color: #c62828; }
    .violations-summary__item--overdue strong { color: #c62828; }
    .violations-summary__item--done { color: #2e7d32; }
    .violations-summary__item--done strong { color: #2e7d32; }
    .violations-summary__item--check { color: #1565c0; }
    .violations-summary__item--check strong { color: #1565c0; }

    .violations-filter-bar {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1.25rem;
        border-bottom: 1px solid var(--color_06);
    }
    .violations-filter-bar__label {
        font-size: 13px;
        color: var(--color_04);
        margin-right: 0.25rem;
    }
    .violations-filter__btn {
        font-size: 12px;
        padding: 3px 10px;
        min-height: 28px;
        border-radius: 14px;
    }
    .violations-filter__btn.active {
        background: var(--color_04);
        color: #fff;
    }

    .violations-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
    }

    .btn-open-road:hover { color: var(--color_04_hover) !important; }

    #tbl_violations td { vertical-align: top; }
</style>

<script src="/modules/violations/js/registry.js?v=<?= $gui->genpass() ?>"></script>
