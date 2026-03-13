<?php
/**
 * modules/roadmap/dialogs/view_road.php
 *
 * Просмотр графика устранения нарушений + ведение статусов.
 * Открывается через el_app.dialog_open('view_road', {roadId: X}, 'roadmap')
 *
 * Роли:
 *   ОК (role=5)     — прикрепляет файлы подтверждения, отправляет на проверку
 *   Министерство    — снимает / возвращает / продлевает срок
 */

use Core\Db;
use Core\Auth;
use Core\Files;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db   = new Db();
$auth = new Auth();

$roadId    = intval($_POST['params']['roadId'] ?? 0);
$is_object = $auth->haveUserRole(5);

$road = $db->selectOne('agreement', ' WHERE id = ? AND documentacial = 5', [$roadId]);
if (!$road) { echo 'График не найден'; exit; }

$schedule = json_decode($road->agreementlist ?? '[]', true) ?: [];
$ins      = $db->selectOne('institutions', ' WHERE id = ?', [intval($road->ins_id ?: $road->source_id)]);

// Загружаем исходный акт
$act = $db->selectOne('agreement', ' WHERE id = ?', [intval($road->source_id)]);

// Статистика
$total   = count($schedule);
$done    = count(array_filter($schedule, fn($s) => intval($s['fix_status'] ?? 0) === 2));
$onCheck = count(array_filter($schedule, fn($s) => intval($s['fix_status'] ?? 0) === 1));
$overdue = 0;
foreach ($schedule as $s) {
    $dl = $s['deadline_extended'] ?? $s['schedule_deadlines'] ?? null;
    if ($dl && strtotime($dl) < time() && intval($s['fix_status'] ?? 0) < 2) $overdue++;
}

$fixStatusLabels = [
    0 => ['text' => 'Не устранено',        'color' => '#999'],
    1 => ['text' => 'На проверке',          'color' => '#1565c0'],
    2 => ['text' => 'Снято',               'color' => '#2e7d32'],
    3 => ['text' => 'Возврат',             'color' => '#e65100'],
    4 => ['text' => 'Просрочено',          'color' => '#c62828'],
];
?>

<style>
    #view_road_dialog table.road_tbl { width: 100%; border-collapse: collapse; }
    #view_road_dialog table.road_tbl th {
        background: var(--color_07);
        padding: 8px 10px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        border-bottom: 2px solid var(--color_06);
        white-space: nowrap;
    }
    #view_road_dialog table.road_tbl td {
        padding: 8px 10px;
        border-bottom: 1px solid var(--color_06);
        vertical-align: top;
        font-size: 13px;
    }
    #view_road_dialog table.road_tbl tr:last-child td { border-bottom: none; }
    #view_road_dialog table.road_tbl tr:hover td { background: var(--color_07); }
    #view_road_dialog .status-tag {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 500;
        background: var(--color_07);
    }
    #view_road_dialog .overdue { color: #c62828; font-weight: 600; }
    #view_road_dialog .info-bar {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 9px 14px;
        border-radius: 5px;
        font-size: 13px;
        margin-bottom: 14px;
    }
    #view_road_dialog .info-bar.info    { background: #e3f0fb; color: #1565c0; }
    #view_road_dialog .info-bar.success { background: #e6f4ea; color: #2e7d32; }
    #view_road_dialog .info-bar.warning { background: #fff3e0; color: #e65100; }
    #view_road_dialog .row-actions { display: flex; gap: 4px; flex-wrap: wrap; }
    #view_road_dialog .fix-note { font-size: 11px; color: var(--color_04); margin-top: 3px; }
    #view_road_dialog .fix-reject { font-size: 11px; color: #c62828; margin-top: 3px; }
</style>

