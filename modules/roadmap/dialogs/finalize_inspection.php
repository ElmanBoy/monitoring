<?php
/**
 * Диалог итоговой фиксации результатов проверки
 */

use Core\Db;
use Core\Gui;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db = new Db();
$gui = new Gui();
$auth = new Auth();

$taskId = intval($_POST['params']['taskId'] ?? 0);

// Загружаем данные задачи/проверки
$task = $db->selectOne('tasks', ' WHERE id = ?', [$taskId]);
if (!$task) {
    echo '<div class="error">Задача не найдена</div>';
    exit;
}

// Загружаем checkstaff
$checkstaff = $db->selectOne('checkstaff', ' WHERE id = ?', [intval($task->checkstaff ?? 0)]);
$institution = $db->selectOne('institutions', ' WHERE id = ?', [intval($checkstaff->institution ?? 0)]);

// Ищем акт о нарушениях
$act = $db->selectOne('agreement', ' WHERE source_id = ? AND documentacial = 2 AND active = 1', [$taskId]);

// Ищем график устранения
$schedule = null;
$scheduleStats = ['total' => 0, 'fixed' => 0, 'pending' => 0, 'overdue' => 0];

if ($act) {
    $schedule = $db->selectOne('agreement', ' WHERE source_id = ? AND documentacial = 5 AND active = 1', [$act->id]);

    if ($schedule) {
        $scheduleData = json_decode($schedule->agreementlist ?? '[]', true) ?: [];
        $scheduleStats['total'] = count($scheduleData);

        foreach ($scheduleData as $item) {
            $fixStatus = intval($item['fix_status'] ?? 0);
            if ($fixStatus === 2) {
                $scheduleStats['fixed']++;
            } else {
                $scheduleStats['pending']++;

                $deadline = $item['deadline_extended'] ?? $item['schedule_deadlines'] ?? null;
                if ($deadline && strtotime($deadline) < time()) {
                    $scheduleStats['overdue']++;
                }
            }
        }
    }
}

// Проверяем, не зафиксирован ли уже результат
$existingResult = $db->selectOne('inspectionresults', ' WHERE task_id = ? AND active = 1', [$taskId]);
?>

