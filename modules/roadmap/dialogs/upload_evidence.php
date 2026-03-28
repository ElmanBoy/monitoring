<?php
/**
 * Диалог загрузки подтверждающих документов об устранении нарушения
 */

use Core\Db;
use Core\Gui;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db = new Db();
$gui = new Gui();

$agreementId = intval($_POST['params']['agreementId'] ?? 0);
$violationIndex = intval($_POST['params']['violationIndex'] ?? 0);

$agreement = $db->selectOne('agreement', ' WHERE id = ? AND documentacial = 5', [$agreementId]);
if (!$agreement) {
    echo '<div class="error">График не найден</div>';
    exit;
}

$schedule = json_decode($agreement->agreementlist ?? '[]', true) ?: [];
if (!isset($schedule[$violationIndex])) {
    echo '<div class="error">Нарушение не найдено</div>';
    exit;
}

$violation = $schedule[$violationIndex];
$institution = $db->selectOne('institutions', ' WHERE id = ?', [$agreement->ins_id]);
?>

<style>
    .violation_info {
        background: #f5f5f5;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .violation_info .info_row {
        margin: 5px 0;
        color: #666;
    }
    .violation_info .info_row strong {
        color: #333;
    }
    .status_badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: bold;
    }
    .status_pending { background: #ffecb3; color: #f57f17; }
    .status_check { background: #bbdefb; color: #1565c0; }
    .status_fixed { background: #c8e6c9; color: #2e7d32; }

    .existing_files { margin-top: 15px; }
    .file_item {
        display: flex;
        align-items: center;
        padding: 8px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 3px;
        margin-bottom: 5px;
    }
    .file_item .material-icons { margin-right: 10px; font-size: 18px; }
    .file_item .file_name { flex: 1; }
    .file_item .file_date { color: #999; font-size: 11px; margin-right: 10px; }
</style>

<div class="pop_up drag" style="width: 60vw; min-height: 50vh;">
    <div class="title handle">
        <div class="name">Загрузка подтверждающих документов</div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">

        <div class="violation_info">
            <h4 style="margin: 0 0 10px 0;">Информация о нарушении</h4>
            <div class="info_row">
                <strong>Учреждение:</strong> <?= htmlspecialchars($institution->name ?? 'Не указано') ?>
            </div>
            <div class="info_row">
                <strong>Предложение:</strong> <?= htmlspecialchars($violation['schedule_offers'] ?? '') ?>
            </div>
            <div class="info_row">
                <strong>Действия для устранения:</strong> <?= htmlspecialchars($violation['schedule_actions'] ?? '') ?>
            </div>
            <div class="info_row">
                <strong>Срок устранения:</strong>
                <?= htmlspecialchars($violation['deadline_extended'] ?? $violation['schedule_deadlines'] ?? '') ?>
            </div>
            <div class="info_row">
                <strong>Ответственный:</strong> <?= htmlspecialchars($violation['schedule_responsible'] ?? '') ?>
            </div>
            <div class="info_row">
                <strong>Текущий статус:</strong>
                <?php
                $statusLabels = [
                    0 => '<span class="status_badge status_pending">Не начато</span>',
                    1 => '<span class="status_badge status_check">На проверке</span>',
                    2 => '<span class="status_badge status_fixed">Устранено</span>'
                ];
                echo $statusLabels[intval($violation['fix_status'] ?? 0)] ?? 'Неизвестно';
                ?>
            </div>
        </div>

        <form method="post" id="upload_evidence_form" class="ajaxFrm" enctype="multipart/form-data">
            <input type="hidden" name="agreement_id" value="<?= $agreementId ?>">
            <input type="hidden" name="violation_index" value="<?= $violationIndex ?>">
            <input type="hidden" name="fix_status" value="1">

            <div class="group">
                <div class="item w_100">
                    <div class="el_data">
                        <label>Комментарий об устранении нарушения</label>
                        <textarea name="fix_comment" class="el_input" style="min-height: 100px;"><?= htmlspecialchars($violation['fix_comment'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="item w_100">
                    <div class="el_data">
                        <label>Подтверждающие документы</label>
                        <input type="file" name="evidence_files[]" class="el_input" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                        <div style="font-size: 11px; color: #999; margin-top: 5px;">
                            Поддерживаемые форматы: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG. Максимальный размер файла: 10 МБ
                        </div>
                    </div>
                </div>

                <?php if (!empty($violation['fix_files'])): ?>
                    <div class="item w_100">
                        <div class="el_data">
                            <label>Ранее загруженные документы</label>
                            <div class="existing_files">
                                <?php foreach ($violation['fix_files'] as $file): ?>
                                    <div class="file_item">
                                        <span class="material-icons">attach_file</span>
                                        <span class="file_name"><?= htmlspecialchars($file['filename'] ?? 'Файл') ?></span>
                                        <span class="file_date"><?= date('d.m.Y H:i', strtotime($file['uploaded_at'] ?? '')) ?></span>
                                        <a href="/core/ajaxHandlers/downloadFile.php?id=<?= $file['file_id'] ?>" target="_blank" title="Скачать">
                                            <span class="material-icons">download</span>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="group" style="margin-top: 20px;">
                <div class="item w_100" style="text-align: right;">
                    <div class="button positive" onclick="$('#upload_evidence_form').submit();">
                        <span class="material-icons">save</span>
                        <span>Загрузить документы</span>
                    </div>
                    <div class="button" onclick="el_app.dialog_close('upload_evidence');">
                        <span class="material-icons">close</span>
                        <span>Отмена</span>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>
