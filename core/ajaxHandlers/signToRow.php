<?php
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db();
$auth = new Auth();

$user_id = intval($_SESSION['user_id']);
$row_id = intval($_POST['row_id']);
$sign = json_encode($_POST['signature']);
$type = intval($_POST['type']);
$section = intval($_POST['section']);
$table = trim($_POST['source']);
$err = 0;
$result = false;
$message = '';
$sign_type = $type == 1 ? 'подписан' : 'согласован';
$created_at = date('Y-m-d H:i:s');
$dateTime = new DateTime($created_at);
$formattedDate = $dateTime->format('d.m.Y H:i');

preg_match('/CN=([^,]+)/', $_POST['signature']['certificate_info']['subject'], $matches);
$fullName = $matches[1] ?? null;

$user = $db->selectOne('users', " WHERE id = ?", [$user_id]);
$userFio = trim($user->surname).' '.trim($user->name).' '.trim($user->middle_name);

if($auth->isLogin()) {
    $exist = $db->selectOne("signs", " WHERE 
    user_id = $user_id AND doc_id = $row_id AND type = $type AND table_name = '$table'");
    if($exist->id > 0){
        $err++;
        $result = false;
        $message = 'Этот документ уже Вами '.$sign_type;
    }
    if(mb_strtolower($userFio) != mb_strtolower($fullName)){
        $err++;
        $result = false;
        $message = 'Не совпадают ФИО в ЭЦП с ФИО авторизованного сотрудника.<br>'.
            'Владелец ЭЦП - '.$fullName.'.<br> Вы авторизованы как '.$userFio;
    }
    if($err == 0) {
        $insert = [
            'author' => $user_id,
            'created_at' => $created_at,
            'doc_id' => $row_id,
            'user_id' => $user_id,
            'sign' => $sign,
            'type' => $type,
            'section' => $section,
            'table_name' => $table
        ];
        try {
            $doc = $db->insert('signs', $insert);
            $message = 'Документ '.$sign_type;
            $result = true;
        } catch (\RedBeanPHP\RedException $e) {
            $message = $e->getMessage();
            $result = false;
        }
    }

    echo json_encode(array(
        'result' => $result,
        'resultText' => $message,
        'date' => $formattedDate,
        'errorFields' => []));
}else{
    echo json_encode(array(
        'result' => false,
        'resultText' => '<script>alert("Ваша сессия устарела.");document.location.href = "/"</script>',
        'errorFields' => []));
}