<div class="pop_up drag" id="view_road_dialog" style="width: 90vw; max-width: 1100px;">
    <div class="title handle">
        <div class="name">
            <span class="material-icons" style="font-size:18px; vertical-align:middle; margin-right:4px">rule</span>
            График устранения нарушений —
            <strong><?= htmlspecialchars($ins->name ?? '') ?></strong>
        </div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>

    <div class="pop_up_body" style="padding:16px 20px;">

        <?php if ($total === 0): ?>
            <div style="text-align:center; padding:40px; color:var(--color_04)">
                <span class="material-icons" style="font-size:48px; display:block; margin-bottom:8px">rule</span>
                Строки графика отсутствуют
            </div>
        <?php else: ?>

            <?php if ($done === $total): ?>
                <div class="info-bar success">
                    <span class="material-icons" style="font-size:18px">check_circle</span>
                    Все нарушения устранены (<?= $done ?>/<?= $total ?>).
                </div>
            <?php elseif ($overdue > 0): ?>
                <div class="info-bar warning">
                    <span class="material-icons" style="font-size:18px">alarm_off</span>
                    Устранено: <strong><?= $done ?>/<?= $total ?></strong> · Просрочено: <strong><?= $overdue ?></strong>
                    <?= $onCheck > 0 ? ' · На проверке: <strong>' . $onCheck . '</strong>' : '' ?>
                </div>
            <?php else: ?>
                <div class="info-bar info">
                    <span class="material-icons" style="font-size:18px">info</span>
                    Устранено: <strong><?= $done ?>/<?= $total ?></strong>
                    <?= $onCheck > 0 ? ' · На проверке: <strong>' . $onCheck . '</strong>' : '' ?>
                </div>
            <?php endif; ?>

            <div style="overflow-x:auto;">
                <table class="road_tbl">
                    <thead>
                    <tr>
                        <th style="width:28px">#</th>
                        <th>Нарушение / Предложения</th>
                        <th>Действия</th>
                        <th style="width:110px">Срок</th>
                        <th style="width:160px">Ответственный</th>
                        <th style="width:120px">Статус</th>
                        <th style="width:170px">Подтверждение</th>
                        <th style="width:155px">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($schedule as $idx => $row):
                        $fixSt   = intval($row['fix_status'] ?? 0);
                        $fBadge  = $fixStatusLabels[$fixSt] ?? $fixStatusLabels[0];
                        $dl      = $row['deadline_extended'] ?? $row['schedule_deadlines'] ?? null;
                        $isOver  = $dl && strtotime($dl) < time() && $fixSt < 2;
                        ?>
                        <tr id="road_row_<?= $idx ?>"<?= $isOver ? ' style="background:rgba(198,40,40,0.04)"' : '' ?>>

                            <td style="color:var(--color_04); font-size:11px"><?= $idx + 1 ?></td>

                            <td style="max-width:250px">
                                <?= htmlspecialchars($row['schedule_offers'] ?? '') ?>
                            </td>

                            <td style="max-width:220px; font-size:12px; color:var(--color_04)">
                                <?= htmlspecialchars($row['schedule_actions'] ?? '') ?>
                            </td>

                            <td style="white-space:nowrap">
                                <?php if ($dl): ?>
                                    <span <?= $isOver ? 'class="overdue"' : '' ?>>
                                    <?= date('d.m.Y', strtotime($dl)) ?>
                                </span>
                                    <?php if (!empty($row['deadline_extended'])): ?>
                                        <br><small style="color:var(--color_04)">(продлён)</small>
                                    <?php endif; ?>
                                    <?php if ($isOver): ?>
                                        <br><small class="overdue">Срок истёк</small>
                                    <?php endif; ?>
                                <?php else: ?> — <?php endif; ?>
                            </td>

                            <td><?= htmlspecialchars($row['schedule_responsible'] ?? '—') ?></td>

                            <td>
                            <span class="status-tag" style="color:<?= $fBadge['color'] ?>">
                                <?= $fBadge['text'] ?>
                            </span>
                                <?php if ($fixSt === 3 && !empty($row['check_comment'])): ?>
                                    <div class="fix-reject"><?= htmlspecialchars($row['check_comment']) ?></div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($is_object && in_array($fixSt, [0, 3])): ?>
                                    <input type="file"
                                           id="fix_file_<?= $idx ?>"
                                           multiple
                                           style="font-size:11px; display:block; margin-bottom:4px; width:100%">
                                    <input type="text"
                                           id="fix_comment_<?= $idx ?>"
                                           class="el_input"
                                           placeholder="Комментарий"
                                           style="font-size:12px">
                                <?php elseif ($fixSt >= 1): ?>
                                    <?php
                                    $fids = is_array($row['fix_files'] ?? null) ? $row['fix_files'] : [];
                                    foreach ($fids as $fpath):
                                        $fname = basename($fpath); ?>
                                        <a href="<?= htmlspecialchars($fpath) ?>"
                                           target="_blank"
                                           style="font-size:12px; display:block">
                                            <span class="material-icons" style="font-size:13px; vertical-align:middle">attach_file</span>
                                            <?= htmlspecialchars($fname) ?>
                                        </a>
                                    <?php endforeach; ?>
                                    <?php if (!empty($row['fix_comment'])): ?>
                                        <div class="fix-note"><?= htmlspecialchars($row['fix_comment']) ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="row-actions">
                                    <?php if ($is_object && in_array($fixSt, [0, 3])): ?>
                                        <button type="button"
                                                class="button icon text btn-submit-fix"
                                                data-idx="<?= $idx ?>"
                                                data-road="<?= $roadId ?>">
                                            <span class="material-icons">send</span>Отправить
                                        </button>

                                    <?php elseif (!$is_object && $fixSt === 1): ?>
                                        <button type="button"
                                                class="button icon text btn-fix-close"
                                                data-idx="<?= $idx ?>"
                                                data-road="<?= $roadId ?>">
                                            <span class="material-icons">check_circle</span>Снять
                                        </button>
                                        <button type="button"
                                                class="button icon text btn-fix-return"
                                                data-idx="<?= $idx ?>"
                                                data-road="<?= $roadId ?>">
                                            <span class="material-icons">replay</span>Вернуть
                                        </button>

                                    <?php elseif (!$is_object && in_array($fixSt, [0, 3])): ?>
                                        <button type="button"
                                                class="button icon text btn-fix-extend"
                                                data-idx="<?= $idx ?>"
                                                data-road="<?= $roadId ?>">
                                            <span class="material-icons">event</span>Продлить
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>

    <div class="confirm">
        <button type="button" class="button icon text close">
            <span class="material-icons">close</span>Закрыть
        </button>
    </div>
