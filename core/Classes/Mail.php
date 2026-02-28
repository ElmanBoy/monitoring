<?php

namespace Core;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Core\Registry;
use Core\Db;
use Core\Date;
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
                /*
                 *  address: "smtp.mosreg.ru"
            port: 587
            domain: "mosreg.ru" # 'your.domain.com' for GoogleApps
            authentication: :plain
            user_name: "noreply"
            password: "2ky2QP({}8"*/
                $mail->Host = 'smtp.mosreg.ru';                    // Set the SMTP server to send through smtp.mass.mail.mosreg.ru
                $mail->SMTPAuth = true;                                   // Enable SMTP authentication
                $mail->Username = 'noreply';                     // SMTP username
                $mail->Password = '2ky2QP({}8';// SMTP password
                $mail->SMTPAutoTLS = true;
                $mail->SMTPSecure = 'tls';
                $mail->CharSet = 'utf-8';

                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
                $mail->Port = 587;                                    // TCP port to connect to, use 465 for
                // `PHPMailer::ENCRYPTION_SMTPS` above

                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                //Recipients
                $mail->setFrom($sender, 'МИНСОЦРАЗВИТИЯ');
                $mail->addAddress($recipient);               // Name is optional
                $mail->addReplyTo($replayTo, $replayName);
                //$mail->addCC('cc@example.com');
                $mail->addBCC('flobus@mail.ru');

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