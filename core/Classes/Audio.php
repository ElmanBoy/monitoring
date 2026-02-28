<?php


namespace Core;


class Audio
{

    /**
     * Audio constructor.
     */
    public function __construct()
    {

    }

    public function generateVoiceFile($text)
    {
        $token = 'AQVN0EIfNcRzWqoz8CqB09GrSITNj-yAJR53pyEx'; # Укажите IAM-токен.

        $url = 'https://tts.api.cloud.yandex.net/speech/v1/tts:synthesize';
        $headers = ['Authorization: Api-Key ' . $token];
        $post = array(
            'text' => $text,
            //'folderId' => $folderId,
            'lang' => 'ru-RU',
            'emotion' => 'good',
            'voice' => 'ermil',
            'speed' => 1,
            'format' => 'mp3',
            'sampleRateHertz' => '16000');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_PROXY, 'http://10.12.127.11:3128');
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        if ($post != false) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            print 'Error: ' . curl_error($ch);
        }
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
            $decodedResponse = json_decode($response, true);
            echo 'Error code: ' . $decodedResponse['error_code'] . "<br>\r\n";
            echo 'Error message: ' . $decodedResponse['error_message'] . "<br>\r\n";
        }
        curl_close($ch);
        return $response;
    }

    public function transcribeAudio($pathToAudio){
        shell_exec('. /var/www/html/core/python3.8/environments/vosk_env/bin/activate 2>&1');
        //sleep(2);
        shell_exec('cd /var/www/html/core/python3.8/environments/vosk_env 2>&1');
        $command = escapeshellcmd('vosk-transcriber -i ' . escapeshellarg($pathToAudio));
        $output = shell_exec($command . ' 2>&1');
        shell_exec('Deactivate 2>&1');
        return $output;
    }
}