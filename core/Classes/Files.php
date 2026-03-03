<?php


namespace Core;


use Exception;

class Files
{
    /**
     * @var string[]
     */
    private array $allowedTypes;

    public function __construct()
    {
        $this->allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/tiff',
            'application/zip',
            'application/x-rar-compressed',
            'audio/mpeg',
            'video/quicktime',
            'video/mp4',
            'video/x-msvideo',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain'
        ];

    }

    public function checkAllowedType($mime_type): bool
    {
        return in_array($mime_type, $this->allowedTypes);
    }

    /**
     * Определяет MIME-тип по расширению файла (резервный метод)
     */
    public function getMimeTypeByExtension(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $mimeTypes = [
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // изображения
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',

            // архивы
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // аудио/видео
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'wmv' => 'video/x-ms-wmv',

            // документы
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Форматирует размер файла в человеко-читаемый вид
     */
    public function formatFileSize(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Безопасная проверка загруженного файла через $_FILES
     *
     * @param array $fileData Элемент массива $_FILES
     * @return array Информация о файле
     * @throws Exception Если файл невалиден
     */
    public function checkUploadedFile(array $fileData): array
    {
        // Проверяем ошибки загрузки
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadErrorText($fileData['error']));
        }

        // Проверяем, что файл был загружен через HTTP POST
        if (!is_uploaded_file($fileData['tmp_name'])) {
            throw new Exception('Файл не был загружен через HTTP POST');
        }

        // Получаем информацию о файле
        $fileInfo = $this->getFileInfo($fileData['tmp_name']);

        // Дополнительная проверка MIME-типа
        if ($fileData['type'] !== $fileInfo['mime_type']) {
            // Логируем несоответствие, но используем определенный сервером тип
            error_log("MIME type mismatch: declared {$fileData['type']}, detected {$fileInfo['mime_type']}");
        }

        return array_merge($fileInfo, [
                'original_name' => $fileData['name'],
                'tmp_name' => $fileData['tmp_name'],
                'upload_error' => $fileData['error']
            ]
        );
    }

    /**
     * Возвращает текстовое описание ошибки загрузки
     */
    function getUploadErrorText($errorCode): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Размер файла превышает разрешенный директивой upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'Размер файла превышает указанный в форме MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'Файл был загружен только частично',
            UPLOAD_ERR_NO_FILE => 'Файл не был загружен',
            UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
            UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск',
            UPLOAD_ERR_EXTENSION => 'PHP-расширение остановило загрузку файла'
        ];

        return $errors[$errorCode] ?? "Неизвестная ошибка загрузки: $errorCode";
    }

    /**
     * @throws \Exception
     */
    public function safeGenerateFilename(string $originalFilename): string
    {
        // Получаем и проверяем расширение
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $extension = strtolower($extension);

        // Генерируем UUID
        if (function_exists('uuid_create')) {
            return uuid_create(UUID_TYPE_RANDOM) . '.' . $extension;
        }

        // Fallback
        return bin2hex(random_bytes(16)) . '.' . $extension;
    }

    /**
     * Безопасная отдача файла с поддержкой докачки
     */
    function downloadFile($fileId)
    {
        try {
            // Получаем информацию о файле из БД
            $db = new Db();
            $file = $db->selectOne('files', ' WHERE id = ?', [$fileId]);

            if (!$file) {
                throw new Exception('Файл не найден');
            }

            // Полный путь к файлу (ВНЕ webroot)
            $filePath = $file->file_path . '/' . $file->system_filename;

            // Проверяем существование файла
            if (!file_exists($filePath)) {
                throw new Exception('Файл не существует на сервере');
            }

            // Проверяем права доступа
            if (!isset($_SESSION['login'])) {
                throw new Exception('Доступ запрещен');
            }

            // Получаем актуальный размер файла (на случай если файл изменился)
            $fileSize = filesize($filePath);
            $fileTime = filemtime($filePath);


            // Устанавливаем общие заголовки
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $file->mime_type);
            header('Content-Disposition: attachment; filename="' . rawurlencode($file->original_filename) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $fileTime) . ' GMT');
            header('Accept-Ranges: bytes');

            // Обрабатываем докачку
            if (isset($_SERVER['HTTP_RANGE'])) {
                $this->handlePartialDownload($filePath, $fileSize);
            } else {
                // Полная загрузка файла
                header('Content-Length: ' . $fileSize);
                readfile($filePath);
            }

            exit;

        } catch (Exception $e) {
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
            echo '<h1>Ошибка</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
            exit;
        }
    }

    /**
     * Обработка частичной загрузки (докачки)
     */
    function handlePartialDownload($filePath, $fileSize)
    {
        // Парсим диапазон bytes=start-end
        list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);

        if ($size_unit != 'bytes') {
            http_response_code(416);
            header('Content-Range: bytes */' . $fileSize);
            throw new Exception('Неверный диапазон');
        }

        // Может быть несколько диапазонов, но мы обрабатываем только один
        list($range, $extra_ranges) = explode(',', $range_orig, 2);
        list($start, $end) = explode('-', $range, 2);

        $start = trim($start);
        $end = trim($end);

        // Устанавливаем начальную и конечную позиции
        if ($start === '') {
            // Если start не указан, начинаем с конца файла
            $start = max(0, $fileSize - $end);
            $end = $fileSize - 1;
        } elseif ($end === '' || $end > $fileSize - 1) {
            // Если end не указан или больше размера файла
            $end = $fileSize - 1;
        }

        $start = (int)$start;
        $end = (int)$end;

        // Проверяем валидность диапазона
        if ($start > $end || $start >= $fileSize || $end >= $fileSize) {
            http_response_code(416);
            header('Content-Range: bytes */' . $fileSize);
            throw new Exception('Неверный диапазон');
        }

        $length = $end - $start + 1;

        // Устанавливаем заголовки для частичной загрузки
        http_response_code(206);
        header('Content-Length: ' . $length);
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);

        // Читаем и отправляем часть файла
        $fp = fopen($filePath, 'rb');
        if ($fp) {
            fseek($fp, $start);
            $buffer = 1024 * 8;
            $bytesSent = 0;

            while (!feof($fp) && $bytesSent < $length && connection_status() == 0) {
                $bytesToRead = min($buffer, $length - $bytesSent);
                $data = fread($fp, $bytesToRead);
                echo $data;
                flush();
                $bytesSent += $bytesToRead;
            }

            fclose($fp);
        }
    }

    /**
     * Определяет MIME-тип и размер файла
     *
     * @param string $filePath Путь к файлу
     * @return array Массив с информацией о файле
     * @throws Exception Если файл не существует или недоступен
     */
    public function getFileInfo(string $filePath): array
    {
        clearstatcache(true, $filePath); // Очищаем кэш статуса файла

        if (!file_exists($filePath)) {
            throw new Exception('Файл не существует: ' . $filePath);
        }

        if (!is_readable($filePath)) {
            throw new Exception('Файл недоступен для чтения: ' . $filePath);
        }

        $fileSize = filesize($filePath);

        // Пытаемся определить MIME-тип несколькими способами
        $mimeType = null;

        // 1. Используем finfo (самый надежный способ)
        if (extension_loaded('fileinfo')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
        }

        // 2. Если finfo не доступен, используем mime_content_type
        if (!$mimeType && function_exists('mime_content_type')) {
            $mimeType = mime_content_type($filePath);
        }

        // 3. Fallback: используем расширение файла (менее надежно)
        if (!$mimeType) {
            $mimeType = $this->getMimeTypeByExtension($filePath);
        }

        return [
            'mime_type' => $mimeType,
            'size' => $fileSize,
            'size_human' => $this->formatFileSize($fileSize),
            'filename' => basename($filePath)
        ];
    }

    /**
     * @throws \Exception
     */
    public function attachFiles(array $files, array $custom_names): array
    {
        $userDir = $_SERVER['DOCUMENT_ROOT'] . '/files/' . $_SESSION['user_id'];
        $userYearDir = $userDir . '/' . date('Y');
        $userYearMonthDir = $userYearDir . '/' . date('m');
        $err = 0;
        $message = [];
        $ids = [];
        $result = false;

        if (is_array($files) && count($files) > 0) {
            if (!is_dir($userDir)) {
                mkdir($userDir, 0755);
            }
            if (!is_dir($userYearDir)) {
                mkdir($userYearDir, 0755);
            }
            if (!is_dir($userYearMonthDir)) {
                mkdir($userYearMonthDir, 0755);
            }

            for ($c = 0; $c < count($files['name']); $c++) {
                $err = 0;
                $fileInfo = $this->getFileInfo($files['tmp_name'][$c]);
                $newFileName = $this->safeGenerateFilename($files['name'][$c]);

                // Проверка размера (макс. 50 МБ)
                $maxSize = 50 * 1024 * 1024;
                if ($fileInfo['size'] > $maxSize) {
                    $err++;
                    $message[] = 'Файл &laquo;' . $files['name'][$c] . '&raquo; превышает максимально допустимый размер (50 МБ)';
                }

                if (!$this->checkAllowedType($fileInfo['mime_type'])) {
                    $err++;
                    $message[] = 'Недопустимый тип &laquo;' . $fileInfo['mime_type'] . '&raquo; у файла &laquo;' . $files['name'][$c] . '&raquo;';
                }
                if ($err == 0 && !move_uploaded_file($files['tmp_name'][$c], $userYearMonthDir . '/' . $newFileName)) {
                    $err++;
                    $message[] = 'Не удалось переместить файл &laquo;' . $files['name'][$c] . '&raquo;';
                }

                if ($err == 0) {
                    try {

                        /*echo 'MIME-type: ' . $fileInfo['mime_type'] . "\n";
                        echo 'Размер: ' . $fileInfo['size_human'] . "\n";
                        echo 'Размер в байтах: ' . $fileInfo['size'] . "\n";*/

                        $fileData = [
                            'userid' => $_SESSION['user_id'],
                            'author' => $_SESSION['user_id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'original_filename' => $files['name'][$c],
                            'system_filename' => $newFileName,
                            'name' => $custom_names[$c],
                            'file_path' => $userYearMonthDir,
                            'mime_type' => $fileInfo['mime_type'],
                            'size' => $fileInfo['size']
                        ];

                        $db = new Db();
                        $db->insert('files', $fileData);
                        $ids[] = $db->last_insert_id;
                        $message[] = $files['name'][$c];
                        $result = true;

                    } catch (Exception $e) {
                        $message[] = 'Ошибка: ' . $e->getMessage();
                        $result = false;
                    }
                }
            }
        }
        return ['result' => $result, 'message' => $message, 'ids' => $ids];
    }

    public function getAttachedFiles(?array $ids): array
    {
        $out = [];
        if (is_array($ids) && count($ids) > 0) {
            $db = new Db();
            $ids = array_map('intval', $ids);
            $slots = $db->db::genSlots($ids);
            $files = $db->db::getAll('SELECT * FROM ' . TBL_PREFIX . "files WHERE id IN ($slots)", $ids);

            foreach ($files as $file) {
                $out[] = [
                    'name' => $file['name'],
                    'id' => $file['id'],
                    'file' => $file['original_filename'],
                    'file_path' => $file['file_path'],
                    'system_filename' => $file['system_filename'],
                    'original_filename' => $file['original_filename'],
                    'author' => $file['author']
                ];
            }
        }
        return $out;
    }

    public function deleteFile(int $file_id): array
    {
        $db = new Db();
        $auth = new Auth();
        $exist = $this->getAttachedFiles([$file_id])[0];
        $result = [];

        if ($exist['author'] == $_SESSION['user_id'] || $auth->isAdmin()) {
            $db->delete('files', [$file_id]);
            $filPath = $exist['file_path'] . '/' . $exist['system_filename'];
            if ($filPath != '/') {
                if (!unlink($filPath)) {
                    $result['result'] = false;
                    $result['resultText'] = 'Не удалось удалить файл. Возможно, недостаточно прав для этой операции.';
                } else {
                    $result['result'] = true;
                    $result['resultText'] = 'Файл &laquo;' . $exist['original_filename'] . '&raquo; удален.';
                }
            }

        } else {
            $result['result'] = false;
            $result['resultText'] = 'У Вас недостаточно прав для удаления этого файла';
        }
        return $result;
    }
}