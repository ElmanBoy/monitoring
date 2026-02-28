<?php
//echo json_decode(file_get_contents('https://ya.ru'));

$url = 'https://api.mymemory.translated.net/get';
$params = [
    'q' => 'подразделенеий',//$_POST['text'],
    'langpair' => 'ru|eng'
];
$requestUrl = $url . '?' . http_build_query($params);

// Инициализируем cURL
$ch = curl_init();

// Настраиваем параметры cURL
curl_setopt_array($ch, [
    CURLOPT_URL => $requestUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false, // Для теста (в продакшене лучше true)
    CURLOPT_TIMEOUT => 10
]);

// Выполняем запрос
$response = curl_exec($ch);

// Проверяем на ошибки
if (curl_errno($ch)) {
    throw new Exception('cURL error: ' . curl_error($ch));
}

// Закрываем соединение
curl_close($ch);

echo json_decode($response, true);