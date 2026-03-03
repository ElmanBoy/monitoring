<?php
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();
$permissions = array();

$db = new Db;
$auth = new Auth();

if($auth->checkAjax()) {

    if (strlen(trim($_POST['name'])) == 0) {
        $err++;
        $errStr[] = 'Укажите название роли';
        $errorFields[] = 'name';
    }
    if (count($_POST['modules']) == 0) {
        $err++;
        $errStr[] = 'Укажите минимум один модуль';
    } else {
        for ($i = 0; $i < count($_POST['modules']); $i++) {
            $permissions[$_POST['modules'][$i]] = array(
                'module' => $_POST['modules'][$i],
                'view' => ($_POST['view' . $i] == 'y'),
                'edit' => ($_POST['edit' . $i] == 'y'),
                'delete' => ($_POST['delete' . $i] == 'y')
            );
        }
    }

    if ($err == 0) {

        $role = array(
            'active' => 1,
            'name' => $_POST['name'],
            'permissions' => json_encode($permissions),
            'comment' => $_POST['comment']
        );
        $db->insert('roles', $role);

        $result = true;
        $message = 'Роль успешно создана.<script>el_app.reloadMainContent(); el_app.dialog_close("role_create");</script>';
    } else {
        $message = '<strong>Ошибка:</strong>&nbsp; ' . implode('<br>&nbsp; ', $errStr);
    }
    echo json_encode(array(
        'result' => $result,
        'resultText' => $message,
        'errorFields' => $errorFields));
}
?>