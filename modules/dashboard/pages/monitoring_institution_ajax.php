<?php
/**
 * Срез мониторинга устранения нарушений по учреждению
 */

use Core\Gui;
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$gui = new Gui();
$db = new Db();
$auth = new Auth();

// Security: Verify authentication
if (!$auth->isLogin()) {
    echo '<script>document.location.href="/?session_expired=1"</script>';
    exit;
}

// Security: Verify module permissions
$modulePerms = $auth->checkModulePermissions(18);
if (!($modulePerms['view'] ?? false)) {
    echo '<script>alert("Доступ запрещён.");</script>';
    die();
}

$gui->set('module_id', 18);

// Получаем institution_id из params (AJAX) или GET
$institutionId = intval($_POST['params'] ?? $_GET['institution_id'] ?? 0);
if (is_string($_POST['params'] ?? null)) {
    parse_str($_POST['params'], $paramsArr);
    $institutionId = intval($paramsArr['institution_id'] ?? 0);
}

// Validation: Check institution_id is provided
if ($institutionId === 0) {
    echo '<div style="padding: 40px; text-align: center;">
        <span class="material-icons" style="font-size: 64px; color: #999;">error_outline</span>
        <h3>Не указано учреждение</h3>
    </div>';
    exit;
}

// Security: Verify institution exists and is active
$institution = $db->selectOne('institutions', ' WHERE id = ? AND active = 1', [$institutionId]);
if (!$institution) {
    echo '<div style="padding: 40px; text-align: center;">
        <span class="material-icons" style="font-size: 64px; color: #999;">error_outline</span>
        <h3>Учреждение не найдено</h3>
    </div>';
    exit;
}

