<?php
use Core\Date;
use Core\Db;
use Core\Auth;
use Core\Notifications;
use Core\Files;
use Core\Registry;

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db = new Db();
$auth = new Auth();
$alert = new Notifications();
$files = new Files();
$reg = new Registry();
$date = new Date();

if ($auth->isLogin()) {

    if ($auth->checkAjax()) {

        $doc_id = intval($_POST['document_id']);
        $message = '';
        $fileIds = [];
        $err = 0;
        $errMsg = [];
        $data = [];

        if (isset($_FILES['files']) && count($_FILES['files']) > 0 && is_array($_POST['custom_names'])) {
            $existFilesIds = [];
            if ($doc_id > 0) {
                $fileExist = $db->selectOne('agreement', ' WHERE id = ?', [$doc_id]);
                if (!is_null($fileExist->file_ids)) {
                    $existFilesIds = is_string($fileExist->file_ids)
                        ? json_decode($fileExist->file_ids) : $fileExist->file_ids;
                }
            }
            $fileIds = $files->attachFiles($_FILES['files'], $_POST['custom_names']);
            if ($fileIds['result']) {
                if (!is_null($existFilesIds)) {
                    $data['files_ids'] = json_encode(array_merge($existFilesIds, $fileIds['ids']));
                } else {
                    $data['files_ids'] = json_encode($fileIds['ids']);
                }
                $data['author'] = $_SESSION['user_id'];
                $data['edited_at'] = date('Y-m-d H:i:s');
                if($db->update('agreement', $doc_id, $data)){
                    $errMsg[] = 'Файлы успешно сохранены!';
                    $reg->insertTaskLog($doc_id, 'Приложение файлов &laquo;'.
                        implode('&raquo;, &laquo;', $_POST['custom_names']).'&raquo;',
                        'calendar', 'ins_info');
                }else{
                    $err++;
                    $errMsg[] = 'Файлы не удалось записать в базу данных';
                }

            } else {
                $err++;
                $errMsg[] = $fileIds['message'];
            }
        }

        echo json_encode(array(
                'result' => $err == 0,
                'resultText' => implode('<br>', $errMsg) . '
                                <script>
                                //el_app.reloadMainContent();
                                el_app.dialog_close("ins_info");
                                </script>',
                'post' => $_POST,
                'errorFields' => [])
        );
    }
}