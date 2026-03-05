<?php


namespace Core;

use Core\Registry;
use Core\Db;
use Core\Date;
use Core\Mail;
use Core\Gui;
use Exception;
use R;
use RedBeanPHP\RedException\SQL;
use RedBeanPHP\RedException;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;


class Notifications
{
    private function remindLog(string $msg): void
    {
        $logDir = $_SERVER['DOCUMENT_ROOT'] . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }
        file_put_contents($logDir . '/reminders.log', '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND);
    }
    /**
     * @var array
     */
    private $_get;
    /**
     * @var array
     */
    private $_post;
    /**
     * @var array
     */
    private $_session;
    /**
     * @var array
     */
    private $_server;
    /**
     * @var array
     */
    private $_cookie;
    /**
     * @var Registry
     */
    private $reg;
    /**
     * @var Db
     */
    private $db;
    /**
     * @var Date
     */
    private $date;
    /**
     * @var \Core\Mail
     */
    private $mail;
    /**
     * @var R
     */
    private $rb;
    /**
     * @var \Core\Gui
     */
    private $gui;


    public function __construct()
    {
        $this->_get = $_GET;
        $this->_post = $_POST;
        $this->_session = $_SESSION;
        $this->_server = $_SERVER;
        $this->_cookie = $_COOKIE;
        $this->reg = new Registry();
        $this->db = new Db();
        $this->rb = new R();
        $this->date = new Date();
        $this->mail = new Mail();
        //$this->gui = new \Core\Gui();
    }

    public function getRecordsToPanel(int $user_id): array
    {
        $out = [];
        $countTotal = 0;
        $countUnseen = 0;
        $countTitle = '';

        $records = $this->db->select('notifications', " WHERE user_id = $user_id ORDER BY created_at DESC");

        foreach ($records as $record) {
            $out[] = '<div class="notificationItem' . ($record->viewed == '1' ? ' viewed' : '') . '" data-id="' .
                $record->task_id . '" data-path="' . $record->path . '">' .
                '<div class="deleteNotification" data-id="' . $record->id . '"><span class="material-icons">close</span></div>' .
                '<span class="notificationDate">' . $this->date->dateToString($record->created_at) . '</span><br> ' .
                $record->message . '</div>';
            if ($record->viewed == '0') {
                $countUnseen++;
            }
            $countTotal++;
        }
        $gui = new \Core\Gui();
        if ($countTotal > 0) {
            $countTitle = 'Всего ' . $countUnseen . ' непрочитанн' . $gui->postfix($countUnseen, 'ое', 'ых', 'ых') .
                ' уведомлени' . $gui->postfix($countUnseen, 'е', 'я', 'й');
        }
        if ($countUnseen > 0) {
            $countTitle = $countUnseen . ' нов' . $gui->postfix($countUnseen, 'ое', 'ых', 'ых') . ' уведомлени' .
                $gui->postfix($countUnseen, 'е', 'я', 'й');
        }

        return [
            'countTotal' => $countUnseen,
            'countUnseens' => $countUnseen,
            'countTitle' => $countTitle,
            'messages' => implode("\n", $out)
        ];
    }

    public function addRecordToPanel(int $user_id, string $message, int $taskId, string $path)
    {
        $record = [
            'created_at' => date('Y-m-d H:i:s'),
            'message' => $message,
            'user_id' => $user_id,
            'task_id' => $taskId,
            'path' => $path,
            'viewed' => 0
        ];
        try {
            $this->db->insert('notifications', $record);
            $this->deleteOldRecords($user_id);
        } catch (RedException $e) {
            echo $e->getMessage();
        }
    }

    public function setRecordViewed(int $id)
    {
        $this->db->update('notifications', $id, ['viewed' => 1]);
    }

    public function deleteOldRecords(int $user_id)
    {
        $this->rb::exec('DELETE FROM ' . TBL_PREFIX . "notifications WHERE user_id = '$user_id' AND created_at < NOW() - INTERVAL '1 month'");
    }

    public function deleteRecordById(int $user_id, int $record_id)
    {
        $this->rb::exec('DELETE FROM ' . TBL_PREFIX . "notifications WHERE user_id = '$user_id' AND id = '$record_id'");
    }

    public function setRecordViewedByTaskId(int $user_id, int $task_id)
    {
        $this->rb::exec('UPDATE ' . TBL_PREFIX . "notifications SET viewed = '1' WHERE user_id = '$user_id' AND task_id = '$task_id'");
    }

