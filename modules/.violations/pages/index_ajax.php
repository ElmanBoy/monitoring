<?php

use Core\Gui;
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$gui = new Gui();
$db = new Db();
$auth = new Auth();

$gui->set('module_id', 20);

// Только сотрудники министерства — для ОК этот раздел недоступен
$currentUser = $db->selectOne('users', ' WHERE id = ?', [$_SESSION['user_id']]);
$userRoles = json_decode($currentUser->roles ?? '[]', true) ?: [];
$isOK = in_array(5, $userRoles);

if ($isOK) {
    echo '<div style="padding:40px; text-align:center; color:var(--color_04)">Раздел доступен только сотрудникам министерства</div>';
    exit;
}

// ── Фильтры из GET/POST ────────────────────────────────────────────────────
$params = [];
if (isset($_POST['params'])) {
    parse_str($_POST['params'], $params);
}

$filterScheduleStatus = intval($params['schedule_status'] ?? $_GET['schedule_status'] ?? -1);
$filterFixStatus = intval($params['fix_status'] ?? $_GET['fix_status'] ?? -1);
$filterInstitution = intval($params['institution'] ?? $_GET['institution'] ?? 0);
$filterOverdue = intval($params['overdue'] ?? $_GET['overdue'] ?? 0);

// ── Запрос нарушений ────────────────────────────────────────────────────────
$where = ['1=1'];
$binds = [];

if ($filterScheduleStatus >= 0) {
    $where[] = 'cv.schedule_status = ?';
    $binds[] = $filterScheduleStatus;
}
if ($filterFixStatus >= 0) {
    $where[] = 'cv.fix_status = ?';
    $binds[] = $filterFixStatus;
}
if ($filterInstitution > 0) {
    $where[] = 'cs.institution = ?';
    $binds[] = $filterInstitution;
}
if ($filterOverdue === 1) {
    $where[] = 'COALESCE(cv.deadline_extended, cv.deadline) < CURRENT_DATE AND cv.fix_status < 2';
}

$whereStr = implode(' AND ', $where);

$violations = $db->db::getAll('
    SELECT
        cv.id,
        cv.name            AS violation_name,
        cv.violations      AS violation_type_id,
        cv.responsible,
        cv.deadline,
        cv.deadline_extended,
        cv.schedule_status,
        cv.fix_status,
        cv.schedule_comment,
        cv.check_comment,
        cv.fix_files,
        vt.name            AS violation_type_name,
        ins.name           AS institution_name,
        ins.short          AS institution_short,
        agr.id             AS agreement_id,
        agr.doc_number     AS act_number,
        agr.docdate        AS act_date
    FROM ' . TBL_PREFIX . 'checksviolations cv
    JOIN ' . TBL_PREFIX . 'checkstaff cs  ON cs.id  = cv.tasks
    JOIN ' . TBL_PREFIX . 'institutions ins ON ins.id = cs.institution
    LEFT JOIN ' . TBL_PREFIX . 'checks vt ON vt.id = cv.violations
    LEFT JOIN ' . TBL_PREFIX . "agreement agr
        ON agr.documentacial = 2
       AND agr.ins_id = cs.institution
    WHERE $whereStr
    ORDER BY
        CASE cv.schedule_status WHEN 1 THEN 0 WHEN 3 THEN 1 ELSE 2 END,
        COALESCE(cv.deadline_extended, cv.deadline) ASC NULLS LAST,
        cv.id DESC
", $binds
);

// ── Справочники для фильтров ────────────────────────────────────────────────
$institutions = $db->getRegistry('institutions', '', [], ['name']);

// ── Счётчики по статусам ───────────────────────────────────────────────────
$cntWaiting = count(array_filter($violations, fn($v) => intval($v['schedule_status']) === 1));
$cntRejected = count(array_filter($violations, fn($v) => intval($v['schedule_status']) === 3));
$cntOnCheck = count(array_filter($violations, fn($v) => intval($v['fix_status']) === 1));
$cntOverdue = count(array_filter($violations, function ($v) {
    $dl = $v['deadline_extended'] ?: $v['deadline'];
    return $dl && strtotime($dl) < time() && intval($v['fix_status']) < 2;
}
)
);

