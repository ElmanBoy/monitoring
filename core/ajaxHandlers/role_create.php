<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();
$permissions = array();

if(strlen(trim($_POST['name'])) == 0){
    $err++;
    $errStr[] = 'Укажите название роли';
    $errorFields[] = 'name';
}
if(count($_POST['modules']) == 0){
    $err++;
    $errStr[] = 'Укажите минимум один модуль';
}else{
    for($i = 0; $i < count($_POST['modules']); $i++){
        $permissions[$_POST['modules'][$i]] = array(
            'module' => $_POST['modules'][$i],
            'view' => ($_POST['view'.$i] == 'y'),
            'edit' => ($_POST['edit'.$i] == 'y'),
            'delete' => ($_POST['delete'.$i] == 'y')
        );
    }
}

if($err == 0) {
// Указываем, что будем работать с таблицей ohs_roles
    R::ext('xdispense', function( $type ){
        return R::getRedBean()->dispense( $type );
    });

    $role = R::xdispense('ohs_roles');
// Заполняем объект свойствами
    $role->active = 1;
    $role->name = $_POST['name'];
    $role->permissions = json_encode($permissions);
    $role->comment = $_POST['comment'];

// Сохраняем объект
    R::store($role);
    $result = true;
    $message = 'Роль успешно создана.<script>el_app.setMainContent("/roles");el_app.dialog_close("role_create");</script>';
}else{
    $message = '<strong>Ошибка:</strong><br> '.implode('<br>', $errStr);
}
echo json_encode(array(
    'result' => $result,
    'resultText' => $message,
    'errorFields' => $errorFields));
?>