    public function removeRemind(int $remind_id)
    {
        $task = $this->db->selectOne('reminders', ' WHERE id = ?', [$remind_id]);
        $task_id = $task->task_id;
        $this->db->update('checkstaff', $task_id, ['allowremind' => 0]);
        $this->db->delete('reminders', [$remind_id]);

    }

    /**
     * @throws \RedBeanPHP\RedException
     */
    public function setRemind(
        int $author,
        int $taskId,
        int $executorId,
        string $remindDateTime,
        string $message,
        string $url,
        string $caption,
        ?string $remindComment,
        ?string $executorEmail,
        ?string $executorFIO,
        ?string $letterText
    )
    {
        $rem = [
            'author' => $author,
            'created_at' => date('Y-m-d H:i:s'),
            'task_id' => $taskId,
            'employee' => $executorId,
            'datetime' => $remindDateTime,
            'comment' => $remindComment,
            'email' => $executorEmail,
            'letter' => $letterText,
            'message' => $message,
            'url' => $url,
            'caption' => $caption
        ];
        $exist = $this->db->selectOne('reminders', ' WHERE task_id = ? AND employee = ?', [$taskId, $executorId]);
        try {
            if ($exist !== null && $exist->id) {
                $this->db->update('reminders', $exist->id, $rem);
                $this->remindLog('setRemind: UPDATE id=' . $exist->id . ' task_id=' . $taskId . ' employee=' . $executorId);
            } else {
                $this->remindLog('setRemind: before INSERT rem=' . json_encode($rem, JSON_UNESCAPED_UNICODE));
                $insertResult = $this->db->insert('reminders', $rem);
                $this->remindLog('setRemind: INSERT result=' . json_encode($insertResult) . ' last_id=' . $this->db->last_insert_id);
            }
        } catch (\Exception $e) {
            $this->remindLog('setRemind ERROR: ' . $e->getMessage() . ' | rem=' . json_encode($rem, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * @throws \ErrorException
     */
    public function sendingRemind(int $remindId): bool
    {
        $result = false;
        $remind = $this->db->selectOne('reminders', ' WHERE id = ?', [$remindId]);
        $user = $this->db->selectOne('users', ' WHERE id = ?', [$remind->employee]);
        $executorEmail = $remind->email;
        $letterText = $remind->letter;
        $taskId = $remind->task_id;
        $clickUrl = $remind->url;//'https://monitoring.msr.mosreg.ru/assigned/?task_id=' . $taskId;
        $remindComment = stripslashes(htmlspecialchars($remind->comment));
        $caption = '⏰ ' . $remind->caption;
        $executorId = $user->id;
        $executorFIO = trim($user->surname) . ' ' . trim($user->name) . ' ' . trim($user->middle_name);
        $executorMessage = $letterText .
            (strlen($remindComment) > 0 ? '<p><strong>Комментарий: </strong>' . $remindComment . '</p>' : '') .
            '<p>&nbsp;</p><p><a href="' . $clickUrl . '">Просмотреть задачу</a></p>';

        $caption = strip_tags(str_replace(['<br>', '</br>'], "\n", $caption));
        $pushMessage = $remind->message;
        $this->sendWSMessage(json_encode(['userId' => $executorId, 'title' => $caption, 'body' => $pushMessage, 'url' => $clickUrl]));
        //$this->sendWebPush($executorId, 'PUSH: '.$caption, $pushMessage);

        if (strlen($executorEmail) > 0) {

            $letter_body = $this->mail->template_render('/tmpl/letter/task_letter.php',
                [
                    'email' => $executorEmail,
                    'greeting' => 'Здравствуйте, ' . $executorFIO . '!',
                    'caption' => $caption,
                    'task_id' => $taskId,
                    'text' => $letterText .
                        (strlen($remindComment) > 0 ? '<p><strong>Комментарий: </strong>' . $remindComment . '</p>' : '') .
                        '<p>&nbsp;</p><p><a href="' . $clickUrl . '">Просмотреть задачу</a></p>'
                ]
            );
            $result = $this->mail->send($executorEmail, $caption,
                $letter_body, 'noreply@mosreg.ru', 'html', 'smtp', '', 'noreply@mosreg.ru'
            );
            //if ($result) {
            $this->removeRemind($remindId);
            //}

        }
        return $result;
    }

    /**
     * @throws \RedBeanPHP\RedException
     */
    public function notificationTask(
        int $appointedId,
        int $executorId,
        int $taskId,
        string $mode = 'new',
        bool $allowRemind = false,
        ?string $remindDateTime = '',
        ?string $remindComment = ''
    ): bool
    {
        $user = $this->db->getRegistry('users', " WHERE id = $appointedId OR id = $executorId");
        //$sheets = $this->db->getRegistry('checklists');
        $inspections = $this->db->getRegistry('subject');
        $appointed = $user['result'][$appointedId];
        $appointedFIO = trim($appointed->surname) . ' ' . trim($appointed->name) . ' ' . trim($appointed->middle_name);
        $executor = $user['result'][$executorId];
        $executorFIO = trim($executor->surname) . ' ' . trim($executor->name) . ' ' . trim($executor->middle_name);
        $executorEmail = $executor->email;
        $inspection = '';


        // Напоминание сохраняется независимо от наличия email
        if ($allowRemind) {
            $finalRemindDateTime = (strlen(trim($remindDateTime)) > 0)
                ? $remindDateTime
                : date('Y-m-d H:i:s', strtotime('+1 day'));
            $this->setRemind(
                $appointedId,
                $taskId,
                $executorId,
                $finalRemindDateTime,
                'Кликните по уведомлению для просмотра задачи',
                'https://monitoring.msr.mosreg.ru/assigned?open_dialog=' . $taskId,
                'Напоминание о задаче № ' . $taskId,
                $remindComment,
                $executor->email ?? '',
                $executorFIO,
                ''  // letterText будет заполнен ниже если есть email
            );
        }

        if (strlen(trim($executorEmail)) > 0) {
            $task = $this->db->selectOne('checkstaff', ' WHERE id = ' . $taskId);
            $insId = intval($task->institution);
            $ins = $this->db->getRegistry('institutions', ' WHERE id = ' . $insId);


            if ($task->object_type == 0) {
                $ins = $this->db->getRegistry('persons', '', [], ['surname', 'first_name', 'middle_name', 'birth']);
                $object = stripslashes(htmlspecialchars($ins['array'][$insId][0])) . ' ' .
                    stripslashes(htmlspecialchars($ins['array'][$insId][1])) . ' ' .
                    stripslashes(htmlspecialchars($ins['array'][$insId][2])) . ' ' .
                    (strlen(trim($ins['array'][$insId][3])) > 0 ?
                        $this->date->correctDateFormatFromMysql($ins['array'][$insId][3]) : '');
            } else {
                $object = stripslashes(htmlspecialchars($ins['array'][$insId][0]));
            }

            $dateArr = explode(' - ', $task->dates);
            $dateStart = $this->date->correctDateFormatFromMysql($dateArr[0]);
            $dateEnd = $this->date->correctDateFormatFromMysql($dateArr[1]);
            $dates = 'с ' . $this->date->dateToString($dateStart) . ' по ' . $this->date->dateToString($dateEnd);

            $taskInfo = $this->db->selectOne('tasks', 'WHERE id = ?', [$task->task_id]);
            $inspectArr = [];
            $subjectArr = json_decode($taskInfo->subject);
            if (is_array($subjectArr) && count($subjectArr) > 0) {
                foreach (json_decode($taskInfo->subject) as $sub) {
                    $inspectArr[] = $inspections['array'][$sub];
                }
                $inspection = '<ul><li>' . implode('</li><li>', $inspectArr) . '</li></ul>';
            }

            if ($mode == 'new') {
                $caption = 'Новая задача на проверку № ' . $taskId . '';
                $letterText =
                    '<p><strong>Задачу назначил:</strong> ' . $appointedFIO . '</p>' .
                    '<p><strong>Объект проверки: </strong>' . $object . '</p>' .
                    (strlen($ins['result'][$task->institution]->location) > 0 ?
                        '<p><strong>Адрес объекта: </strong>' . $ins['result'][$task->institution]->location . '</p>' : '') .
                    '<p><strong>Период проверки: </strong>' . $dates . '</p>' .
                    '<p><strong>Предмет проверки: </strong>' . $inspection . '</p>';
            } else {
                $caption = 'Изменена задача на проверку № ' . $taskId . '';
                $letterText =
                    '<p><strong>Задачу изменил: </strong>' . $appointedFIO . '</p>' .
                    '<p><strong>Объект проверки: </strong>' . $object . '</p>' .
                    (strlen($ins['result'][$task->institution]->location) > 0 ?
                        '<p><strong>Адрес объекта: </strong>' . $ins['result'][$task->institution]->location . '</p>' : '') .
                    '<p><strong>Период проверки: </strong>' . $dates . '</p>' .
                    (strlen($inspection) > 0 ? '<p><strong>Предмет проверки: </strong>' . $inspection . '</p>' : '');
            }

            // Обновляем letter в записи напоминания теперь когда letterText готов
            if ($allowRemind) {
                $exist = $this->db->selectOne('reminders', ' WHERE task_id = ? AND employee = ?', [$taskId, $executorId]);
                if ($exist !== null && $exist->id) {
                    $this->db->update('reminders', $exist->id, ['letter' => $letterText]);
                }
            }

            // Удаление remind_id выполняется в вызывающем коде (check_staff.php) до вызова notificationTask

            $this->addRecordToPanel($executorId, $letterText, $taskId, '/assigned');

            $letter_body = $this->mail->template_render('/tmpl/letter/task_letter.php',
                [
                    'email' => $executorEmail,
                    'greeting' => 'Здравствуйте, ' . $executorFIO . '!',
                    'caption' => $caption,
                    'task_id' => $taskId,
                    'text' => $letterText . '<p>&nbsp;</p><p><a href="https://monitoring.msr.mosreg.ru/assigned?open_dialog=' . $taskId . '">Просмотреть задачу</a></p>'
                ]
            );
            return $this->mail->send($executorEmail, $caption,
                $letter_body, 'noreply@mosreg.ru', 'html', 'smtp', '', 'noreply@mosreg.ru'
            );
        } else {
            return false;
        }
    }

    /**
     * @throws \RedBeanPHP\RedException
     */
    public function notificationSigner(
        int $signerId,
        int $agreementType,
        int $documentId,
        string $documentName,
        string $mode = 'new',
        bool $allowRemind = true,
        ?string $remindDateTime = '',
        ?string $remindComment = ''
    ): bool
    {
        $user = $this->db->getRegistry('users', " WHERE id = $signerId");
        $executor = $user['result'][$signerId];
        $executorFIO = trim($executor->surname) . ' ' . trim($executor->name) . ' ' . trim($executor->middle_name);
        $executorEmail = $executor->email;
        $agreementAction = $agreementType == 1 ? 'Ваша подпись' : 'Ваше согласование';


        if (strlen(trim($executorEmail)) > 0) {

            $caption = 'Вы включены в список согласования';
            $letterText =
                '<p>Ожидается ' . $agreementAction . ' для документа «' . $documentName . '»</p>';


            if ($allowRemind) {
                $this->setRemind(
                    $signerId,
                    $documentId,
                    $signerId,
                    date('Y-m-d H:i:s'),
                    strip_tags($letterText),
                    'https://monitoring.msr.mosreg.ru/documents?open_dialog=' . $documentId,
                    $caption,
                    $remindComment,
                    $executorEmail,
                    $executorFIO,
                    $letterText
                );
            }

            // Удаление remind выполняется в вызывающем коде до вызова этого метода

            $this->addRecordToPanel($signerId, $letterText, $documentId, '/documents');

            $letter_body = $this->mail->template_render('/tmpl/letter/task_letter.php',
                [
                    'email' => $executorEmail,
                    'greeting' => 'Здравствуйте, ' . $executorFIO . '!',
                    'caption' => $caption,
                    'task_id' => $documentId,
                    'text' => $letterText . '<p>&nbsp;</p><p><a href="https://monitoring.msr.mosreg.ru/documents?open_dialog=' . $documentId . '">Открыть лист согласования</a></p>'
                ]
            );
            return $this->mail->send($executorEmail, $caption,
                $letter_body, 'noreply@mosreg.ru', 'html', 'smtp', '', 'noreply@mosreg.ru'
            );
        } else {
            return false;
        }
    }

    /**
     * @throws \RedBeanPHP\RedException
     */
    public function notificationOrder(
        int $signerId,
        int $documentId,
        string $documentName,
        bool $allowRemind = true,
        ?string $remindComment = 'Необходимо назначить задания сотрудникам группы проверки'
    ): bool
    {
        $user = $this->db->getRegistry('users', " WHERE id = $signerId");
        $executor = $user['result'][$signerId];
        $executorFIO = trim($executor->surname) . ' ' . trim($executor->name) . ' ' . trim($executor->middle_name);
        $executorEmail = $executor->email;


        if (strlen(trim($executorEmail)) > 0) {

            $caption = 'Подписан приказ о проведении проверки';
            $letterText =
                '<p>Вы назначены руководителем проверки в приказе «' . $documentName . '»</p>';


            if ($allowRemind) {
                $this->setRemind(
                    $signerId,
                    $documentId,
                    $signerId,
                    date('Y-m-d H:i:s'),
                    strip_tags($letterText),
                    'https://monitoring.msr.mosreg.ru/documents?module=documents&mode=planPdf&open_dialog={"docId":' . $documentId . '}',
                    $caption,
                    $remindComment,
                    $executorEmail,
                    $executorFIO,
                    $letterText
                );
            }

            // Удаление remind выполняется в вызывающем коде до вызова этого метода

            $this->addRecordToPanel($signerId, $letterText, $documentId, '/documents');

            $letter_body = $this->mail->template_render('/tmpl/letter/task_letter.php',
                [
                    'email' => $executorEmail,
                    'greeting' => 'Здравствуйте, ' . $executorFIO . '!',
                    'caption' => $caption,
                    'task_id' => $documentId,
                    'text' => $letterText . '<p>&nbsp;</p><p><a href=\'https://monitoring.msr.mosreg.ru/documents?module=documents&mode=planPdf&open_dialog={"docId":' . $documentId . '}\'>Посмотреть приказ</a></p>'
                ]
            );
            return $this->mail->send($executorEmail, $caption,
                $letter_body, 'noreply@mosreg.ru', 'html', 'smtp', '', 'noreply@mosreg.ru'
            );
        } else {
            return false;
        }
    }

    public function notificationObject(
        int $signerId,
        int $agreementType,
        int $documentId,
        string $documentName,
        string $mode = 'new',
        bool $allowRemind = true,
        ?string $remindDateTime = '',
        ?string $remindComment = ''
    ): bool
    {
        $user = $this->db->getRegistry('users', " WHERE id = $signerId");
        $executor = $user['result'][$signerId];
        $executorFIO = trim($executor->surname) . ' ' . trim($executor->name) . ' ' . trim($executor->middle_name);
        $executorEmail = $executor->email;
        $agreementAction = $agreementType == 1 ? 'Ваша подпись' : 'Ваше согласование';


        if (strlen(trim($executorEmail)) > 0) {

            $caption = 'Вам поступил акт проверки';
            $letterText =
                '<p>Ожидается ваше согласие или возражения по документу «' . $documentName . '»</p>';


            if ($allowRemind) {
                $this->setRemind(
                    $signerId,
                    $documentId,
                    $signerId,
                    date('Y-m-d H:i:s'),
                    strip_tags($letterText),
                    'https://monitoring.msr.mosreg.ru/roadmap?open_dialog=' . $documentId,
                    $caption,
                    $remindComment,
                    $executorEmail,
                    $executorFIO,
                    $letterText
                );
            }

            // Удаление remind выполняется в вызывающем коде до вызова этого метода

            $this->addRecordToPanel($signerId, $letterText, $documentId, '/documents');

            $letter_body = $this->mail->template_render('/tmpl/letter/task_letter.php',
                [
                    'email' => $executorEmail,
                    'greeting' => 'Здравствуйте, ' . $executorFIO . '!',
                    'caption' => $caption,
                    'task_id' => $documentId,
                    'text' => $letterText . '<p>&nbsp;</p><p><a href="https://monitoring.msr.mosreg.ru/roadmap?open_dialog=' . $documentId . '">Открыть лист согласования</a></p>'
                ]
            );
            return $this->mail->send($executorEmail, $caption,
                $letter_body, 'noreply@mosreg.ru', 'html', 'smtp', '', 'noreply@mosreg.ru'
            );
        } else {
            return false;
        }
    }

    public function sendWSMessage(string $message): bool
    {
        $connector = new Connector();
        $serverUri = 'wss://monitoring.msr.mosreg.ru/websocket';
        $result = false;

        $connector($serverUri)
            ->then(function (WebSocket $conn) use ($serverUri, $message) {
                // Отправляем сообщение
                if ($conn->send($message) != false) {
                    $result = true;
                } else {
                    $result = false;
                }

                // Закрываем соединение
                $conn->close();
            }, function ($e) {
                $result = false;
                echo "Не удалось подключиться: {$e->getMessage()}\n";
            }
            );
        return $result;
    }

    /**
     * @throws \ErrorException
     */
    public function sendWebPush(int $userId, string $title, string $message): bool
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/web-push/config.php';

        $subscriptions = $this->db->select('subscriptions', ' WHERE user_id = ?', [$userId]);

        if (empty($subscriptions)) return false;

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => VAPID_SUBJECT,
                'publicKey' => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY
            ]
        ], [
            //'TTL' => 2419200,
            //'batchSize' => 200,
            //'urgency' => 'high',
            'timeout' => 60,
            'proxy' => PROXY_URL,
            'https_proxy' => PROXY_URL,
            'curlOptions' => [
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 60,
                CURLOPT_PROXY => PROXY_URL,
            ]
        ]
        );

        $payload = json_encode([
                'title' => $title,
                'body' => $message,
                'icon' => '/favicons/icons-192x192.png',
                'timestamp' => time()
            ]
        );
//print_r($payload);
        foreach ($subscriptions as $sub) {
            try {
                $subscription = Subscription::create([
                        'endpoint' => $sub->endpoint,
                        'publicKey' => $sub->p256dh,
                        'authToken' => $sub->auth,
                        'contentEncoding' => 'aes128gcm'
                    ]
                );

                /*$webPush->queueNotification($subscription, 'Hello from PHP!');

// Send the notifications
                $webPush->flush();*/

                $result = $webPush->sendOneNotification($subscription, $payload/*,
                    ['TTL' => 2419200, 'urgency' => 'normal', 'topic' => 'notifications']*/
                );
//echo '<pre>';print_r($result);
                if (!$result->isSuccess()) {
                    // Удаляем просроченные подписки
                    echo '❌ Ошибка: ' . $result->getReason();
                    //$this->rb::exec('DELETE FROM cam_subscriptions WHERE endpoint = \'' . $result->getEndpoint() . '\'');
                }
            } catch (Exception $e) {
                error_log('Push error: ' . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * @throws \RedBeanPHP\RedException
     */
    public function notificationAgreement(
        int $signerId,
        int $documentId,
        string $documentName,
        string $actionType,
        bool $allowRemind = true,
        ?string $remindComment = null,
        ?string $remindDateTime = null
    ): bool
    {
        $user = $this->db->getRegistry('users', " WHERE id = $signerId");
        $executor = $user['result'][$signerId];
        $executorFIO = trim($executor->surname) . ' ' . trim($executor->name) . ' ' . trim($executor->middle_name);
        $executorEmail = $executor->email;

        if (strlen(trim($executorEmail)) > 0) {

            $caption = 'Требуется ваше действие по документу';
            $letterText = '<p>По документу «' . $documentName . '» наступила ваша очередь ' . $actionType . '.</p>' .
                '<p>Пожалуйста, перейдите в лист согласования для выполнения действия.</p>';

            if ($allowRemind) {
                $remindCommentText = $remindComment ?? 'Необходимо согласовать/подписать документ';

                $this->setRemind(
                    $signerId,
                    $documentId,
                    $signerId,
                    $remindDateTime ?? date('Y-m-d H:i:s', strtotime('+1 day')),
                    strip_tags($letterText),
                    'https://monitoring.msr.mosreg.ru/documents?module=documents&mode=planPdf&open_dialog={"docId":' . $documentId . '}',
                    $caption,
                    $remindCommentText,
                    $executorEmail,
                    $executorFIO,
                    $letterText
                );
            }

            $this->addRecordToPanel(
                $signerId,
                $letterText,
                $documentId,
                '/documents'
            );

            $letter_body = $this->mail->template_render('/tmpl/letter/task_letter.php',
                [
                    'email' => $executorEmail,
                    'greeting' => 'Здравствуйте, ' . $executorFIO . '!',
                    'caption' => $caption,
                    'task_id' => $documentId,
                    'text' => $letterText .
                        '<p>&nbsp;</p>' .
                        '<p><a href="https://monitoring.msr.mosreg.ru/documents?module=documents&mode=planPdf&open_dialog={\'docId\':' . $documentId . '}">' .
                        'Открыть лист согласования</a></p>'
                ]
            );

            return $this->mail->send(
                $executorEmail,
                $caption,
                $letter_body,
                'noreply@mosreg.ru',
                'html',
                'smtp',
                '',
                'noreply@mosreg.ru'
            );
        } else {
            // Если email не указан - всё равно добавляем в панель уведомлений
            $this->addRecordToPanel(
                $signerId,
                $letterText ?? 'Требуется ваше действие по документу «' . $documentName . '»',
                $documentId,
                '/documents'
            );
            return false;
        }
    }
}