// Получаем общую статистику по учреждению
$stats = $db->db::getRow("
    SELECT * FROM v_dashboard_institutions
    WHERE institution_id = ?
", [$institutionId]);

$fixRate = $stats['total_violations'] > 0
    ? round(($stats['violations_fixed'] / $stats['total_violations']) * 100, 1)
    : 0;

// Получаем детальную информацию по каждому нарушению
$violations = $db->db::getAll("
    SELECT * FROM v_violation_details
    WHERE institution_id = ?
    ORDER BY
        CASE
            WHEN is_overdue THEN 1
            WHEN fix_status = 0 THEN 2
            WHEN fix_status = 1 THEN 3
            ELSE 4
        END,
        deadline
", [$institutionId]);

// Статусы
$statusLabels = [
    0 => ['label' => 'Не начато', 'class' => 'status_pending', 'icon' => 'schedule'],
    1 => ['label' => 'На проверке', 'class' => 'status_check', 'icon' => 'fact_check'],
    2 => ['label' => 'Устранено', 'class' => 'status_fixed', 'icon' => 'check_circle']
];
?>

<style>
    .monitoring_institution_container {
        padding: 20px;
    }

    .institution_header {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .institution_header h2 {
        margin: 0 0 10px 0;
        color: #333;
    }

    .institution_header .meta {
        color: #666;
        font-size: 13px;
    }

    .stats_cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }

    .stat_card {
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: center;
    }

    .stat_card.success { border-top: 4px solid #4CAF50; }
    .stat_card.warning { border-top: 4px solid #FF9800; }
    .stat_card.danger { border-top: 4px solid #f44336; }
    .stat_card.info { border-top: 4px solid #2196F3; }

    .stat_card .value {
        font-size: 36px;
        font-weight: bold;
        color: #333;
        margin: 10px 0;
    }

    .stat_card .label {
        font-size: 13px;
        color: #666;
    }

    .progress_block {
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .progress_bar_large {
        width: 100%;
        height: 40px;
        background: #eee;
        border-radius: 20px;
        overflow: hidden;
        position: relative;
        margin: 15px 0;
    }

    .progress_fill_large {
        height: 100%;
        background: linear-gradient(90deg, #4CAF50, #8BC34A);
        transition: width 0.3s;
    }

    .progress_text_large {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 14px;
        font-weight: bold;
        color: #333;
    }

    .violations_table {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .violations_table table {
        width: 100%;
        border-collapse: collapse;
    }

    .violations_table th {
        background: #f5f5f5;
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        color: #333;
        border-bottom: 2px solid #ddd;
    }

    .violations_table td {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }

    .violations_table tr:hover {
        background: #f9f9f9;
    }

    .status_badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }

    .status_badge .material-icons {
        font-size: 14px;
    }

    .status_pending { background: #fff3e0; color: #e65100; }
    .status_check { background: #e3f2fd; color: #1565c0; }
    .status_fixed { background: #e8f5e9; color: #2e7d32; }

    .overdue_badge {
        background: #ffebee;
        color: #c62828;
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: bold;
        margin-left: 5px;
    }

    .deadline_cell {
        white-space: nowrap;
    }

    .deadline_cell.overdue {
        color: #c62828;
        font-weight: bold;
    }

    .button:hover span,
    .button:hover .material-icons {
        color: #fff !important;
    }
</style>

<div class="nav">
    <div class="nav_01">
        <?
        echo $gui->buildTopNav([
            'title' => 'Мониторинг устранения нарушений',
            'logout' => 'Выйти'
        ]);
        ?>
        <div class="button" onclick="backToDashboard();" title="Вернуться к общему дашборду">
            <span class="material-icons">arrow_back</span>
            <span>К дашборду</span>
        </div>
    </div>
</div>

<div class="scroll_wrap">
    <div class="monitoring_institution_container">

        <div class="institution_header">
            <h2><?= htmlspecialchars($institution->short ?: $institution->name) ?></h2>
            <div class="meta">
                <?php if ($institution->name != ($institution->short ?: $institution->name)): ?>
                    Полное название: <?= htmlspecialchars($institution->name) ?><br>
                <?php endif; ?>
                <?php if ($institution->inn): ?>
                    ИНН: <?= htmlspecialchars($institution->inn) ?>
                <?php endif; ?>
                <?php if ($institution->address): ?>
                    | Адрес: <?= htmlspecialchars($institution->address) ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($stats && $stats['total_violations'] > 0): ?>

            <div class="stats_cards">
                <div class="stat_card info">
                    <div class="label">Всего нарушений</div>
                    <div class="value"><?= $stats['total_violations'] ?></div>
                </div>

                <div class="stat_card success">
                    <div class="label">Устранено</div>
                    <div class="value"><?= $stats['violations_fixed'] ?></div>
                    <div class="label"><?= $fixRate ?>%</div>
                </div>

                <div class="stat_card warning">
                    <div class="label">На проверке</div>
                    <div class="value"><?= $stats['violations_check'] ?></div>
                </div>

                <div class="stat_card info">
                    <div class="label">Ожидают устранения</div>
                    <div class="value"><?= $stats['violations_pending'] ?></div>
                </div>

                <div class="stat_card danger">
                    <div class="label">Просрочено</div>
                    <div class="value"><?= $stats['violations_overdue'] ?></div>
                </div>
            </div>

            <div class="progress_block">
                <h3 style="margin: 0 0 10px 0;">Прогресс устранения нарушений</h3>
                <div class="progress_bar_large">
                    <div class="progress_fill_large" style="width: <?= $fixRate ?>%"></div>
                    <div class="progress_text_large">
                        <?= $stats['violations_fixed'] ?> из <?= $stats['total_violations'] ?> (<?= $fixRate ?>%)
                    </div>
                </div>
            </div>

            <h3 style="margin: 20px 0;">Детальная информация по нарушениям</h3>

            <div class="violations_table">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;">№</th>
                            <th>Предложение по устранению</th>
                            <th style="width: 150px;">Ответственный</th>
                            <th style="width: 120px;">Срок устранения</th>
                            <th style="width: 150px;">Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($violations as $idx => $v): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($v['offers']) ?></strong>
                                    <?php if ($v['actions']): ?>
                                        <br><small style="color: #666;"><?= htmlspecialchars($v['actions']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($v['responsible']) ?></td>
                                <td class="deadline_cell <?= $v['is_overdue'] ? 'overdue' : '' ?>">
                                    <?= $v['effective_deadline'] ? date('d.m.Y', strtotime($v['effective_deadline'])) : '—' ?>
                                    <?php if ($v['is_overdue']): ?>
                                        <br><span class="overdue_badge">ПРОСРОЧЕНО</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status = $statusLabels[$v['fix_status']] ?? ['label' => 'Неизвестно', 'class' => '', 'icon' => 'help'];
                                    ?>
                                    <span class="status_badge <?= $status['class'] ?>">
                                        <span class="material-icons"><?= $status['icon'] ?></span>
                                        <?= $status['label'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <div style="padding: 40px; text-align: center; background: #fff; border-radius: 8px;">
                <span class="material-icons" style="font-size: 64px; color: #4CAF50; display: block; margin-bottom: 15px;">check_circle</span>
                <h3>Нарушений не выявлено</h3>
                <p style="color: #666;">У данного учреждения отсутствуют активные нарушения для устранения</p>
            </div>
        <?php endif; ?>

        <!-- Кнопка закрыть по центру -->
        <div style="text-align: center; margin-top: 30px; padding: 20px 0;">
            <div class="button" onclick="backToDashboard();" style="display: inline-flex;">
                <span class="material-icons">close</span>
                <span>Закрыть</span>
            </div>
        </div>

    </div>
</div>

<script>
function backToDashboard() {
    // Возврат на главный дашборд через AJAX
    $.ajax({
        url: '/',
        type: 'POST',
        headers: {
            'X-Csrf-Token': el_tools.getcookie("CSRF-TOKEN"),
            'X-Requested-With': "XMLHttpRequest"
        },
        data: {
            ajax: 1,
            mode: 'mainpage',
            url: 'dashboard',
            page: 'index'
        },
        success: function(html) {
            $('.main_data').html(html);
            history.pushState({}, '', '?url=dashboard');
            el_app.mainInit();
        }
    });
}
</script>
