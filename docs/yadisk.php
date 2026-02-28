<?php
$token = 'AgAAAAA49MUQAAXnSt9JXtjcdUxmkHs8kh1jBKo';
/*
ID: acf2125014544e6ba4ce20e2037d309c
Пароль: 5a9a1f74336d49ff921c1e4339309867
Callback URL: https://oauth.yandex.ru/verification_code*/

// Выведем список корневой папки.
$path = '/Photo/Session/';

// Оставим только названия и тип.
$fields = '_embedded.items.name,_embedded.items.type';

$limit = 100;

$ch = curl_init('https://cloud-api.yandex.net/v1/disk/resources/files/?limit=100000&preview_size=XXXL&&fields=_embedded.items.path,_embedded.items.name&media_type=image');
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: OAuth ' . $token));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, false);
$res = curl_exec($ch);
curl_close($ch);

$res = json_decode($res, true);

$filesArray = array();
$filesList = "<?php\n\$ydImages = array(\n";
for($i = 0; $i < count($res['items']); $i++){
    $filesArray[] = "\t'".$res['items'][$i]['name']."' => '".$res['items'][$i]['path']."'";
    //echo '<img src="/images/showImage.php?path='.urlencode($res['items'][$i]['name']).'" alt="'.$res['items'][$i]['path'].'" style="max-width:200px;
    // float:left; margin:5px; display:inline;"><br>';
};
$filesList .= implode(",\n", $filesArray)."\n);\n?>";

file_put_contents($_SERVER['DOCUMENT_ROOT'].'/modules/ydFiles.php', $filesList);
file_put_contents($_SERVER['DOCUMENT_ROOT'].'/ydLog.txt', date('Y-m-d H:i:s')."\n", FILE_APPEND);
//print_r($filesArray);

?>