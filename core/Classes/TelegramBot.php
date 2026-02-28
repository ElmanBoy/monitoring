<?php


namespace Core;


class TelegramBot {

    private $api_key;
    private $base_url;

    public function __construct($api_key) {
        $this->api_key = $api_key;
        $this->base_url = "https://api.telegram.org/bot" . $api_key . "/";
    }

    // Метод для отправки сообщений
    public function sendMessage($chat_id, $text) {
        $url = $this->base_url . "sendMessage";
        $params = [
            'chat_id' => $chat_id,
            'text' => $text
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    // Метод для получения обновлений
    public function getUpdates($offset = null) {
        $url = $this->base_url . "getUpdates";
        if ($offset) {
            $url .= "?offset=" . $offset;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    // Метод для отправки изображений
    public function sendPhoto($chat_id, $photo_url, $caption = null) {
        $url = $this->base_url . "sendPhoto";
        $params = [
            'chat_id' => $chat_id,
            'photo' => $photo_url,
            'caption' => $caption
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}

/*
 * Вы можете использовать этот класс для отправки сообщений, получения обновлений и отправки изображений в Telegram.
 * Чтобы начать использовать класс, необходимо создать новый объект класса и передать в него ключ API вашего бота. Например:

$bot = new TelegramBot('YOUR_API_KEY');
После этого вы можете вызывать методы класса для взаимодействия с Telegram Bot API, например:


$bot->sendMessage($chat_id, 'Привет, это сообщение от бота!');
Обратите внимание, что в примере выше $chat_id - это идентификатор чата, куда будет отправлено сообщение.
Чтобы узнать идентификатор чата, можно воспользоваться методом getUpdates, который возвращает список последних обновлений.


зачем параметр $offset у метода getUpdates?
Параметр $offset у метода getUpdates используется для указания идентификатора последнего обновления,
которое было обработано ботом.

При вызове метода getUpdates Telegram API возвращает список последних обновлений, которые произошли в боте (например,
новое сообщение от пользователя). Каждое обновление имеет уникальный идентификатор.
Если вы указываете параметр $offset при вызове getUpdates, то Telegram API вернет только те обновления, чьи идентификаторы больше чем $offset.

Это позволяет боту получать только новые обновления, которые он еще не обработал, и игнорировать старые обновления.
Без использования $offset бот будет получать все обновления с самого начала, включая те, которые он уже обработал,
что может быть неэффективно и вызывать проблемы с производительностью.

Использование параметра $offset при вызове метода getUpdates позволяет оптимизировать работу бота и сократить
количество повторных обработок одних и тех же обновлений.


как получить идентификатор последнего обновления?
Для того, чтобы получить идентификатор последнего обновления в Telegram API, необходимо вызвать метод getUpdates и
извлечь идентификатор последнего обновления из объекта Update, который представляет последнее обновление.

Вот пример кода на PHP, который позволяет получить идентификатор последнего обновления:

// Создаем экземпляр Telegram Bot API
$telegramApi = new TelegramBot\Api\BotApi('YOUR_BOT_TOKEN');

// Вызываем метод getUpdates
$updates = $telegramApi->getUpdates();

// Если есть хотя бы одно обновление
if (!empty($updates)) {
    // Получаем последнее обновление
    $lastUpdate = end($updates);

    // Извлекаем идентификатор последнего обновления
    $lastUpdateId = $lastUpdate->getUpdateId();

    // Используем идентификатор последнего обновления для следующих запросов
    $nextOffset = $lastUpdateId + 1;
}
В этом примере мы используем метод end() для получения последнего элемента в массиве обновлений.
Затем мы вызываем метод getUpdateId() для извлечения идентификатора последнего обновления.
В конце мы используем этот идентификатор для установки следующего значения параметра $offset при вызове метода getUpdates.
 */