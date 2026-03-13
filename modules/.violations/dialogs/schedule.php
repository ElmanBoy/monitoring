<?php
/**
 * modules/violations/dialogs/schedule.php
 *
 * Диалог плана-графика устранения нарушений.
 * Открывается через el_app.dialog_open('schedule', {agreement_id: X}, 'violations')
 *
 * POST-параметры:
 *   params[0] — agreement_id
 */

use Core\Db;
use Core\Auth;
use Core\Files;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db    = new Db();
$auth  = new Auth();
$files = new Files();

if (!$auth->isLogin()) { echo 'Нет доступа'; exit; }

$agreementId = intval($_POST['params'][0] ?? $_POST['agreement_id'] ?? 0);
if ($agreementId <= 0) { echo 'Не указан акт'; exit; }

$agr = $db->selectOne('agreement', ' WHERE id = ? AND documentacial = 2', [$agreementId]);
if (!$agr) { echo 'Акт не найден'; exit; }

// Текущий пользователь и его роли
$currentUser = $db->selectOne('users', ' WHERE id = ?', [$_SESSION['user_id']]);
$userRoles   = json_decode($currentUser->roles ?? '[]', true) ?: [];
$isOK        = in_array(5, $userRoles);   // объект контроля (директор учреждения)
$isMinistry  = !$isOK;

// Учреждение
$ins = $db->selectOne('institutions', ' WHERE id = ?', [intval($agr->ins_id)]);

// Нарушения через checkstaff → checksviolations
$plan      = $db->selectOne('checksplans', ' WHERE id = ?', [intval($agr->plan_id)]);
$staffRows = ($plan)
    ? $db->select('checkstaff', ' WHERE check_uid = ? AND institution = ?', [$plan->uid, $agr->ins_id])
    : [];

$taskIds    = array_map(fn($s) => intval($s->id), $staffRows);
$violations = [];

if (!empty($taskIds)) {
    $taskIdsStr = implode(',', $taskIds);
    $violations = $db->db::getAll(
        "SELECT cv.*, vt.name AS violation_type_name
         FROM " . TBL_PREFIX . "checksviolations cv
         LEFT JOIN " . TBL_PREFIX . "checks vt ON vt.id = cv.violations
         WHERE cv.tasks IN ($taskIdsStr)
         ORDER BY cv.id"
    );
}

// Общий статус графика
$scheduleState = 'empty';
if (!empty($violations)) {
    $statuses = array_map('intval', array_column($violations, 'schedule_status'));
    $nullOrZero = array_filter($statuses, fn($s) => $s === 0);
    if (count($nullOrZero) === count($statuses)) {
        $scheduleState = 'filling';
    } elseif (in_array(3, $statuses)) {
        $scheduleState = 'rejected';
    } elseif (in_array(1, $statuses)) {
        $scheduleState = 'waiting';
    } else {
        $scheduleState = 'approved';
    }
}

// Метки статусов
$fixStatusLabels = [
    0 => ['text' => 'Не устранено',           'color' => '#999'],
    1 => ['text' => 'На проверке',             'color' => '#1565c0'],
    2 => ['text' => 'Снято',                   'color' => '#2e7d32'],
    3 => ['text' => 'Возврат на доработку',    'color' => '#e65100'],
    4 => ['text' => 'Не предоставлено в срок', 'color' => '#c62828'],
];
$scheduleStatusLabels = [
    0 => ['text' => 'Ожидает заполнения', 'color' => '#999'],
    1 => ['text' => 'Ожидает утверждения','color' => '#1565c0'],
    2 => ['text' => 'Утверждён',          'color' => '#2e7d32'],
    3 => ['text' => 'Отклонён',           'color' => '#c62828'],
];

// Статистика
$total   = count($violations);
$closed  = count(array_filter($violations, fn($v) => intval($v['fix_status']) === 2));
$pending = count(array_filter($violations, fn($v) => intval($v['fix_status']) === 1));
?>

