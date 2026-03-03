<?php

namespace Core;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Fgribreau\MailChecker;

class Mail
{
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
     * @var MailChecker
     */
    private $check;

    public function __construct()
    {
        $this->_get = $_GET;
        $this->_post = $_POST;
        $this->_session = $_SESSION;
        $this->_server = $_SERVER;
        $this->_cookie = $_COOKIE;
        $this->reg = new Registry();
        $this->db = new Db();
        $this->date = new Date();
        $this->check = new MailChecker();
    }

    public function validateEmail(string $email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function checkEmailMailChecker(string $email): bool
    {
        return MailChecker::isValid($email);
    }

    public function checkEmailDomain(string $email): bool
    {
        list($username, $domain) = explode('@', $email);
        return checkdnsrr($domain, 'MX');
    }

    public function checkEmail(string $email): bool
    {
        if (!$this->validateEmail($email)) {
            return false;
        }
        if (!$this->checkEmailDomain($email)) {
            return false;
        }
        if (!$this->checkEmailMailChecker($email)) {
            return false;
        }
        return true;
    }

    public function template_render(string $template_path, array $data_array): string
    {
        extract($data_array);
        ob_start();
        require ROOT . $template_path;
        return ob_get_clean();
    }

    public function send(
        string $recipient,
        string $subject,
        string $message,
        string $sender = '',
        string $type = 'html',
        string $mode = 'smtp',
        string $fileList = '',
        string $replayTo = '',
        string $replayName = 'Information'
    ): bool
    {
        //error_reporting(E_ALL);
        if($this->checkEmail(trim($recipient))) {

            require ROOT . '/core/vendor/autoload.php';
            $mail = new PHPMailer(true);

            try {
                //Server settings
                $mail->SMTPDebug = 0;//SMTP::DEBUG_LOWLEVEL;// Enable verbose debug output

                $mail->setLanguage('ru', '/core/vendor/phpmailer/phpmailer/language/');
                if ($mode == 'smtp' || $mode == '')
                    $mail->isSMTP();                                            // Send using SMTP
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPAutoTLS = true;
                $mail->SMTPSecure = 'tls';
                $mail->CharSet = 'utf-8';

                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
                $mail->Port = SMTP_PORT;

                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                //Recipients
                $mail->setFrom(strlen($sender) > 0 ? $sender : SMTP_FROM, 'МИНСОЦРАЗВИТИЯ');
                $mail->addAddress($recipient);
                $mail->addReplyTo($replayTo, $replayName);
                if (strlen(SMTP_BCC) > 0) {
                    $mail->addBCC(SMTP_BCC);
                }

                // Attachments
                if (strlen($fileList) > 0) {
                    $imgArr = explode(' , ', $fileList);
                    for ($i = 0; $i < count($imgArr); $i++) {
                        $file_send = $_SERVER['DOCUMENT_ROOT'] . $imgArr[$i];
                        $mail->addAttachment($file_send);
                    }
                }

                // Content
                $mail->isHTML($type == 'html' || $type == '');// Set email format to HTML
                $mail->Subject = $subject;
                $mail->Body = $message;
                $mail->AltBody = strip_tags($message);

                $mail->send();
                return true;
            } catch (Exception $e) {
                //echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                return false;
            }
        }else{
            return false;
        }
    }
}