</div>

<script>
    (function () {
        var roadId = <?= $roadId ?>;

        el_app.mainInit();

        function reloadDialog() {
            $('.preloader').fadeIn('fast');
            $.post('/', {
                ajax: 1, mode: 'popup', module: 'roadmap', url: 'view_road',
                params: {roadId: roadId}
            }, function (data) {
                $('#view_road_dialog').closest('.wrap_pop_up').html(data);
                $('.preloader').fadeOut('fast');
            });
        }

        // ОК: отправить подтверждение (файл + комментарий)
        $(document).on('click', '.btn-submit-fix', function () {
            var idx       = $(this).data('idx');
            var fileInput = document.getElementById('fix_file_' + idx);
            if (!fileInput || fileInput.files.length === 0) {
                inform('Ошибка', 'Прикрепите файл подтверждения');
                return;
            }
            var fd = new FormData();
            fd.append('ajax', '1');
            fd.append('action', 'update_road');
            fd.append('path', 'roadmap');
            fd.append('road_id', roadId);
            fd.append('row_idx', idx);
            fd.append('fix_action', 'submit');
            fd.append('fix_comment', $('#fix_comment_' + idx).val() || '');
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

        // Министерство: снять нарушение
        $(document).on('click', '.btn-fix-close', function () {
            if (!confirm('Подтвердить снятие нарушения?')) return;
            var idx = $(this).data('idx');
            $('.preloader').fadeIn('fast');
            $.post('/', {
                ajax: 1, action: 'update_road', path: 'roadmap',
                road_id: roadId, row_idx: idx, fix_action: 'close'
            }, function (data) {
                var res = JSON.parse(data);
                $('.preloader').fadeOut('fast');
                inform(res.result ? 'Отлично!' : 'Ошибка', res.resultText);
                if (res.result) reloadDialog();
            });
        });

        // Министерство: вернуть на доработку
        $(document).on('click', '.btn-fix-return', function () {
            var comment = prompt('Укажите причину возврата:');
            if (!comment || $.trim(comment) === '') return;
            var idx = $(this).data('idx');
            $('.preloader').fadeIn('fast');
            $.post('/', {
                ajax: 1, action: 'update_road', path: 'roadmap',
                road_id: roadId, row_idx: idx, fix_action: 'return', check_comment: comment
            }, function (data) {
                var res = JSON.parse(data);
                $('.preloader').fadeOut('fast');
                inform(res.result ? 'Отлично!' : 'Ошибка', res.resultText);
                if (res.result) reloadDialog();
            });
        });

        // Министерство: продлить срок
        $(document).on('click', '.btn-fix-extend', function () {
            var newDate = prompt('Новый срок устранения (ГГГГ-ММ-ДД):');
            if (!newDate || $.trim(newDate) === '') return;
            var reason = prompt('Причина продления:');
            if (!reason || $.trim(reason) === '') return;
            var idx = $(this).data('idx');
            $('.preloader').fadeIn('fast');
            $.post('/', {
                ajax: 1, action: 'update_road', path: 'roadmap',
                road_id: roadId, row_idx: idx, fix_action: 'extend',
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