<style>
    #schedule_dialog table.reg { width: 100%; border-collapse: collapse; }
    #schedule_dialog table.reg th {
        background: var(--color_07);
        padding: 8px 10px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        border-bottom: 2px solid var(--color_06);
        white-space: nowrap;
    }
    #schedule_dialog table.reg td {
        padding: 8px 10px;
        border-bottom: 1px solid var(--color_06);
        vertical-align: top;
        font-size: 13px;
    }
    #schedule_dialog table.reg tr:last-child td { border-bottom: none; }
    #schedule_dialog table.reg tr:hover td { background: var(--color_07); }
    #schedule_dialog .vio-num { color: var(--color_04); font-size: 11px; width: 28px; }
    #schedule_dialog .status-tag {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 500;
        background: var(--color_07);
    }
    #schedule_dialog .overdue { color: #c62828; font-weight: 600; }
    #schedule_dialog .info-bar {
        padding: 9px 14px;
        border-radius: 5px;
        font-size: 13px;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    #schedule_dialog .info-bar.info    { background: #e3f0fb; color: #1565c0; }
    #schedule_dialog .info-bar.warning { background: #fff3e0; color: #e65100; }
    #schedule_dialog .info-bar.success { background: #e6f4ea; color: #2e7d32; }
    #schedule_dialog .reject-comment   { font-size: 12px; color: #c62828; margin-top: 3px; }
    #schedule_dialog .fix-comment-text { font-size: 12px; color: var(--color_04); margin-top: 3px; }
    #schedule_dialog .row-actions      { display: flex; gap: 4px; flex-wrap: wrap; }
    #schedule_dialog .el_input         { font-size: 13px; }
</style>

