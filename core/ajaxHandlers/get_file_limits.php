<?php
header('Content-Type: application/json');

// Получаем настройки из php.ini
$maxFileSize = parse_size(ini_get('upload_max_filesize'));
$maxFileUploads = ini_get('max_file_uploads');

echo json_encode([
    'maxFileSize' => $maxFileSize,
    'maxFileUploads' => $maxFileUploads
]);

// Функция для преобразования размера из формата php.ini (например, 2M) в байты
function parse_size($size): float
{
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    }
    return round($size);
}
?>