// ── Метки статусов ──────────────────────────────────────────────────────────
$fixStatusLabels = [
    0 => ['text' => 'Не устранено', 'color' => '#999'],
    1 => ['text' => 'На проверке', 'color' => '#1565c0'],
    2 => ['text' => 'Снято', 'color' => '#2e7d32'],
    3 => ['text' => 'Возврат', 'color' => '#e65100'],
    4 => ['text' => 'Просрочено', 'color' => '#c62828'],
];
$scheduleStatusLabels = [
    0 => ['text' => 'Ожидает заполнения', 'color' => '#999'],
    1 => ['text' => 'Ожидает утверждения', 'color' => '#1565c0'],
    2 => ['text' => 'Утверждён', 'color' => '#2e7d32'],
    3 => ['text' => 'Отклонён', 'color' => '#c62828'],
];
?>

<div class="nav">
    <div class="nav_01">
        <?php
        echo $gui->buildTopNav([
            'title' => 'Устранение нарушений',
            'renew' => 'Сбросить все фильтры',
            'logout' => 'Выйти'
        ]
        );
        ?>
    </div>
</div>

<div class="scroll_wrap">

    <!-- Счётчики-фильтры -->
    <div style="display:flex; gap:10px; padding:14px 0 10px; flex-wrap:wrap;">

        <a href="/violations"
           class="button icon text<?= ($filterScheduleStatus < 0 && $filterFixStatus < 0 && !$filterOverdue) ? ' active' : '' ?>">
            <span class="material-icons">list</span>Все нарушения
            <span style="background:var(--color_06); border-radius:10px; padding:1px 7px; font-size:11px; margin-left:4px">
                <?= count($violations) ?>
            </span>
        </a>

        <?php if ($cntWaiting > 0): ?>
            <a href="/violations?schedule_status=1"
               class="button icon text<?= $filterScheduleStatus === 1 ? ' active' : '' ?>">
                <span class="material-icons">hourglass_empty</span>Ждут утверждения
                <span style="background:#e3f0fb; color:#1565c0; border-radius:10px; padding:1px 7px; font-size:11px; margin-left:4px">
                <?= $cntWaiting ?>
            </span>
            </a>
        <?php endif; ?>

        <?php if ($cntOnCheck > 0): ?>
            <a href="/violations?fix_status=1" class="button icon text<?= $filterFixStatus === 1 ? ' active' : '' ?>">
                <span class="material-icons">rate_review</span>На проверке
                <span style="background:#e3f0fb; color:#1565c0; border-radius:10px; padding:1px 7px; font-size:11px; margin-left:4px">
                <?= $cntOnCheck ?>
            </span>
            </a>
        <?php endif; ?>

        <?php if ($cntRejected > 0): ?>
            <a href="/violations?schedule_status=3"
               class="button icon text<?= $filterScheduleStatus === 3 ? ' active' : '' ?>">
                <span class="material-icons">cancel</span>Отклонённые графики
                <span style="background:#fdecea; color:#c62828; border-radius:10px; padding:1px 7px; font-size:11px; margin-left:4px">
                <?= $cntRejected ?>
            </span>
            </a>
        <?php endif; ?>

        <?php if ($cntOverdue > 0): ?>
            <a href="/violations?overdue=1" class="button icon text<?= $filterOverdue ? ' active' : '' ?>">
                <span class="material-icons">alarm_off</span>Просроченные
                <span style="background:#fdecea; color:#c62828; border-radius:10px; padding:1px 7px; font-size:11px; margin-left:4px">
                <?= $cntOverdue ?>
            </span>
            </a>
        <?php endif; ?>

        <!-- Фильтр по учреждению -->
        <div style="margin-left:auto;">
            <select id="filter_institution" class="el_input" style="min-width:220px; font-size:13px;">
                <option value="0">Все учреждения</option>
                <?php foreach ($institutions['result'] as $ins): ?>
                    <option value="<?= $ins->id ?>"
                        <?= $filterInstitution === intval($ins->id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ins->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Таблица -->
    <table class="table_data" id="tbl_violations">
        <thead>
        <tr class="fixed_thead">
            <th style="width:28px">#</th>
            <th>Учреждение</th>
            <th>Нарушение</th>
            <th>Акт</th>
            <th style="width:130px">Ответственный</th>
            <th style="width:110px">Срок</th>
            <th style="width:130px">График</th>
            <th style="width:120px">Устранение</th>
            <th style="width:80px"></th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($violations)): ?>
            <tr>
                <td colspan="9" style="text-align:center; padding:30px; color:var(--color_04);">
                    Нарушений не найдено
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($violations as $i => $v):
                $schedSt = intval($v['schedule_status']);
                $fixSt = intval($v['fix_status']);
                $deadline = $v['deadline_extended'] ?: $v['deadline'];
                $isOverdue = $deadline && strtotime($deadline) < time() && $fixSt < 2;
                $sBadge = $scheduleStatusLabels[$schedSt] ?? $scheduleStatusLabels[0];
                $fBadge = $fixStatusLabels[$fixSt] ?? $fixStatusLabels[0];
                ?>
                <tr data-id="<?= $v['id'] ?>"
                    data-agreement="<?= intval($v['agreement_id']) ?>"
                    style="<?= $isOverdue ? 'background:rgba(198,40,40,0.04)' : '' ?>">

                    <td style="color:var(--color_04); font-size:11px"><?= $i + 1 ?></td>

                    <td>
                        <div style="font-size:13px"><?= htmlspecialchars($v['institution_short'] ?: $v['institution_name']) ?></div>
                    </td>

                    <td style="max-width:300px">
                        <div style="font-size:13px"><?= htmlspecialchars($v['violation_name']) ?></div>
                        <?php if (!empty($v['violation_type_name'])): ?>
                            <div style="font-size:11px; color:var(--color_04)"><?= htmlspecialchars($v['violation_type_name']) ?></div>
                        <?php endif; ?>
                    </td>

                    <td style="white-space:nowrap; font-size:12px">
                        <?php if ($v['act_number']): ?>
                            <?= htmlspecialchars($v['act_number']) ?>
                            <?php if ($v['act_date']): ?>
                                <br><span
                                        style="color:var(--color_04)"><?= date('d.m.Y', strtotime($v['act_date'])) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>

                    <td style="font-size:13px"><?= htmlspecialchars($v['responsible'] ?? '—') ?></td>

                    <td style="font-size:13px; white-space:nowrap">
                        <?php if ($deadline): ?>
                            <span <?= $isOverdue ? 'style="color:#c62828; font-weight:600"' : '' ?>>
                                <?= date('d.m.Y', strtotime($deadline)) ?>
                            </span>
                            <?php if ($v['deadline_extended']): ?>
                                <br><small style="color:var(--color_04)">(продлён)</small>
                            <?php endif; ?>
                            <?php if ($isOverdue): ?>
                                <br><small style="color:#c62828">Срок истёк</small>
                            <?php endif; ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>

                    <td>
                        <span class="status-tag" style="
                            display:inline-block;
                            padding:2px 8px;
                            border-radius:10px;
                            font-size:11px;
                            font-weight:500;
                            background:var(--color_07);
                            color:<?= $sBadge['color'] ?>">
                            <?= $sBadge['text'] ?>
                        </span>
                        <?php if ($schedSt === 3 && !empty($v['schedule_comment'])): ?>
                            <div style="font-size:11px; color:#c62828; margin-top:2px">
                                <?= htmlspecialchars($v['schedule_comment']) ?>
                            </div>
                        <?php endif; ?>
                    </td>

                    <td>
                        <span class="status-tag" style="
                            display:inline-block;
                            padding:2px 8px;
                            border-radius:10px;
                            font-size:11px;
                            font-weight:500;
                            background:var(--color_07);
                            color:<?= $fBadge['color'] ?>">
                            <?= $fBadge['text'] ?>
                        </span>
                        <?php if ($fixSt === 1 && !empty($v['fix_files'])): ?>
                            <?php $cnt = count(json_decode($v['fix_files'], true) ?: []); ?>
                            <?php if ($cnt > 0): ?>
                                <div style="font-size:11px; color:var(--color_04); margin-top:2px">
                                    <span class="material-icons" style="font-size:12px; vertical-align:middle">attach_file</span>
                                    <?= $cnt ?> файл<?= $cnt > 1 ? 'а' : '' ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if (intval($v['agreement_id']) > 0): ?>
                            <button type="button"
                                    class="button icon text btn-open-schedule"
                                    data-agreement="<?= intval($v['agreement_id']) ?>"
                                    title="Открыть план-график">
                                <span class="material-icons">checklist</span>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

</div><!-- /scroll_wrap -->

<script>
    $(function () {

        // Открыть план-график по кнопке или клику по строке
        $(document).on('click', '.btn-open-schedule, #tbl_violations tbody tr td:not(:last-child)', function () {
            var $row = $(this).closest('tr');
            var agrId = $row.data('agreement');
            if (!agrId) return;
            el_app.dialog_open('schedule', {agreement_id: agrId}, 'violations');
        });

        // Фильтр по учреждению — перезагружаем страницу с GET-параметром
        $('#filter_institution').on('change', function () {
            var ins = $(this).val();
            var url = new URL(window.location.href);
            if (parseInt(ins) > 0) {
                url.searchParams.set('institution', ins);
            } else {
                url.searchParams.delete('institution');
            }
            window.location.href = url.toString();
        });

    });
</script>