<div class="pop_up drag" id="schedule_dialog" style="width: 92vw; max-width: 1150px;">
    <div class="title handle">
        <div class="name">
            <span class="material-icons" style="font-size:18px; vertical-align:middle; margin-right:4px">checklist</span>
            План-график устранения нарушений — <strong><?= htmlspecialchars($ins->name ?? '') ?></strong>
        </div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>

    <div class="pop_up_body" style="padding: 16px 20px;">

        <?php if (empty($violations)): ?>
            <div style="text-align:center; padding:40px; color:var(--color_04);">
                <span class="material-icons" style="font-size:48px; display:block; margin-bottom:8px">checklist</span>
                Нарушений по данному акту не обнаружено
            </div>

        <?php else: ?>

            <?php if ($isOK && $scheduleState === 'filling'): ?>
                <div class="info-bar info">
                    <span class="material-icons" style="font-size:18px">info</span>
                    Заполните ответственного и срок по каждому пункту, затем нажмите «Отправить на утверждение».
                </div>
            <?php elseif ($isOK && $scheduleState === 'rejected'): ?>
                <div class="info-bar warning">
                    <span class="material-icons" style="font-size:18px">warning</span>
                    <strong>График отклонён.</strong>&nbsp;Ознакомьтесь с замечаниями и повторно отправьте на утверждение.
                </div>
            <?php elseif ($isOK && $scheduleState === 'waiting'): ?>
                <div class="info-bar info">
                    <span class="material-icons" style="font-size:18px">hourglass_empty</span>
                    График направлен на утверждение. Ожидайте решения.
                </div>
            <?php elseif ($scheduleState === 'approved'): ?>
                <div class="info-bar success">
                    <span class="material-icons" style="font-size:18px">check_circle</span>
                    График утверждён. Устранено: <strong><?= $closed ?>/<?= $total ?></strong>
                    <?php if ($pending > 0): ?>&nbsp;· На проверке: <strong><?= $pending ?></strong><?php endif; ?>
                </div>
            <?php endif; ?>

            <form class="noreset" id="frm_schedule" onsubmit="return false">
                <input type="hidden" name="agreement_id" value="<?= $agreementId ?>">
                <div style="overflow-x:auto;">
                <table class="reg">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Нарушение</th>
                            <th>Тип</th>
                            <th>Ответственный</th>
                            <th>Срок</th>
                            <?php if ($scheduleState !== 'filling' || $isMinistry): ?>
                                <th>Статус графика</th>
                            <?php endif; ?>
                            <?php if ($scheduleState === 'approved' || $isMinistry): ?>
                                <th>Устранение</th>
                                <th>Подтверждение</th>
                                <th>Действия</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($violations as $i => $v):
                        $vid        = intval($v['id']);
                        $schedSt    = intval($v['schedule_status']);
                        $fixSt      = intval($v['fix_status']);
                        $isEditable = $isOK && in_array($schedSt, [0, 3]);
                        $deadline   = $v['deadline_extended'] ?: $v['deadline'];
                        $isOverdue  = $deadline && strtotime($deadline) < time() && $fixSt < 2;
                        $sBadge     = $scheduleStatusLabels[$schedSt] ?? $scheduleStatusLabels[0];
                        $fBadge     = $fixStatusLabels[$fixSt] ?? $fixStatusLabels[0];
                    ?>
                        <tr id="vrow_<?= $vid ?>">

                            <td class="vio-num"><?= $i + 1 ?></td>

                            <td style="max-width:280px"><?= nl2br(htmlspecialchars($v['name'])) ?></td>

                            <td><?= htmlspecialchars($v['violation_type_name'] ?? '—') ?></td>

                            <td style="width:180px">
                                <?php if ($isEditable): ?>
                                    <input type="text"
                                           class="el_input"
                                           name="items[<?= $vid ?>][responsible]"
                                           placeholder="ФИО ответственного"
                                           value="<?= htmlspecialchars($v['responsible'] ?? '') ?>">
                                    <input type="hidden" name="items[<?= $vid ?>][id]" value="<?= $vid ?>">
                                <?php else: ?>
                                    <?= htmlspecialchars($v['responsible'] ?? '—') ?>
                                <?php endif; ?>
                            </td>

                            <td style="width:130px">
                                <?php if ($isEditable): ?>
                                    <input type="date"
                                           class="el_input"
                                           name="items[<?= $vid ?>][deadline]"
                                           value="<?= htmlspecialchars($v['deadline'] ?? '') ?>">
                                <?php else: ?>
                                    <span <?= $isOverdue ? 'class="overdue"' : '' ?>>
                                        <?= $deadline ? date('d.m.Y', strtotime($deadline)) : '—' ?>
                                        <?= $v['deadline_extended'] ? '<br><small style="color:var(--color_04)">(продлён)</small>' : '' ?>
                                    </span>
                                    <?php if ($isOverdue): ?>
                                        <br><small class="overdue">Срок истёк</small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            <?php if ($scheduleState !== 'filling' || $isMinistry): ?>
                                <td style="width:140px">
                                    <span class="status-tag" style="color:<?= $sBadge['color'] ?>">
                                        <?= $sBadge['text'] ?>
                                    </span>
                                    <?php if ($schedSt === 3 && !empty($v['schedule_comment'])): ?>
                                        <div class="reject-comment"><?= htmlspecialchars($v['schedule_comment']) ?></div>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>

                            <?php if ($scheduleState === 'approved' || $isMinistry): ?>

                                <td style="width:130px">
                                    <span class="status-tag" style="color:<?= $fBadge['color'] ?>">
                                        <?= $fBadge['text'] ?>
                                    </span>
                                    <?php if (!empty($v['check_comment'])): ?>
                                        <div class="reject-comment"><?= htmlspecialchars($v['check_comment']) ?></div>
                                    <?php endif; ?>
                                </td>

                                <td style="width:170px">
                                    <?php if ($isOK && in_array($fixSt, [0, 3]) && $schedSt === 2): ?>
                                        <input type="file"
                                               id="fix_files_<?= $vid ?>"
                                               multiple
                                               style="font-size:11px; display:block; margin-bottom:4px; width:100%">
                                        <input type="text"
                                               id="fix_comment_<?= $vid ?>"
                                               class="el_input"
                                               placeholder="Комментарий"
                                               style="font-size:12px">
                                    <?php elseif ($fixSt >= 1): ?>
                                        <?php $fids = json_decode($v['fix_files'] ?? '[]', true) ?: [];
                                        foreach ($fids as $fid):
                                            $f = $files->getFileById(intval($fid));
                                            if ($f): ?>
                                                <a href="<?= htmlspecialchars($f->path) ?>"
                                                   target="_blank"
                                                   style="font-size:12px; display:block">
                                                    <span class="material-icons" style="font-size:14px; vertical-align:middle">attach_file</span>
                                                    <?= htmlspecialchars($f->original_name ?? 'Файл') ?>
                                                </a>
                                        <?php endif; endforeach; ?>
                                        <?php if (!empty($v['fix_comment'])): ?>
                                            <div class="fix-comment-text"><?= htmlspecialchars($v['fix_comment']) ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>

                                <td style="width:160px">
                                    <div class="row-actions">
                                        <?php if ($isOK && in_array($fixSt, [0, 3]) && $schedSt === 2): ?>
                                            <button type="button"
                                                    class="button icon text btn-submit-fix"
                                                    data-id="<?= $vid ?>">
                                                <span class="material-icons">send</span>Отправить
                                            </button>

                                        <?php elseif ($isMinistry && $fixSt === 1): ?>
                                            <button type="button"
                                                    class="button icon text btn-fix-close"
                                                    data-id="<?= $vid ?>">
                                                <span class="material-icons">check_circle</span>Снять
                                            </button>
                                            <button type="button"
                                                    class="button icon text btn-fix-return"
                                                    data-id="<?= $vid ?>">
                                                <span class="material-icons">replay</span>Вернуть
                                            </button>

                                        <?php elseif ($isMinistry && in_array($fixSt, [0, 3]) && $schedSt === 2): ?>
                                            <button type="button"
                                                    class="button icon text btn-fix-extend"
                                                    data-id="<?= $vid ?>">
                                                <span class="material-icons">event</span>Продлить
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>

                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </form>

        <?php endif; ?>
    </div>

    <?php if (!empty($violations)): ?>
    <div class="confirm">
        <?php if ($isOK && in_array($scheduleState, ['filling', 'rejected'])): ?>
            <button type="button" class="button icon text" id="btn_save_draft">
                <span class="material-icons">save</span>Сохранить черновик
            </button>
            <button type="button" class="button icon text" id="btn_submit_schedule">
                <span class="material-icons">send</span>Отправить на утверждение
            </button>
        <?php elseif ($isMinistry && $scheduleState === 'waiting'): ?>
            <button type="button" class="button icon text" id="btn_reject_schedule">
                <span class="material-icons">cancel</span>Отклонить
            </button>
            <button type="button" class="button icon text" id="btn_approve_schedule">
                <span class="material-icons">check_circle</span>Утвердить
            </button>
        <?php endif; ?>
        <button type="button" class="button icon text close">
            <span class="material-icons">close</span>Закрыть
        </button>
    </div>
    <?php endif; ?>
