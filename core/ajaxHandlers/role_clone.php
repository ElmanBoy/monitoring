<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$err = 0;
$errStr = array();
$result = false;
$errorFields = array();

if(count($_POST['role_id']) == 0){
    $err++;
    $errStr[] = "Не выбрана ни одна роль.";
}

if($err == 0){
    $ids = $_POST['role_id'];
    $roles = R::loadAll('ohs_roles', $ids);
    foreach($roles as $role){
        R::ext('xdispense', function( $type ){
            return R::getRedBean()->dispense( $type );
        });

        $new_role = R::xdispense('ohs_roles');
// Заполняем объект свойствами
        $new_role->active = $role->active;
        $new_role->name = $role->name;
        $new_role->permissions = $role->permissions;
        $new_role->comment = $role->comment;

// Сохраняем объект
        R::store($new_role);
    }
    $result = true;
    $message = 'Роли успешно клонированы.<script>el_app.setMainContent(\'/roles\');</script>';
}else{
    $message = '<strong>Ошибка:</strong>&nbsp; '.implode('<br>', $errStr);
}

echo json_encode(array(
    'result' => $result,
    'resultText' => $message,
    'errorFields' => $errorFields));
?>