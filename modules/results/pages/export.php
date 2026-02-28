<?php
require_once 'config.php';

$reportId = $_GET['id'] ?? 0;
$format = $_GET['format'] ?? 'pdf';

// Получаем данные отчета
$report = R::load('report', $reportId);
if (!$report->id) {
    die('Отчет не найден');
}

// Получаем поля отчета
$fields = R::find('report_field',
    ' template_id = ? AND is_visible = TRUE ORDER BY display_order',
    [$report->template_id]
);

// Получаем данные отчета
$data = [];
switch ($report->template_id) {
    case 1: // Отчет по проверкам
        $data = R::getAll('
            SELECT 
                i.name AS institution_name,
                it.name AS inspection_type,
                ins.start_date, ins.end_date, ins.inspector_name, ins.result,
                (SELECT COUNT(*) FROM violation v WHERE v.inspection_id = ins.id) AS violations_count
            FROM inspection ins
            JOIN institution i ON ins.institution_id = i.id
            LEFT JOIN inspection_type it ON ins.type_id = it.id
            ORDER BY ins.start_date DESC
        '
        );
        break;
    // Другие типы отчетов...
}

// Генерация PDF
if ($format == 'pdf') {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);

    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . html($report->name) . '</title>
        <style>
            body { font-family: DejaVu Sans, sans-serif; }
            h1 { text-align: center; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h1>' . html($report->name) . '</h1>
        <p>Дата формирования: ' . date('d.m.Y H:i') . '</p>
        <table>
            <thead><tr>';

    // Заголовки таблицы
    foreach ($fields as $field) {
        $html .= '<th>' . html($field->display_name) . '</th>';
    }

    $html .= '</tr></thead><tbody>';

    // Данные таблицы
    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($fields as $field) {
            $html .= '<td>' . html($row[$field->field_name] ?? '') . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table></body></html>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    // Сохранение файла
    $output = $dompdf->output();
    $filename = 'reports/report_' . $report->id . '_' . date('Ymd_His') . '.pdf';
    file_put_contents($filename, $output);

    // Отправка файла пользователю
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    readfile($filename);
    exit;
}

// Генерация Excel (используем PHPOffice/PhpSpreadsheet)
if ($format == 'xlsx') {
    require 'vendor/autoload.php';
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Заголовки
    $col = 1;
    foreach ($fields as $field) {
        $sheet->setCellValueByColumnAndRow($col++, 1, $field->display_name);
    }

    // Данные
    $row = 2;
    foreach ($data as $item) {
        $col = 1;
        foreach ($fields as $field) {
            $sheet->setCellValueByColumnAndRow($col++, $row, $item[$field->field_name] ?? '');
        }
        $row++;
    }

    // Генерация файла
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $filename = 'reports/report_' . $report->id . '_' . date('Ymd_His') . '.xlsx';
    $writer->save($filename);

    // Отправка файла пользователю
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    readfile($filename);
    exit;
}