</div>

<script>
    (function () {
        var agreementId = <?= $agreementId ?>;

        el_app.mainInit();

        function reloadDialog() {
            $('.preloader').fadeIn('fast');
            $.post('/', {
                ajax: 1,
                mode: 'popup',
                module: 'violations',
                url: 'schedule',
                params: [agreementId]
            }, function (data) {
                $('#schedule_dialog').closest('.wrap_pop_up').html(data);
                $('.preloader').fadeOut('fast');
            });
        }

        function collectItems() {
            var items = [];
            $('#frm_schedule input[name^="items["]').each(function () {
                var m = $(this).attr('name').match(/items\[(\d+)\]\[(\w+)\]/);
                if (!m) return;
                var id = m[1], field = m[2];
                var entry = $.grep(items, function (e) { return e.id == id; })[0];
                if (!entry) { entry = {id: id}; items.push(entry); }
                entry[field] = $(this).val();
            });
            return items;
        }

        $('#btn_save_draft').on('click', function () {
            $('.preloader').fadeIn('fast');
            $.post('/', {
                ajax: 1, action: 'saveSchedule', path: 'violations',
                agreement_id: agreementId, items: collectItems(), submit: 0
            }, function (data) {
                var res = JSON.parse(data);
                $('.preloader').fadeOut('fast');
                inform(res.result ? 'Отлично!' : 'Ошибка', res.resultText);
            });
        });

        $('#btn_submit_schedule').on('click', function () {
            if (!confirm('Отправить план-график на утверждение министерству?')) return;
            $('.preloader').fadeIn('fast');
            $.post('/', {
                ajax: 1, action: 'saveSchedule', path: 'violations',
                agreement_id: agreementId, items: collectItems(), submit: 1
            }, function (data) {
                var res = JSON.parse(data);
                $('.preloader').fadeOut('fast');
                inform(res.result ? 'Отлично!' : 'Ошибка', res.resultText);
                if (res.result) reloadDialog();
            });
        });

        $('#btn_approve_schedule').on('click', function () {
            if (!confirm('Утвердить план-график устранения нарушений?')) return;
            $('.preloader').fadeIn('fast');
            $.post('/', {
                ajax: 1, action: 'approveSchedule', path: 'violations',
                agreement_id: agreementId, action_type: 'approve'
            }, function (data) {
                var res = JSON.parse(data);
                $('.preloader').fadeOut('fast');
                inform(res.result ? 'Отлично!' : 'Ошибка', res.resultText);
                if (res.result) reloadDialog();
            });
        });

        $('#btn_reject_schedule').on('click', function () {
            var comment = prompt('Укажите замечания для объекта контроля:');
            if (!comment || $.trim(comment) === '') return;
            $('.preloader').fadeIn('fast');
            $.post('/', {
                ajax: 1, action: 'approveSchedule', path: 'violations',
                agreement_id: agreementId, action_type: 'reject', comment: comment
            }, function (data) {
                var res = JSON.parse(data);
                $('.preloader').fadeOut('fast');
                inform(res.result ? 'Отлично!' : 'Ошибка', res.resultText);
                if (res.result) reloadDialog();
            });
        });

        // ОК: отправить подтверждение (файл — через $.ajax FormData)
        $(document).on('click', '.btn-submit-fix', function () {
            var vid = $(this).data('id');
            var fileInput = document.getElementById('fix_files_' + vid);
            if (!fileInput || fileInput.files.length === 0) {
                inform('Ошибка', 'Прикрепите файл подтверждения');
                return;
            }
            var fd = new FormData();
            fd.append('ajax', '1');
            fd.append('action', 'submitFix');
            fd.append('path', 'violations');
            fd.append('violation_id', vid);
            fd.append('fix_comment', $('#fix_comment_' + vid).val() || '');
            $.each(fileInput.files, function (i, f) { fd.append('files[]', f); });
            $('.preloader').fadeIn('fast');
            $.ajax({
                url: '/', type: 'POST', data: fd,
                processData: false, contentType: false,
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                success: function (data) {
                    var res = JSON.parse(data);
                    $('.preloader').fadeOut('fast');
                    inform(res.result ? 'Отлично!' : 'Ошибка', res.resultText);
                    if (res.result) reloadDialog();
                }
            });
        });

        $(document).on('click', '.btn-fix-close', function () {
            if (!confirm('Подтвердить снятие нарушения?')) return;
            var vid = $(this).data('id');
            $('.preloader').fadeIn('fast');
            $.post('/', {
                ajax: 1, action: 'reviewFix', path: 'violations',
                violation_id: vid, fix_action: 'close'
            }, function (data) {
                var res = JSON.parse(data);
                $('.preloader').fadeOut('fast');
                inform(res.result ? 'Отлично!' : 'Ошибка', res.resultText);
                if (res.result) reloadDialog();
            });
        });

        $(document).on('click', '.btn-fix-return', function () {
            var comment = prompt('Укажите причину возврата:');
            if (!comment || $.trim(comment) === '') return;
            var vid = $(this).data('id');
            $('.preloader').fadeIn('fast');
            $.post('/', {
                ajax: 1, action: 'reviewFix', path: 'violations',
                violation_id: vid, fix_action: 'return', check_comment: comment
            }, function (data) {
                var res = JSON.parse(data);
                $('.preloader').fadeOut('fast');
                inform(res.result ? 'Отлично!' : 'Ошибка', res.resultText);
                if (res.result) reloadDialog();
            });
        });

        $(document).on('click', '.btn-fix-extend', function () {
            var vid = $(this).data('id');
            var newDate = prompt('Новый срок устранения (ГГГГ-ММ-ДД):');
            if (!newDate || $.trim(newDate) === '') return;
            var reason = prompt('Причина продления срока:');
            if (!reason || $.trim(reason) === '') return;
            $('.preloader').fadeIn('fast');
            $.post('/', {
                ajax: 1, action: 'reviewFix', path: 'violations',
                violation_id: vid, fix_action: 'extend',
                deadline_extended: newDate, extended_reason: reason
            }, function (data) {
                var res = JSON.parse(data);
                $('.preloader').fadeOut('fast');
                inform(res.result ? 'Отлично!' : 'Ошибка', res.resultText);
                if (res.result) reloadDialog();
            });
        });

    })();
</script>