<style>
    .finalize_info {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .info_section {
        margin-bottom: 15px;
    }
    .info_section h4 {
        margin: 0 0 10px 0;
        color: #333;
        font-size: 14px;
    }
    .info_row {
        display: flex;
        padding: 5px 0;
        border-bottom: 1px solid #eee;
    }
    .info_row:last-child {
        border-bottom: none;
    }
    .info_label {
        flex: 0 0 200px;
        font-weight: 600;
        color: #666;
    }
    .info_value {
        flex: 1;
        color: #333;
    }
    .result_badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .result_badge.success { background: #c8e6c9; color: #2e7d32; }
    .result_badge.warning { background: #fff3e0; color: #e65100; }
    .result_badge.danger { background: #ffcdd2; color: #c62828; }
</style>

<div class="pop_up drag" style="width: 70vw; min-height: 60vh;">
    <div class="title handle">
        <div class="name">Итоговая фиксация результатов проверки</div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">

        <?php if ($existingResult): ?>
            <div style="padding: 40px; text-align: center;">
                <span class="material-icons" style="font-size: 64px; color: #4CAF50; display: block; margin-bottom: 15px;">check_circle</span>
                <h3>Результаты проверки уже зафиксированы</h3>
                <p style="color: #666;">Дата фиксации: <?= date('d.m.Y H:i', strtotime($existingResult->created_at)) ?></p>
                <div style="margin-top: 20px;">
                    <div class="button" onclick="el_app.dialog_close('finalize_inspection');">
                        <span class="material-icons">close</span>
                        <span>Закрыть</span>
                    </div>
                </div>
            </div>
        <?php else: ?>

            <div class="finalize_info">
                <div class="info_section">
                    <h4>Информация о проверке</h4>
                    <div class="info_row">
                        <div class="info_label">Учреждение:</div>
                        <div class="info_value"><?= htmlspecialchars($institution->name ?? 'Не указано') ?></div>
                    </div>
                    <div class="info_row">
                        <div class="info_label">Задача:</div>
                        <div class="info_value"><?= htmlspecialchars($task->name ?? '') ?></div>
                    </div>
                    <div class="info_row">
                        <div class="info_label">Дата проверки:</div>
                        <div class="info_value">
                            <?= $checkstaff->arrival ? date('d.m.Y', strtotime($checkstaff->arrival)) : '—' ?>
                            <?= $checkstaff->ending ? ' — ' . date('d.m.Y', strtotime($checkstaff->ending)) : '' ?>
                        </div>
                    </div>
                </div>

                <div class="info_section">
                    <h4>Результаты по нарушениям</h4>
                    <?php if ($act): ?>
                        <div class="info_row">
                            <div class="info_label">Акт о нарушениях:</div>
                            <div class="info_value">
                                <span class="result_badge warning">Составлен</span>
                                <?= $act->act_number ?? '№ ' . $act->id ?>
                            </div>
                        </div>

                        <?php if ($schedule): ?>
                            <div class="info_row">
                                <div class="info_label">График устранения:</div>
                                <div class="info_value">
                                    <span class="result_badge warning">Сформирован</span>
                                </div>
                            </div>
                            <div class="info_row">
                                <div class="info_label">Всего нарушений:</div>
                                <div class="info_value"><strong><?= $scheduleStats['total'] ?></strong></div>
                            </div>
                            <div class="info_row">
                                <div class="info_label">Устранено:</div>
                                <div class="info_value">
                                    <?php if ($scheduleStats['fixed'] > 0): ?>
                                        <span class="result_badge success"><?= $scheduleStats['fixed'] ?></span>
                                    <?php else: ?>
                                        0
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="info_row">
                                <div class="info_label">Не устранено:</div>
                                <div class="info_value">
                                    <?php if ($scheduleStats['pending'] > 0): ?>
                                        <span class="result_badge warning"><?= $scheduleStats['pending'] ?></span>
                                    <?php else: ?>
                                        0
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($scheduleStats['overdue'] > 0): ?>
                                <div class="info_row">
                                    <div class="info_label">Просрочено:</div>
                                    <div class="info_value">
                                        <span class="result_badge danger"><?= $scheduleStats['overdue'] ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="info_row">
                                <div class="info_label">График устранения:</div>
                                <div class="info_value" style="color: #999;">Не создан</div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="info_row">
                            <div class="info_label">Нарушения:</div>
                            <div class="info_value">
                                <span class="result_badge success">Отсутствуют</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <form method="post" id="finalize_inspection_form" class="ajaxFrm">
                <input type="hidden" name="task_id" value="<?= $taskId ?>">
                <input type="hidden" name="checkstaff_id" value="<?= $checkstaff->id ?? 0 ?>">
                <input type="hidden" name="act_id" value="<?= $act->id ?? 0 ?>">
                <input type="hidden" name="schedule_id" value="<?= $schedule->id ?? 0 ?>">
                <input type="hidden" name="total_violations" value="<?= $scheduleStats['total'] ?>">
                <input type="hidden" name="violations_fixed" value="<?= $scheduleStats['fixed'] ?>">
                <input type="hidden" name="violations_unfixed" value="<?= $scheduleStats['pending'] ?>">

                <div class="group">
                    <div class="item w_100">
                        <div class="el_data">
                            <label>Тип результата</label>
                            <select name="result_type" class="el_input" required>
                                <option value="">Выберите тип результата</option>
                                <?php if (!$act): ?>
                                    <option value="no_violations">Нарушения отсутствуют</option>
                                <?php elseif ($scheduleStats['fixed'] === $scheduleStats['total']): ?>
                                    <option value="violations_fixed">Все нарушения устранены</option>
                                <?php else: ?>
                                    <option value="violations_partial">Нарушения частично устранены</option>
                                    <option value="violations_pending">Нарушения в процессе устранения</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="item w_100">
                        <div class="el_data">
                            <label>Итоговое заключение</label>
                            <textarea name="result_summary" class="el_input" rows="6" required placeholder="Краткое описание результатов проверки, принятых мер и рекомендаций..."></textarea>
                        </div>
                    </div>

                    <div class="item w_50">
                        <div class="el_data">
                            <label>Дата финализации</label>
                            <input type="date" name="finalized_date" class="el_input" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                </div>

                <div class="group" style="margin-top: 30px;">
                    <div class="item w_100" style="text-align: right;">
                        <div class="button positive" onclick="$('#finalize_inspection_form').submit();">
                            <span class="material-icons">check_circle</span>
                            <span>Зафиксировать результаты</span>
                        </div>
                        <div class="button" onclick="el_app.dialog_close('finalize_inspection');">
                            <span class="material-icons">close</span>
                            <span>Отмена</span>
                        </div>
                    </div>
                </div>
            </form>

        <?php endif; ?>

    </div>
</div>
