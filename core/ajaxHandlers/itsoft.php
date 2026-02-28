<?php
$opts = [
    'http' => [
        'method' => 'GET',
        'header' => "Accept-encoding: gzip\r\n"
    ]
];

$context = stream_context_create($opts);
/*$content = gzdecode(file_get_contents('https://egrul.itsoft.ru/7730588444.xml', false, $context));
$xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOBLANKS);
$xml = json_decode(json_encode($xml), TRUE);
print_r($xml);*/

$content = file_get_contents('https://egrul.itsoft.ru/'.$_POST['inn'].'.json');
$json = json_decode($content, TRUE);
print_r($json);