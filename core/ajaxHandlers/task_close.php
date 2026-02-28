<?php
use Core\Registry;
session_start();
if(isset($_SESSION['login'])) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
    $reg = new Registry();
    $task_id = intval($_POST['task_id']);
    $action = $_POST['log_action'] ?? 'Закрытие окна задачи';
    $module = $_POST['module'] ?? 'assigned';
    $form_id = $_POST['form_id'] ?? 'view_task';
    $reg->insertTaskLog($task_id, $action, $module, $form_id);
}