<?php
/*
 * HTTP Server Status:
Prefix:
Server: Asterisk
Server Enabled and Bound to 10.12.120.13:8088
Enabled URI's:
/httpstatus => Asterisk HTTP General Status
/phoneprov/... => Asterisk HTTP Phone Provisioning Tool
/amanager => HTML Manager Event Interface w/Digest authentication
/metrics/... => Prometheus Metrics URI
/arawman =›
Raw HTTP Manager Event Interface w/Digest authentication
/manager => HTML Manager Event Interface
/rawman => Raw HTTP Manager Event Interface
/amxml => XML Manager Event Interface w/Digest authentication
/mxml => XML Manager Event Interface
/ari/... => Asterisk RESTful API
/ws => Asterisk HTTP WebSocket
 */

function generateVoiceFile($text)
{
    // Установите API ключ Yandex SpeechKit
    $apiKey = 'AQVN2uvV43tcT8CtjA5lr_JsQ1ve-8k2PXSgP8Ev';
    $text = urlencode($text);
    $url = 'https://tts.api.cloud.yandex.net/speech/v1/tts';
    $headers = [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ];
    $data = [
        'text' => $text,
        'lang' => 'ru-RU',
        'emotion' => 'good',
        'speaker' => 'zahar',
        'format' => 'mp3',
        'sampleRateHertz' => 48000
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function getCallResultFromAsterisk($taskId)
{
    // Установите SIP канал для связи с Asterisk
    $sipChannel = 'SIP/your_sip_provider';

    // Составляем команду для получения результата обзвона
    $command = "asterisk -rx 'show channels'";
    $output = shell_exec($command);
    $channels = explode("\n", $output);
    foreach ($channels as $channel) {
        if (strpos($channel, 's@auto-dialout') !== false) {
            $channelId = substr($channel, strpos($channel, 'Channel: ') + 8);
            $result = getChannelResult($channelId);
            return $result;
        }
    }
    return null;
}

function getChannelResult($channelId)
{
    // Установите SIP канал для связи с Asterisk
    $sipChannel = 'SIP/your_sip_provider';

    // Составляем команду для получения результата канала
    $command = "asterisk -rx 'show channel $channelId'";
    $output = shell_exec($command);
    $result = json_decode($output, true);
    return $result;
}

function processCallResult($result) {
    // Установите путь к файлу модели Vosk
    $modelPath = 'path/to/model';

    // Установите путь к файлу голосового ответа
    $audioFilePath = 'path/to/audio';

    // Составляем команду для обработки голосового ответа
    $command = "vosk -m $modelPath -i $audioFilePath";
    $output = shell_exec($command);
    $transcript = trim($output);
    return $transcript;
}